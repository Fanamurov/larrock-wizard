<?php

namespace Larrock\ComponentWizard;

use File;
use Cache;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Larrock\Core\Traits\ShareMethods;
use Larrock\Core\Models\Config as Model_Config;
use Larrock\ComponentWizard\Helpers\AdminWizard;

/**
 * Class AdminWizardController.
 */
class AdminWizardController extends Controller
{
    use ShareMethods;

    protected $config;

    public function __construct(AdminWizard $adminWizard)
    {
        $this->shareMethods();
        $this->middleware(\LarrockPages::combineAdminMiddlewares());
        $Component = new WizardComponent();
        $this->config = $Component->shareConfig();
        \Config::set('breadcrumbs.view', 'larrock::admin.breadcrumb.breadcrumb');
    }

    /**
     * Генерирование страницы с названиями листов из прайса
     * after: ajax-загрузка содержимого листов.
     *
     * @param AdminWizard $adminWizard
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AdminWizard $adminWizard)
    {
        if ($adminWizard->findXLSX()) {
            $data['data'] = Excel::load($adminWizard->findXLSX(), function ($reader) {
                $reader->takeRows(1);
            })->get();

            $data['rows'] = $adminWizard->rows;
            $data['xlsx'] = $adminWizard->findXLSX();
            $data['fillable'] = $adminWizard->getFillableRows();
        } else {
            \Session::push('message.danger', '.xlsx-файл отсутствует в директории /resources/wizard');
            $data = [];
        }
        Cache::forget('scanImageDir');

        return view('larrock::admin.wizard.parse', $data);
    }

    /**
     * Парсинг содержимого листов прайса.
     * @param int $sheet
     * @param AdminWizard $adminWizard
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function sheetParse($sheet = 0, AdminWizard $adminWizard)
    {
        if (env('APP_ENV') === 'local') {
            ini_set('max_execution_time', 120);
        }

        $data['data'] = Excel::selectSheetsByIndex($sheet)->load($adminWizard->findXLSX(), function ($reader) {
        })->get();
        $data['rows'] = $adminWizard->rows;
        $data['fillable'] = $adminWizard->getFillableRows();

        foreach ($data['data']->getHeading() as $heading) {
            if (! array_key_exists($heading, $data['rows'])) {
                $data['rows'][$heading]['db'] = null;
                $data['rows'][$heading]['slug'] = null;
                $data['rows'][$heading]['template'] = null;
                $data['rows'][$heading]['filters'] = null;
                $data['rows'][$heading]['admin'] = null;
            }
        }

        $data['sheet'] = $sheet;
        $data['images'] = Cache::rememberForever('scanImageDir', function () use ($adminWizard) {
            return $adminWizard->scanImageDir();
        });
        if (\count($data['data']) > 0) {
            return view('larrock::admin.wizard.sheet', $data);
        }
        \Session::push('message.danger', 'В листе #'.$sheet.' не найдено данных');

        return response('В листе #'.$sheet.' не найдено данных');
    }

    /**
     * Метод импорта строки из прайса в БД.
     * @param Request $request
     * @param AdminWizard $adminWizard
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function importrow(Request $request, AdminWizard $adminWizard)
    {
        if (! $request->has('title')) {
            return response()->json(['status' => 'error', 'message' => 'Title not found in request data']);
        }
        if ($category = $adminWizard->search_category($request->get('title'))) {
            return response()->json($adminWizard->importCategory($category, $request));
        }

        return response()->json($adminWizard->importTovar($request));
    }

    /**
     * Очистка БД каталога перед новым импортом
     * @param AdminWizard $adminWizard
     * @param null|bool $manual
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function clear(AdminWizard $adminWizard, $manual = null)
    {
        $clearCatalog = $adminWizard->deleteCatalog();
        $clearCategory = $adminWizard->deleteCategoryCatalog();
        \Cache::flush();
        if ($clearCatalog === true && $clearCategory === true) {
            if ($manual) {
                \Session::push('message.success', 'Каталог очищен');

                return back()->withInput();
            }

            return response()->json('Товары и разделы каталога удалены');
        }
        if ($manual) {
            \Session::push('message.success', 'Ошибка: каталог не очищен');

            return back()->withInput();
        }

        return response()->json('Каталог не очищен', 500);
    }

    /**
     * Обновление файла прайса по ячейке.
     * @param Request $request
     * @param AdminWizard $adminWizard
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function updateXLSX(Request $request, AdminWizard $adminWizard)
    {
        if ($request->get('cell', '') !== '' && $request->has('value') && $request->get('sheet', '') !== '') {
            Excel::load($adminWizard->findXLSX(), function ($file) use ($request) {
                $file->setActiveSheetIndex($request->get('sheet'));
                $sheet = $file->getActiveSheet();
                $sheet->setCellValue($request->get('cell'), $request->get('value'));
            })->store('xlsx', resource_path('wizard'));

            return response()->json(['status' => 'success', 'message' => 'Ячейка '.$request->get('cell', 'UNDEFINED').' перезаписана на значение '.$request->get('value')]);
        }

        return response()->json(['status' => 'error', 'message' => 'Не переданы координаты ячейки или ее значение']);
    }

    /**
     * Запись конфига полей.
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
        foreach ($request->get('colomns') as $key => $value) {
            $config[$value]['db'] = array_get($db, $key, '');
            $config[$value]['slug'] = trim(array_get($slug, $key, ''));
            $config[$value]['template'] = array_get($template, $key, '');
            $config[$value]['filters'] = array_get($filters, $key, '');
            $config[$value]['admin'] = array_get($admin, $key, '');
        }

        if (! $data = Model_Config::whereType('wizard')->whereName('catalog')->first()) {
            $data = new Model_Config();
            $data->name = 'catalog';
            $data->type = 'wizard';
            $data->value = serialize($config);
        } else {
            $merge = array_merge($data->value, $config);
            $data->value = serialize($merge);
        }
        
        if ($data->save()) {
            \Session::push('message.success', 'Настройки полей импорта сохранены');
        } else {
            \Session::push('message.danger', 'Настройки полей импорта не сохранены');
        }

        \Cache::flush();

        return back()->withInput();
    }

    /**
     * Загрузка нового файла прайса.
     * При старте происходит удаление всей директории resource_path('wizard') со всеми файлами внутри.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loadXLSX(Request $request)
    {
        File::cleanDirectory(resource_path('wizard'));
        if (! file_exists(resource_path('wizard'))) {
            File::makeDirectory(resource_path('wizard'), 0755, true);
        }

        if (! $request->file('xlsx')) {
            \Session::push('message.danger', 'Файл не передан');

            return back()->withInput();
        }
        $extension = $request->file('xlsx')->getClientOriginalExtension();
        if ($extension === 'xlsx') {
            $uniqueFileName = $request->file('xlsx')->getClientOriginalName();
            if ($request->file('xlsx')->move(resource_path('wizard/'), $uniqueFileName)) {
                \Session::push('message.success', 'Файл прайса '.$uniqueFileName.' успешно загружен');
            } else {
                \Session::push('message.danger', 'Файл прайса '.$uniqueFileName.' не был загружен');
            }
        } else {
            \Session::push('message.danger', 'Загружаемый формат файла .'.$extension.' отличается от требуемого .xlsx');
        }

        return back()->withInput();
    }

    /**
     * Загрузка фото для импорта.
     * @param Request $request
     * @return AdminWizardController|\Illuminate\Http\RedirectResponse
     */
    public function loadImages(Request $request)
    {
        if (! file_exists(public_path('media/wizard'))) {
            File::makeDirectory(public_path('media/wizard'), 0755, true);
        }

        if (! $request->file('images')) {
            \Session::push('message.danger', 'Файлы не передан');

            return back()->withInput();
        }

        /* @noinspection ForeachSourceInspection */
        foreach ($request->file('images') as $image) {
            $extension = $image->getClientOriginalExtension();
            $allow_extensions = ['jpg', 'jpeg', 'gif', 'png'];
            if (\in_array($extension, $allow_extensions, true)) {
                $uniqueFileName = $image->getClientOriginalName();
                if ($image->move(public_path('media/wizard'), $uniqueFileName)) {
                    \Session::push('message.success', 'Фото '.$uniqueFileName.' успешно загружено');
                } else {
                    \Session::push('message.danger', 'Фото '.$uniqueFileName.' не был загружено');
                }
            } else {
                \Session::push('message.danger', 'Загружаемый формат файла '.$image->getClientOriginalName().' отличается от требуемых (jpg, jpeg, gif, png)');
            }
        }

        return back()->withInput();
    }

    /**
     * Создание миграции и ее выполнение для создание нового поля в таблице catalog.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createMigration(Request $request)
    {
        if ($request->has('column') && $request->get('column', '') !== '') {
            $schema = $request->get('column').':text:nullable';
            \Artisan::call('make:migration:schema', ['name' => 'update_catalog_table', '--schema' => $schema, '--model' => 'false']);
            \Artisan::call('migrate');
            \Cache::flush();

            return response()->json(['status' => 'success', 'message' => $request->get('column').' добавлена в колонки таблицы catalog']);
        }

        return response()->json(['status' => 'error', 'message' => 'Column не передано или пустое']);
    }

    /** Откат изменений */
    public function rollbackMigration()
    {
        \Artisan::call('migrate:rollback');
    }
}
