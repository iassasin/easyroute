Простой маршрутизатор, не требующий кучи настроек, устанавливается за 5 минут и просто работает.

Для использования нужно выполнить всего 3 простых шага:

1. Скачать ядро маршрутизатора: `router.php`, и настроить перенаправление в `.htaccess` всех запросов на него, кроме статических ресурсов (`assets`):

```
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/assets/
RewriteRule ^(.*)$ routes.php?url=$1 [B,QSA,L]
```

2. Настроить маршруты:

```php
require_once 'router.php';

$router = new Router();
$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/controllers/');
$router->addRoutes([
	new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
]);
$router->processRoute($_GET['url']);
```

3. Создать контроллер в `controllers/home.php`:

```php
class ControllerHome {
	public function index($arg){
		echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
	}
}
```

Все! Пользуйтесь на здоровье!

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
