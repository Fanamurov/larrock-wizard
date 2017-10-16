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
    protected $signature = 'wizard:import {--sleep= : sleep process in seconds after 1s} {--silence= : dont show dialogs} {--withoutimage= : dont reload images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import .xlsx file to catalog';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sleep = $this->option('sleep');
        $silence = $this->option('silence');
        $withoutimage = $this->option('withoutimage');
        $options = [];
        if($sleep && $sleep > 0){
            $options['--sleep'] = $sleep;
        }
        if($silence > 0){
            $options['--silence'] = $silence;
        }
        $options['--withoutimage'] = $withoutimage;

        $this->call('wizard:clear', $options);

        if($silence > 0){
            $this->process($options);
        }else{
            if ($this->confirm('Start Import?')) {
                $this->process($options);
            }
        }
    }

    protected function process($options)
    {
        $this->call('cache:clear');
        $adminWizard = new AdminWizard();
        $data = Excel::load($adminWizard->findXLSX(), function($reader) {
            $reader->takeRows(1);
        })->get();

        foreach ($data as $key => $sheet){
            \Log::info('Start import '. $adminWizard->findXLSX() .' sheet #'. $key);
            $this->line('Start import '. $adminWizard->findXLSX() .' sheet #'. $key);
            $options['--sheet'] = $key;
            $this->call('wizard:sheet', $options);
        }
        \Log::info('SUCCESS! Import ended');
        $this->info('SUCCESS! Import ended');
        $this->call('cache:clear');
    }
}
