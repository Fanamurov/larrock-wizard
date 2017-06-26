<?php

namespace Larrock\ComponentWizard;

use Larrock\Core\Component;

class WizardComponent extends Component
{
    public function __construct()
    {
        $this->name = $this->table = 'wizard';
        $this->title = 'Wizard';
        $this->description = 'Экспорт .xlsx-прайса в каталог сайта';
    }
}