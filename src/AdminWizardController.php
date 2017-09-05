<?php

namespace Larrock\ComponentWizard;

use Alert;
use Breadcrumbs;
use Cache;
use Excel;
use File;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Larrock\Core\Models\Config as Model_Config;
use Larrock\ComponentWizard\Helpers\AdminWizard;

/**
 * Class AdminWizardController
 * @package App\Http\Controllers\Admin
 */
class AdminWizardController extends Controller
{
    protected $config;

    public function __construct(AdminWizard $adminWizard)
    {
        $Component = new WizardComponent();
        $this->config = $Component->shareConfig();

        \Config::set('breadcrumbs.view', 'larrock::admin.breadcrumb.breadcrumb');
        Breadcrumbs::register('admin.'. $this->config->name .'.index', function($breadcrumbs){
            $breadcrumbs->push($this->config->title, route('admin.wizard.help'));
        });
    }


    /**
     * Генерирование страницы с названиями листов из прайса
     * after: ajax-загрузка содержимого листов
     *
     * @param AdminWizard $adminWizard
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AdminWizard $adminWizard)
    {
        if($adminWizard->findXLSX()){
            $data['data'] = Excel::load($adminWizard->findXLSX(), function($reader) {
                $reader->takeRows(1);
            })->get();

            $data['rows'] = $adminWizard->rows;
            $data['xlsx'] = $adminWizard->findXLSX();
            $data['fillable'] = $adminWizard->getFillableRows();
        }else{
            Alert::add('errorAdmin', '.xlsx-файл отсутствует в директории /resources/wizard')->flash();
            $data = [];
        }

        Breadcrumbs::register('admin.wizard.result', function($breadcrumbs)
        {
            $breadcrumbs->parent('admin.wizard.index');
        });

        Cache::forget('scanImageDir');

        return view('larrock::admin.wizard.parse', $data);
    }

    public function help()
    {
        Breadcrumbs::register('admin.wizard.help', function($breadcrumbs)
        {
            $breadcrumbs->parent('admin.wizard.index');
            $breadcrumbs->push('Помощь');
        });

        return view('larrock::admin.wizard.help');
    }


    /**
     * Парсинг содержимого листов прайса
     *
     * @param int $sheet
     * @param AdminWizard $adminWizard
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function sheetParse($sheet = 0, AdminWizard $adminWizard)
    {
        $data['data'] = Excel::selectSheetsByIndex($sheet)->load($adminWizard->findXLSX(), function($reader) {})->get();
        $data['rows'] = $adminWizard->rows;
        $data['fillable'] = $adminWizard->getFillableRows();
        $data['sheet'] = $sheet;
        $data['images'] = Cache::remember('scanImageDir', 1440, function() use ($adminWizard){
            return $adminWizard->scanImageDir();
        });
        if(count($data['data']) > 0){
            return view('larrock::admin.wizard.sheet', $data);
        }else{
            Alert::add('errorAdmin', 'В листе #'. $sheet .' не найдено данных');
            return response('В листе #'. $sheet .' не найдено данных');
        }
    }


    /**
     * Метод импорта строки из прайса в БД
     *
     * @param Request $request
     * @param AdminWizard $adminWizard
     * @return \Illuminate\Http\JsonResponse
     */
    public function importrow(Request $request, AdminWizard $adminWizard)
    {
        if( !$request->has('title')){
            return response()->json(['status' => 'error', 'message' => 'Title not found in request data']);
        }
        if($category = $adminWizard->search_category($request->get('title'))){
            return response()->json($adminWizard->importCategory($category, $request));
        }
        return response()->json($adminWizard->importTovar($request));
    }


    /**
     * Очистка БД каталога перед новым импортом
     *
     * @param AdminWizard $adminWizard
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function clear(AdminWizard $adminWizard, $manual = NULL)
    {
        $clearCatalog = $adminWizard->deleteCatalog();
        $clearCategory = $adminWizard->deleteCategoryCatalog();
        \Cache::flush();
        if($clearCatalog === TRUE && $clearCategory === TRUE){
            if($manual){
                Alert::add('successAdmin', 'Каталог очищен')->flash();
                return back()->withInput();
            }
            return response()->json('Товары и разделы каталога удалены');
        }
        if($manual){
            Alert::add('successAdmin', 'Ошибка: каталог не очищен')->flash();
            return back()->withInput();
        }
        return response()->json('Каталог не очищен', 500);
    }


    /**
     * Обновление файла прайса по ячейке
     *
     * @param Request $request
     * @param AdminWizard $adminWizard
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function updateXLSX(Request $request, AdminWizard $adminWizard)
    {
        if($request->get('cell', '') !== '' && $request->has('value') && $request->get('sheet', '') !== ''){
            Excel::load($adminWizard->findXLSX(), function($file) use ($request) {
                $file->setActiveSheetIndex($request->get('sheet'));
                //$sheet->setCellValue(5, 'Test String');
                $sheet = $file->getActiveSheet();
                $sheet->setCellValue($request->get('cell'), $request->get('value'));
                //dd($sheet->getCellValue('C9'));
            })->store('xlsx', resource_path('wizard'));
            return response()->json(['status' => 'success', 'message' => 'Ячейка '. $request->get('cell', 'UNDEFINED') .' перезаписана на значение '. $request->get('value')]);
        }
        return response()->json(['status' => 'error', 'message' => 'Не переданы координаты ячейки или ее значение']);
    }


    /**
     * Запись конфига полей
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeConfig(Request $request)
    {
        $config = [];
        $db = $request->get('db');
        $slug = $request->get('slug');
        $template = $request->get('template');
        $filters = $request->get('filters');
        $admin = $request->get('admin');
        foreach($request->get('colomns') as $key => $value){
            $config[$value]['db'] = array_get($db, $key, '');
            $config[$value]['slug'] = trim(array_get($slug, $key, ''));
            $config[$value]['template'] = array_get($template, $key, '');
            $config[$value]['filters'] = array_get($filters, $key, '');
            $config[$value]['admin'] = array_get($admin, $key, '');
        }

        if( !$data = Model_Config::whereType('wizard')->whereName('catalog')->first()){
            $data = new Model_Config();
            $data->name = 'catalog';
            $data->type = 'wizard';
            $data->value = serialize($config);
        }else{
            $data->value = serialize($config);
        }
        if($data->save()){
            Alert::add('successAdmin', 'Настройки полей импорта сохранены')->flash();
        }else{
            Alert::add('errorAdmin', 'Настройки полей импорта не сохранены')->flash();
        }
        return back()->withInput();
    }


    /**
     * Загрузка нового файла прайса.
     * При старте происходит удаление всей директории resource_path('wizard') со всеми файлами внутри
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loadXLSX(Request $request)
    {
        File::cleanDirectory(resource_path('wizard'));
        if( !file_exists(resource_path('wizard'))){
            File::makeDirectory(resource_path('wizard'), 0755, true);
        }

        if( !$request->file('xlsx')){
            Alert::add('errorAdmin', 'Файл не передан')->flash();
            return back()->withInput();
        }
        $extension = $request->file('xlsx')->getClientOriginalExtension();
        if($extension === 'xlsx'){
            $uniqueFileName = $request->file('xlsx')->getClientOriginalName();
            if($request->file('xlsx')->move(resource_path('wizard/'), $uniqueFileName)){
                Alert::add('successAdmin', 'Файл прайса '. $uniqueFileName .' успешно загружен')->flash();
            }else{
                Alert::add('errorAdmin', 'Файл прайса '. $uniqueFileName .' не был загружен')->flash();
            }
        }else{
            Alert::add('errorAdmin', 'Загружаемый формат файла .'. $extension .' отличается от требуемого .xlsx')->flash();
        }
        return back()->withInput();
    }


    /**
     * Загрузка фото для импорта
     *
     * @param Request $request
     * @return AdminWizardController|\Illuminate\Http\RedirectResponse
     */
    public function loadImages(Request $request)
    {
        if( !file_exists(public_path('media/wizard'))){
            File::makeDirectory(public_path('media/wizard'), 0755, true);
        }

        if( !$request->file('images')){
            Alert::add('errorAdmin', 'Файлы не передан')->flash();
            return back()->withInput();
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($request->file('images') as $image){
            $extension = $image->getClientOriginalExtension();
            $allow_extensions = ['jpg', 'jpeg', 'gif', 'png'];
            if(in_array($extension, $allow_extensions, TRUE)){
                $uniqueFileName = $image->getClientOriginalName();
                if($image->move(public_path('media/wizard'), $uniqueFileName)){
                    Alert::add('successAdmin', 'Фото '. $uniqueFileName .' успешно загружено')->flash();
                }else{
                    Alert::add('errorAdmin', 'Фото '. $uniqueFileName .' не был загружено')->flash();
                }
            }else{
                Alert::add('errorAdmin', 'Загружаемый формат файла '. $image->getClientOriginalName() .' отличается от требуемых (jpg, jpeg, gif, png)')->flash();
            }
        }

        return back()->withInput();
    }


    /**
     * Создание миграции и ее выполнение для создание нового поля в таблице catalog
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createMigration(Request $request)
    {
        if($request->has('column') && $request->get('column', '') !== ''){
            $schema = $request->get('column') .':text:nullable';
            \Artisan::call('make:migration:schema', ['name' => 'update_catalog_table', '--schema' => $schema, '--model' => 'false']);
            \Artisan::call('migrate');
            return response()->json(['status' => 'success', 'message' => $request->get('column') .' добавлена в колонки таблицы catalog']);
        }
        return response()->json(['status' => 'error', 'message' => 'Column не передано или пустое']);
    }

    /**
     * Откат изменений
     */
    public function rollbackMigration(){
        \Artisan::call('migrate:rollback');
    }
}
