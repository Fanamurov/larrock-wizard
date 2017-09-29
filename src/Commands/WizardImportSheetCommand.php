<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentWizard\Helpers\AdminWizard;

class WizardImportSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:sheet {--sheet= : ID sheet .xlsx}';

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
        $sheet = $this->option('sheet');
        $adminWizard = new AdminWizard();
        $data = \Excel::selectSheetsByIndex($sheet)->load($adminWizard->findXLSX(), function($reader) {})->get();
        $bar = $this->output->createProgressBar(count($data));
        $adminWizard->artisanSheetImport($sheet, $bar);
        $bar->finish();
        $this->info('Sheet #'. $sheet .' successful imported.');
    }
}
