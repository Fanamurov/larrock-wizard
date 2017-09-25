<?php

namespace Larrock\ComponentWizard\Commands;

use Illuminate\Console\Command;
use Larrock\ComponentWizard\Helpers\AdminWizard;
use Excel;

class WizardImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:import';

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
        $this->call('wizard:clear');

        if ($this->confirm('Start Import?')) {
            $this->call('cache:clear');
            $adminWizard = new AdminWizard();
            $data = Excel::load($adminWizard->findXLSX(), function($reader) {
                $reader->takeRows(1);
            })->get();

            foreach ($data as $key => $sheet){
                $this->line('Start import '. $adminWizard->findXLSX() .' sheet #'. $key);
                $this->call('wizard:sheet', ['--sheet' => $key]);
            }
            $this->info('SUCCESS! Import ended');
            $this->call('cache:clear');
        }
    }
}
