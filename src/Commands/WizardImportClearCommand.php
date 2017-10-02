<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentCatalog\Models\Catalog;
use Larrock\ComponentCategory\Models\Category;
use Larrock\ComponentWizard\Helpers\AdminWizard;

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
    protected $signature = 'wizard:clear {--sleep= : sleep process in seconds after 1s}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear catalog';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if($this->confirm('Clear Catalog?')){
            $sleep = $this->option('sleep');
            $adminWizard = new AdminWizard();
            $this->info('Start catalog items deleting');

            //Копия метода $adminWizard->deleteCatalog(), здесь добавлен прогресс бар на вывод
            $delete = Catalog::all();
            $bar = $this->output->createProgressBar(count($delete));

            foreach($delete as $delete_value){
                if($sleep && $sleep > 0){
                    if(microtime(true) - $start > 1){
                        echo 'sleep '. $sleep .' seconds';
                        sleep($sleep);
                        $start = microtime(true);
                    }
                }
                //Очищаем связи с фото
                $find_item = Catalog::find($delete_value->id);
                $find_item->clearMediaCollection();
                $delete_value->delete();
                if($delete_value->get_category()->count() > 0){
                    $delete_value->get_category()->detach($delete_value->category, ['catalog_id' => $delete_value->id]);
                }
                $bar->advance();
            }

            $bar->finish();

            //$clearCatalog = $adminWizard->deleteCatalog();
            $this->info('Start catalog categories deleting');

            //Копия метода $adminWizard->deleteCategoryCatalog(), здесь добавлен прогресс бар на вывод
            $delete = Category::whereComponent('catalog')->get();
            $bar = $this->output->createProgressBar(count($delete));

            foreach($delete as $delete_value){
                if($sleep && $sleep > 0){
                    if(microtime(true) - $start > 1){
                        echo 'sleep '. $sleep .' seconds';
                        sleep($sleep);
                        $start = microtime(true);
                    }
                }
                $find_item = Category::find($delete_value->id);
                $find_item->clearMediaCollection();
                $delete_value->delete();
                $bar->advance();
            }

            $bar->finish();

            //$clearCategory = $adminWizard->deleteCategoryCatalog();
            $this->info('Catalog removed');
        }
    }
}
