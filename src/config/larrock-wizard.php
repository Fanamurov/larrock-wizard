<?php

return [
    'rows' => [
        'naimenovanie' => [
            'db' => 'title',
            'slug' => '',
            'template' => 'all',
            'filters' => null,
            'admin' => 'input',
        ],
        'tekhnicheskie_kharakteristiki' => [
            'db' => 'description',
            'slug' => '',
            'template' => 'all',
            'filters' => null,
            'admin' => 'textarea',
        ],
        'proizvoditel' => [
            'db' => 'manufacture',
            'slug' => '',
            'template' => 'all',
            'filters' => 'lilu',
            'admin' => 'select',
        ],
        'tsena' => [
            'db' => 'cost',
            'slug' => '',
            'template' => 'all',
            'filters' => null,
            'admin' => 'input',
        ],
        'ed._izm.' => [
            'db' => 'what',
            'slug' => '',
            'template' => null,
            'filters' => null,
            'admin' => 'select',
        ],
        'foto' => [
            'db' => null,
            'slug' => '',
            'template' => null,
            'filters' => null,
            'admin' => null,
        ],
        'id_opisaniya' => [
            'db' => 'description_link',
            'slug' => '',
            'template' => null,
            'filters' => null,
            'admin' => 'input',
        ]
    ]
];