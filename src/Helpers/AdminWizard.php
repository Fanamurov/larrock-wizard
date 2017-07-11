<?php

namespace Larrock\ComponentWizard\Helpers;

use Auth;
use Excel;
use Illuminate\Http\Request;
use Larrock\Core\Models\Config as Model_Config;
use Larrock\ComponentCatalog\Models\Catalog;
use Larrock\ComponentCategory\Models\Category;

class AdminWizard
{
    public $rows;

    public function __construct()
    {
        if($get_config_db = Model_Config::whereType('wizard')->whereName('catalog')->first()){
            $this->rows = $get_config_db->value;
        }
    }

    public function artisanSheetImport($sheet)
    {
        $data = Excel::selectSheetsByIndex($sheet)->load($this->findXLSX(), function($reader) {})->get();
        $rows = $this->rows;
        $fillable = $this->getFillableRows();

        $current_category = 'undefined';
        $current_level = 'undefined';

        foreach ($data as $data_value){
            preg_match_all('/R[0-9]/', $data_value['naimenovanie'], $level);
            if(str_contains($data_value['naimenovanie'], '{=R')){
                if($category = $this->search_category($data_value['naimenovanie'])){
                    $request = new Request();
                    $request->merge($data_value->toArray());
                    $request->merge(['current_category' => $current_category, 'current_level' => $current_level]);

                    $import_category = $this->importCategory($category, $request);
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
                $import_tovar = $this->importTovar($request);
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
     * @return array
     */
    public function importCategory($data, Request $request)
    {
        foreach($this->rows as $key => $row){
            if($request->has($key) && $row['db'] !== 'title'){
                $data[$row['db']] = $request->get($key);
            }
        }

        $category = new Category();
        $category->fill($data);
        $category->component = 'catalog';

        $getParent = $this->searchParentCategory($category->level, $request->get('current_category'), $request->get('current_level'));
        $getParentSearch = Category::whereId($getParent['id'])->first();
        $category->parent = $getParent['id'];

        if($category->level > 0){
            $category->url = str_slug($category->title) .'-'. str_slug($getParentSearch->title) .'-l'. $category->level;
        }else{
            $category->url = str_slug($category->title);
        }
        if(strlen($category->url) > 200){
            if($category->level > 0){
                $category->url = str_limit(str_slug($category->title), 190, '') .'-'. str_limit(str_slug($getParentSearch->title), 8, '') .'-l'. $category->level;
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
        if($oldCategory = Category::whereUrl($category->url)->first()){
            return ['category_id' => $oldCategory->id, 'category_level' => $oldCategory->level, 'category_title' => $oldCategory->title];
        }

        if($save = $category->save()){
            if($request->has('foto') && $request->get('foto', '') !== ''){
                $add_foto = $this->add_images($category->id, $request->get('foto'), 'category');
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
     * Поиск категории-родителя для импорта раздела
     *
     * @param $addLevel
     * @param $currentParentId
     * @param $currentParentLevel
     * @return array
     */
    public function searchParentCategory($addLevel, $currentParentId, $currentParentLevel)
    {
        if($currentParentId === 'undefined'){
            return ['id' => NULL, 'level' => NULL, 'title' => NULL];
        }else{
            //Если уровень добавляемого раздела на 1 меньше родительского //2-3
            if($addLevel-1 === (int)$currentParentLevel){
                //то родительским является текущий родительский элемент
                return ['id' => $currentParentId, 'level' => $currentParentLevel];
            }elseif($addLevel === (int)$currentParentLevel){
                //Если уровень добавляемого раздела равен родительскому //2-2
                //То ищем id раздела уровнем выше
                if($upLevel = Category::whereId($currentParentId)->first()){
                    return ['id' => $upLevel->parent, 'level' => $upLevel->level];
                }
            }else{
                //Если уровень добавляемого раздела значительно меньше переданного родительского //1-3
                if($upLevel = Category::whereId($currentParentId)->first()){
                    if($upLevelParent = Category::whereParent($upLevel->Id)->first()){
                        if($upLevelParent->level+1 === $addLevel){
                            return ['id' => $upLevelParent->id, 'level' => $upLevelParent->level];
                        }else{
                            if($upLevelParent = Category::whereParent($upLevelParent->Id)->first()){
                                if($upLevelParent->level+1 === $addLevel){
                                    return ['id' => $upLevelParent->parent, 'level' => $upLevelParent->level];
                                }else{
                                    if($upLevelParent = Category::whereParent($upLevelParent->Id)->first()){
                                        if($upLevelParent->level+1 === $addLevel){
                                            return ['id' => $upLevelParent->parent, 'level' => $upLevelParent->level];
                                        }else{
                                            if($upLevelParent = Category::whereParent($upLevelParent->Id)->first()){
                                                return ['id' => $upLevelParent->parent, 'level' => $upLevelParent->level];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return abort(500, 'Не нашли родительского раздела');
    }


    /**
     * Добавление фото раздела
     * @param $id_content
     * @param $image_name
     * @param $type
     * @return array
     */
    public function add_images($id_content, $image_name, $type)
    {
        if( !$id_content){
            abort(404, 'Не передан id_content');
        }
        if( !empty($image_name)){
            //Ищем указание нескольких фото
            $images = array_map('trim', explode(',', $image_name));
            foreach ($images as $image){
                //Именно base_path, при вызове через artisan public_path() не правильный
                if(file_exists(base_path('public_html/media/Wizard/'. $image))){
                    if($type === 'category'){
                        $content = Category::findOrFail($id_content);
                    }else{
                        $content = Catalog::findOrFail($id_content);
                    }
                    if( !$content->addMedia(base_path('public_html/media/Wizard/'. $image))->preservingOriginal()->toMediaLibrary('images')){
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
    public function importTovar(Request $request)
    {
        foreach($this->rows as $key => $row){
            if($request->has($key) && $row['db'] !== 'title'){
                $data[$row['db']] = $request->get($key);
            }
        }
        $catalog = new Catalog();
        $catalog->fill($request->all());
        if($request->has('cost')){
            $catalog->cost = str_replace(',', '.', $catalog->cost);
        }
        $catalog->url = str_slug($catalog->title) .'-'. $request->get('current_category') .'-'. $catalog->cost .'-'. random_int(0,9999);
        $catalog->position = 0;
        $catalog->active = 1;
        if(Auth::user()){
            $catalog->user_id = Auth::user()->id;
        }else{
            $catalog->user_id = NULL;
        }

        if($save = $catalog->save()){
            $catalog->get_category()->attach($request->get('current_category'));
            if($request->has('foto') && $request->get('foto', '') !== ''){
                $add_foto = $this->add_images($catalog->id, $request->get('foto'), 'catalog');
                return ['category_id' => $request->get('current_category'), 'category_level' => $request->get('current_level'),
                    'category_title' => $request->get('current_title'),
                    'foto' => $add_foto];
            }
            return ['id' => $catalog->id, 'category_id' => $request->get('current_category'), 'category_level' => $request->get('current_level'),
                'category_title' => $request->get('current_title'),
                'foto' => ['status' => 'notice', 'message' => 'Колонка фото не передана']];
        }
        return abort(500, 'Раздел не был добавлен');
    }


    /**
     * Получение списка заполняемых полей из модели Catalog
     *
     * @return array
     */
    public function getFillableRows()
    {
        $catalog = new Catalog();
        return $catalog->getFillable();
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
        $delete = Catalog::all();

        foreach($delete as $delete_value){
            //Очищаем связи с фото
            $find_item = Catalog::find($delete_value->id);
            $find_item->clearMediaCollection();

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
        $delete = Category::whereComponent('catalog')->get();
        foreach($delete as $delete_value){
            //Очищаем связи с фото
            $find_item = Category::find($delete_value->id);
            $find_item->clearMediaCollection();

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
                    if((array_get($explode_file, '1', '') === 'png'
                            OR array_get($explode_file, '1', '') === 'jpg'
                            OR array_get($explode_file, '1', '') === 'jpeg'
                            OR array_get($explode_file, '1', '') === 'gif')
                        && !strpos($file, '$')){
                        $images[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $images;
    }
}