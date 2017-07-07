# Laravel Larrock Wizard component

---
*Import .xlsx price to catalog component for larrockCMS*

#### Depends:
  - fanamurov/larrock-core
  - fanamurov/larrock-catalog
  - fanamurov/larrock-category
  - maatwebsite/excel
  - laracasts/generators

## INSTALL

1.Install larrock-core, larrock-catalog, larrock-category

2.Add service providers (config/app.php)
```php
//http://www.maatwebsite.nl/laravel-excel/docs/import
Maatwebsite\Excel\ExcelServiceProvider::class,
//https://github.com/laracasts/Laravel-5-Generators-Extended
\Laracasts\Generators\GeneratorsServiceProvider::class,
```
Add alias servise providers
```php
'Excel'     => Maatwebsite\Excel\Facades\Excel::class,
```

3.Publish vendor files
```sh
$ php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```


##START
Load .xlsx file, сonfigure import and import
http://yousite/admin/wizard

##ARTISAN COMMANDS
Start import (clear catalog and import loaded .xlsx)
```sh
$ php artisan wizard:import
```
Clear catalog
```sh
$ php artisan wizard:clear
```
Start import selected sheet
```sh
$ php artisan wizard:sheet --sheet={number sheet}
```

##NOTES

 - The file for import must be only one
 - The file must be in the directory '/resources/wizard'

##WORK WITH LARAVEL 5.4
https://github.com/laracasts/Laravel-5-Generators-Extended/issues/117
fix: https://github.com/laracasts/Laravel-5-Generators-Extended/pull/120/files