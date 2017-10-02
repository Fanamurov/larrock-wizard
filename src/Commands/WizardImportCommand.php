<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentWizard\Helpers\AdminWizard;
use Excel;

/**
 * Запуск импорта каталога
 *
 * Class WizardImportCommand
 * @package Larrock\ComponentWizard\Commands
 */
class WizardImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:import {--sleep= : sleep process in seconds after 1s}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import .xlsx file to catalog';

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
        $sleep = $this->option('sleep');
        $options = [];
        if($sleep && $sleep > 0){
            $options = ['--sleep' => $sleep];
        }

        $this->call('wizard:clear', $options);

        if ($this->confirm('Start Import?')) {
            $this->call('cache:clear');
            $adminWizard = new AdminWizard();
            $data = Excel::load($adminWizard->findXLSX(), function($reader) {
                $reader->takeRows(1);
            })->get();

            foreach ($data as $key => $sheet){
                $this->line('Start import '. $adminWizard->findXLSX() .' sheet #'. $key);
                $options = ['--sheet' => $key];
                if($sleep && $sleep > 0){
                    $options = ['--sheet' => $key, '--sleep' => $sleep];
                }
                $this->call('wizard:sheet', $options);
            }
            $this->info('SUCCESS! Import ended');
            $this->call('cache:clear');
        }
    }
}
