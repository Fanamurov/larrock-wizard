<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentCatalog\Facades\LarrockCatalog;
use Larrock\ComponentCatalog\Models\Catalog;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\ComponentCategory\Models\Category;
use Spatie\MediaLibrary\Media;

/**
 * Очистка данных каталога перед импортом
 *
 * Class WizardImportClearCommand
 * @package Larrock\ComponentWizard\Commands
 */
class WizardImportClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:clear {--sleep= : sleep process in seconds after 1s} {--silence= : dont show dialogs} {--withoutimage= : dont reload images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear catalog';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $silence = $this->option('silence');

        if($silence > 0){
            $this->process();
        }else{
            if($this->confirm('Clear Catalog?')){
                $this->process();
            }
        }
    }

    public function process()
    {
        $sleep = $this->option('sleep');
        $withoutimage = $this->option('withoutimage');

        \Log::info('Start catalog items deleting');
        $this->info('Start catalog items deleting');

        //Копия метода $adminWizard->deleteCatalog(), здесь добавлен прогресс бар на вывод
        $delete = LarrockCatalog::getModel()->all();
        $bar = $this->output->createProgressBar(count($delete));
        $start = microtime(true);

        if($withoutimage){
            Media::whereModelType(LarrockCatalog::getModelName())->delete();
        }

        foreach($delete as $delete_value){
            if($sleep && $sleep > 0){
                if(microtime(true) - $start > 1){
                    echo 'sleep '. $sleep .' seconds';
                    sleep($sleep);
                    $start = microtime(true);
                }
            }
            //Очищаем связи с фото
            $find_item = LarrockCatalog::getModel()->find($delete_value->id);

            if( !$withoutimage){
                $find_item->clearMediaCollection('images');
            }

            $delete_value->delete();
            if($delete_value->get_category()->count() > 0){
                $delete_value->get_category()->detach($delete_value->category, ['catalog_id' => $delete_value->id]);
            }
            $bar->advance();
        }

        $bar->finish();

        //$clearCatalog = $adminWizard->deleteCatalog();
        \Log::info('Start catalog categories deleting');
        $this->info('Start catalog categories deleting');

        //Копия метода $adminWizard->deleteCategoryCatalog(), здесь добавлен прогресс бар на вывод
        $delete = LarrockCategory::getModel()->whereComponent('catalog')->get();
        $bar = $this->output->createProgressBar(count($delete));

        if($withoutimage){
            Media::whereModelType(LarrockCategory::getModelName())->delete();
        }

        foreach($delete as $delete_value){
            if($sleep && $sleep > 0){
                if(microtime(true) - $start > 1){
                    echo 'sleep '. $sleep .' seconds';
                    sleep($sleep);
                    $start = microtime(true);
                }
            }
            $find_item = LarrockCategory::getModel()->find($delete_value->id);

            if( !$withoutimage){
                $find_item->clearMediaCollection('images');
            }

            $delete_value->delete();
            $bar->advance();
        }

        $bar->finish();

        \Log::info('Catalog removed');
        $this->info('Catalog removed');
    }
}
