<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentWizard\Helpers\AdminWizard;

/**
 * Импорт листа прайса .xlsx.
 *
 * Class WizardImportSheetCommand
 */
class WizardImportSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:sheet {--sheet= : ID sheet .xlsx} {--sleep= : sleep process in seconds after 1s} {--silence= : dont show dialogs} {--withoutimage= : dont reload images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sheet .xlsx to catalog';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $sheet = (int) $this->option('sheet');
        $adminWizard = new AdminWizard();
        $data = \Cache::rememberForever('ImportSheet'.$sheet, function () use ($sheet, $adminWizard) {
            return \Excel::selectSheetsByIndex($sheet)->load($adminWizard->findXLSX(), function ($reader) {
            })->get();
        });

        $bar = $this->output->createProgressBar(\count($data));
        $adminWizard->artisanSheetImport($sheet, $bar, $data, $this->option('sleep'), $this->option('withoutimage'));
        $bar->finish();
        \Log::info('Sheet #'.$sheet.' successful imported.');
        $this->info('Sheet #'.$sheet.' successful imported.');
    }
}
