# Laravel Larrock Wizard component

---
*Import .xlsx price to catalog component for larrockCMS*

#### Depends:
  - fanamurov/larrock-core
  - fanamurov/larrock-catalog
  - fanamurov/larrock-category
  - maatwebsite/excel

## INSTALL

1.Install larrock-core, larrock-catalog, larrock-category

2.Add service providers (config/app.php)
```php
/* http://www.maatwebsite.nl/laravel-excel/docs/import */
Maatwebsite\Excel\ExcelServiceProvider::class,
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
http://yousite/admin/wizard