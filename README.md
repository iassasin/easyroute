Простой маршрутизатор, не требующий кучи настроек, устанавливается за 5 минут и просто работает.

Для использования нужно выполнить всего 4 простых шага:

1. Установить easyroute через composer:

```
composer require iassasin/easyroute
```

2. Настроить маршруты в корневой папке сайта в файле `routes.php`:

```php
require_once 'vendor/autoload.php';
use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;

$router = new Router();
$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/controllers/');
$router->addRoutes([
	new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
]);
$router->processRoute($_SERVER['REQUEST_URI']);
```

3. Настроить перенаправление в `.htaccess` всех запросов на него, кроме статических ресурсов (`assets`):

```
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/assets/
RewriteRule ^(.*)$ routes.php [B,QSA,L]
```

4. Создать контроллер в `controllers/home.php`:

```php
class ControllerHome {
	public function index($arg){
		echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
	}
}
```

Все! Приятного использования!

Если требуется более тонкая настройка маршрутов, можно использовать фильтры для аргументов (используются регулярные выражения):

```php
new Route('/{arg}',
	['controller' => 'home', 'action' => 'index'],
	['arg' => '/^\d+$/'] //в arg могут быть только цифры
)
```

Подпапки для отдельных зон:

```php
(new Route('/admin/{controller}/{action}', ['action' => 'index']))
	->setControllersSubpath('zones/admin')
```

Фильтр доступа к маршруту:

```php
use Iassasin\Easyroute\RouteFilter;

class RouteFilterAdmin extends RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		if (!isCurrentUserAdmin()){
			echo 'Access denied!';
			return Router::COMPLETED; //Не вызывать контроллер, маршрут уже обработан
		}

		return Router::CONTINUE; //Вызвать контроллер
	}
}
//...
(new Route('/admin/{controller}/{action}', ['action' => 'index']))
	->setFilter(new RouteFilterAdmin())
```

Свой обработчик ошибки 404:
```php
$router->setHandler404(function($path){
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	echo '<html><body><h1>404 Not Found</h1> The requested url "<i>'.htmlspecialchars($path).'</i>" not found!';
});
```

А также любой префикс имени класса контроллера (стандартный - `Controller`):
```php
$router->setControllerClassPrefix('TheController');
```
