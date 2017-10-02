<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentWizard\Helpers\AdminWizard;
use Excel;

/**
 * Импорт листа прайса .xlsx
 *
 * Class WizardImportSheetCommand
 * @package Larrock\ComponentWizard\Commands
 */
class WizardImportSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:sheet {--sheet= : ID sheet .xlsx} {--sleep= : sleep process in seconds after 1s}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sheet .xlsx to catalog';

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
        $sheet = (int)$this->option('sheet');
        $adminWizard = new AdminWizard();
        $data = \Cache::remember('ImportSheet'. $sheet, 1440, function() use ($sheet, $adminWizard){
            return \Excel::selectSheetsByIndex($sheet)->load($adminWizard->findXLSX(), function($reader) {})->get();
        });

        $bar = $this->output->createProgressBar(count($data));
        $adminWizard->artisanSheetImport($sheet, $bar, $data, $this->option('sleep'));
        $bar->finish();
        $this->info('Sheet #'. $sheet .' successful imported.');
    }
}
