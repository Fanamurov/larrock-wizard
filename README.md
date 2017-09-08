[![Latest Stable Version](https://poser.pugx.org/fanamurov/larrock-smartbanners/version)](https://packagist.org/packages/fanamurov/larrock-smartbanners) [![Total Downloads](https://poser.pugx.org/fanamurov/larrock-smartbanners/downloads)](https://packagist.org/packages/fanamurov/larrock-smartbanners) [![License](https://poser.pugx.org/fanamurov/larrock-smartbanners/license)](https://packagist.org/packages/fanamurov/larrock-smartbanners)

Компонент используется только внутри студии "Март" в Хабаровске. Выполняет роль клиента баннерообменной сети.

## Установка компонента LarrockSmartbanners
```sh
composer require fanamurov/larrock-smartbanners
```

## Показ баннеров
1. За вывод баннеров отвечает **middleware Smartbanners**. Подключите его в **app/Http/Kernel.php** в секцию $middlewareGroups - web
	```php
	use Larrock\ComponentSmartbanners\Middleware\Smartbaners;
	
	class Kernel extends HttpKernel
	{
	 protected $middlewareGroups = [
	        'web' => [
	            ...
	            Smartbanners::class
	        ],
	        ...
	    ];
	}
	```

2. В **.env**-файле вашего сайта определите значения:
	```
	SMARTBANNERS=(true/false) //активировать ли показы
	SMARTBANNERS_BANNERS= //Сколько баннеров показывать
	SMARTBANNERS_PARTNERS= //Сколько баннеров партнеров показывать
	SMARTBANNERS_HOST= //Хост сайта показывающего баннеры
	SMARTBANNERS_SERVER= //Хост сайта сервера баннерообменки
	```
	Пример:
	```
	SMARTBANNERS=true
	SMARTBANNERS_BANNERS=2
	SMARTBANNERS_PARTNERS=1
	SMARTBANNERS_HOST=martds_ru
	SMARTBANNERS_SERVER=http://martds.ru
	```

3. Вызовите в шаблоне сайта:
	```php
	@if(env('SMARTBANNERS') === true)
	    {!! $smartbanners !!}
	@endif
	```
## Пример принимаемых данных от сервера баннерообменки (json):
```
array (
  0 => 
  array (
    'title' => 'Отличные цены [link_start]Входные двери продажа со склада[link_end]',
    'id' => '2',
    'banner_url' => 'http://site.ru',
    'image' => '/public/images/sbanners/big/sbanners.png',
  ),
  1 => 
  array (
    'title' => '[link_start]Компания "Рога и копыта"[link_end]',
    'id' => '16',
    'banner_url' => 'http://site2.ru',
    'image' => '/public/images/sbanners/big/sbanners_2.png',
  ),
)
```