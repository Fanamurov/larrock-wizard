<?php

namespace Larrock\ComponentWizard\Helpers;

use Auth;
use Excel;
use Illuminate\Http\Request;
use Larrock\Core\Helpers\Tree;
use Larrock\Core\Models\Config as Model_Config;
use Larrock\ComponentCatalog\Facades\LarrockCatalog;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Spatie\MediaLibrary\Media;

class AdminWizard
{
    public $rows;

    public function __construct()
    {
        $this->rows = [];
        if(config('larrock-wizard.rows')){
            $this->rows = config('larrock-wizard.rows');
        }
        if($get_config_db = Model_Config::whereType('wizard')->whereName('catalog')->first()){
            if(is_array($get_config_db->value)){
                foreach ($get_config_db->value as $key => $value){
                    $this->rows[$key] = $value;
                }
            }
        }
    }

    /**
     * Запуск импорта через artisan
     *
     * @param int   $sheet          Номер листа .xlsx для импорта (начиная с нуля)
     * @param null  $bar            Прогресс бар для artisan
     * @param null  $sheet_data     Данные из xls
     * @param null  $sleep          Сколько секунд ждать после 1 секунды выполнения (sweb привет)
     * @param null  $withoutimage   Не перегенерировать фотографии
     */
    public function artisanSheetImport($sheet, $bar = NULL, $sheet_data = NULL, $sleep = NULL, $withoutimage = NULL)
    {
        if($sheet_data){
            $data = $sheet_data;
        }else{
            $data = Excel::selectSheetsByIndex($sheet)->load($this->findXLSX(), function($reader) {})->get();
        }

        $current_category = 'undefined';
        $current_level = 'undefined';

        $start = microtime(true);

        foreach ($data as $data_value){
            if($sleep && $sleep > 0){
                if(microtime(true) - $start > 1){
                    echo 'sleep '. $sleep .' seconds';
                    sleep($sleep);
                    $start = microtime(true);
                }
            }
            if(str_contains($data_value['naimenovanie'], '{=R')){
                if($category = $this->search_category($data_value['naimenovanie'])){
                    $request = new Request();
                    $request->merge($data_value->toArray());
                    $request->merge(['current_category' => $current_category, 'current_level' => $current_level]);

                    $import_category = $this->importCategory($category, $request, $withoutimage);
                    $current_category = $import_category['category_id'];
                    $current_level = $import_category['category_level'];
                }
            }else{
                $request = new Request();

                $data = [];
                foreach($this->rows as $key => $row){
                    if($data_value->has($key)){
                        if(empty($row['db']) && $key === 'foto'){
                            $data['foto'] = $data_value->get($key);
                        }else{
                            $data[$row['db']] = $data_value->get($key);
                        }
                    }
                }

                $request->merge($data);
                $request->merge(['current_category' => $current_category, 'current_level' => $current_level]);
                if(isset($request->title) && !empty($request->title)){
                    $this->importTovar($request, $withoutimage);
                }
            }
            if($bar){
                $bar->advance();
            }
        }
    }


    /**
     * Поиск названия файла прайса для импорта
     * @return string
     */
    public function findXLSX()
    {
        if(\File::exists(resource_path('wizard'))){
            $files = collect(\File::allFiles(resource_path('wizard')));
            $filtered = $files->filter(function ($value, $key) {
                return $value->getExtension() === 'xlsx';
            });
            return $filtered->first();
        }
    }


    /**
     * Импорт раздела
     *
     * @param $data
     * @param Request $request
     * @param null $withoutimage
     * @return array
     */
    public function importCategory($data, Request $request, $withoutimage = NULL)
    {
        $prev_category = $request->get('current_category');
        $prev_level = $request->get('current_level');

        foreach($this->rows as $key => $row){
            if($request->has($key) && $row['db'] !== 'title'){
                $data[$row['db']] = $request->get($key);
            }
        }

        $category = LarrockCategory::getModel()->fill($data);

        $category->component = 'catalog';

        if($category->level === 1){
            $category->parent = NULL;
        }else{
            if((int)$prev_level +1 === $category->level){
                $category->parent = $prev_category;
            }
            else{
                $prev_category_data = LarrockCategory::getModel()->find($prev_category);
                $get_parent = collect($prev_category_data->parent_tree)->where('level', $category->level - 1);
                $category->parent = $get_parent->first()->id;
            }
        }

        $slug_parent = \Cache::remember('getSlugWizard'. $category->parent, 1440, function() use ($category){
            if($getParentSearch = LarrockCategory::getModel()->find($category->parent)){
                return str_slug($getParentSearch->title);
            }
            return '';
        });

        if($category->level > 1){
            $category->url = str_slug($category->title) .'-'. $slug_parent .'-l'. $category->level;
        }else{
            $category->url = str_slug($category->title);
        }
        if(strlen($category->url) > 200){
            if($category->level > 1){
                $category->url = str_limit(str_slug($category->title), 190, '') .'-'. str_limit($slug_parent, 8, '') .'-l'. $category->level;
            }else{
                $category->url = str_limit($category->url, 200, '');
            }
        }

        $category->sitemap = 1;
        $category->position = 0;
        $category->active = 1;
        if(Auth::user()){
            $category->user_id = Auth::user()->id;
        }else{
            $category->user_id = NULL;
        }

        //Проверяем, вносили ли мы уже эту категорию в базу
        if($oldCategory = LarrockCategory::getModel()->whereUrl($category->url)->first()){
            return ['category_id' => $oldCategory->id, 'category_level' => $oldCategory->level, 'category_title' => $oldCategory->title];
        }

        if(empty($category->title)){
            \Log::error('Импорт раздела не прошел', $category);
            return abort(500, 'Раздел не был добавлен');
        }

        if($save = $category->save()){
            if($request->has('foto') && $request->get('foto', '') !== ''){
                $add_foto = $this->add_images($category->id, $request->get('foto'), 'category', $withoutimage);
                return ['category_id' => $category->id, 'category_level' => $category->level,
                    'category_title' => $category->title,
                    'foto' => $add_foto];
            }
            return ['category_id' => $category->id, 'category_level' => $category->level,
                'category_title' => $category->title,
                'foto' => ['status' => 'notice', 'message' => 'Колонка фото не передана']];
        }

        return abort(500, 'Раздел не был добавлен');
    }


    /**
     * Добавление фото раздела
     * @param $id_content
     * @param $image_name
     * @param $type
     * @param $withoutimage
     * @return array
     */
    public function add_images($id_content, $image_name, $type, $withoutimage = NULL)
    {
        if( !$id_content){
            abort(404, 'Не передан id_content');
        }
        if($withoutimage && !empty($image_name)){
            $model_type = LarrockCatalog::getModelName();
            if($type === 'category'){
                $model_type = LarrockCategory::getModelName();
            }
            $explode_name = explode('.', $image_name);
            $name = str_replace('.'. array_last($explode_name), '', $image_name);
            $new_media = new Media();
            $new_media['model_id'] = $id_content;
            $new_media['model_type'] = $model_type;
            $new_media['collection_name'] = 'images';
            $new_media['name'] = $name;
            $new_media['file_name'] = $image_name;
            $new_media['size'] = $image_name;
            $new_media->save();
            return ['status' => 'notice', 'message' => 'Фото не обрабатываются'];
        }
        if( !empty($image_name)){
            //Ищем указание нескольких фото
            $images = array_map('trim', explode(',', $image_name));
            foreach ($images as $image){
                //Именно base_path, при вызове через artisan public_path() не правильный
                if(file_exists(base_path('public_html/media/Wizard/'. $image))){
                    if($type === 'category'){
                        $content = LarrockCategory::getModel()->findOrFail($id_content);
                    }elseif($type === 'catalog'){
                        $content = LarrockCatalog::getModel()->findOrFail($id_content);
                    }
                    if( !$content->addMedia(base_path('public_html/media/Wizard/'. $image))->preservingOriginal()->toMediaCollection('images')){
                        return ['status' => 'error', 'message' => 'Фото '. $image. ' найдено, но не обработано'];
                    }
                }else{
                    return ['status' => 'error', 'message' => 'Фото '. $image. ' не найдено'];
                }
            }
            return ['status' => 'success', 'message' => 'Фотографии '. $image_name. ' добавлены'];
        }

        return ['status' => 'notice', 'message' => 'У товара фото не назначено'];
    }


    /**
     * Импорт товара каталога
     *
     * @param Request $request
     * @return array
     */
    public function importTovar(Request $request, $withoutimage = NULL)
    {
        foreach($this->rows as $key => $row){
            if($request->has($key) && $row['db'] !== 'title'){
                $data[$row['db']] = $request->get($key);
            }
        }
        $catalog = LarrockCatalog::getModel()->fill($request->all());

        if($request->has('cost')){
            $catalog->cost = str_replace(',', '.', $catalog->cost);
        }
        $catalog->url = str_slug($catalog->title);

        if(strlen($catalog->url) > 120){
            $catalog->url = str_limit($catalog->url, 120);
        }

        //Проверяем совпадение по url-товаров
        $search_match = \Cache::remember(sha1('searchMatch-'. $catalog->url), 1440, function() use ($catalog){
            return LarrockCatalog::getModel()->whereUrl($catalog->url)->first();
        });

        if($search_match && $find_tovar = LarrockCatalog::getModel()->whereTitle($catalog->title)->latest('id')->first()){
            //Нашли совпадение по базе, ищем наибольший постфикс и делаем +1
            $explode = explode('-ccc', $find_tovar->url);
            if(array_key_exists(1, $explode)){
                echo $explode[1];
                $index = (int)$explode[1] +1;
                $catalog->url = $catalog->url .'-ccc'. $index;
            }else{
                $catalog->url .= '-ccc1';
            }
        }

        $catalog->position = 0;
        $catalog->active = 1;
        if(Auth::user()){
            $catalog->user_id = Auth::user()->id;
        }else{
            $catalog->user_id = NULL;
        }

        if(empty($catalog->title)){
            \Log::error('Импорт товара не прошел', $catalog);
            return abort(500, 'Товар не был добавлен');
        }

        if($save = $catalog->save()){
            $catalog->get_category()->attach($request->get('current_category'));
            if($request->has('foto') && $request->get('foto', '') !== ''){
                $add_foto = $this->add_images($catalog->id, $request->get('foto'), 'catalog', $withoutimage);
                return ['category_id' => $request->get('current_category'), 'category_level' => $request->get('current_level'),
                    'category_title' => $request->get('current_title'),
                    'foto' => $add_foto];
            }
            return ['id' => $catalog->id, 'category_id' => $request->get('current_category'), 'category_level' => $request->get('current_level'),
                'category_title' => $request->get('current_title'),
                'foto' => ['status' => 'notice', 'message' => 'Колонка фото не передана']];
        }
        return abort(500, 'Товар не был добавлен');
    }


    /**
     * Получение списка заполняемых полей из модели Catalog
     *
     * @return array
     */
    public function getFillableRows()
    {
        return LarrockCatalog::getModel()->getFillable();
    }


    /**
     * Парсинг поля из прайса и поиск метки раздела. Делаем вывод раздел это или товар
     *
     * @param $row
     * @return array|bool
     */
    public function search_category($row)
    {
        $category = [];
        if(preg_match('/{=R\d=}/', $row, $match)){
            if(preg_match('/(.*?){=R\d=}/', $row, $title)){
                $category['title'] = $title['1'];
            }
            if(preg_match('/{=R(.*?)=}/', $row, $level)){
                $category['level'] = $level['1'];
            }
            return $category;
        }
        return FALSE;
    }


    /**
     * Удаление всех товаров каталога и открепление разделов
     * Очистка связей с фото
     *
     * @return bool
     */
    public function deleteCatalog()
    {
        $delete = LarrockCatalog::getModel()->all();

        foreach($delete as $delete_value){
            //Очищаем связи с фото
            if($find_item = LarrockCatalog::getModel()->find($delete_value->id)){
                $find_item->clearMediaCollection('images');
            }
            $delete_value->delete();
            if($delete_value->get_category()->count() > 0){
                $delete_value->get_category()->detach($delete_value->category, ['catalog_id' => $delete_value->id]);
            }
        }
        return TRUE;
    }


    /**
     * Удаление разделов каталога. Выполнять только после deleteCatalog()
     * Очистка связей с фото
     *
     * @return bool
     */
    public function deleteCategoryCatalog()
    {
        $delete = LarrockCategory::getModel()->whereComponent('catalog')->get();
        foreach($delete as $delete_value){
            //Очищаем связи с фото
            if($find_item = LarrockCategory::getModel()->find($delete_value->id)){
                $find_item->clearMediaCollection('images');
            }

            $delete_value->delete();
        }
        return TRUE;
    }

    /**
     * Сканирование директории с картинками для экспорта
     *
     * @return array
     */
    public function scanImageDir()
    {
        $images = [];
        if(file_exists(public_path('media/Wizard')) && $handle = opendir(public_path('media/Wizard'))){
            while(false !== ($file = readdir($handle))){
                if($file !== '.' && $file !== '..'){
                    $explode_file = explode('.', $file);
                    $allow_extensions = ['png', 'jpg', 'jpeg', 'gif'];
                    if(in_array(last($explode_file), $allow_extensions, false)){
                        $images[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $images;
    }
}