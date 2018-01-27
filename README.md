# Easyroute
Simple router do not require a lot of setups, install in few minutes and just works.

## Installation

To start using router complete 4 simple steps:

1. Install `easyroute` via composer:

```
composer require iassasin/easyroute
```

2. Setup yours routes in root directory of project. For example in file `routes.php`:

```php
require_once 'vendor/autoload.php';
use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;

$router = new Router();
$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/controllers/');
$router->addRoutes([
	new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
]);
$router->processRoute();
```

3. Tell web server redirect all requests (except static `assets/`) to router via `.htaccess`:

```
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/assets/
RewriteRule ^(.*)$ routes.php [B,QSA,L]
```

4. Create first controller in `controllers/home.php`:

```php
class ControllerHome {
	public function index($arg){
		echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
	}
}
```

That's it!

Note that file name must match `{controller}` name from URL template and controller class name must match `{controller}` name with prefix `Controller`. In example above route `/home/index` matches file `controllers/home.php` and class `ControllerHome`.

## Tuning

To filter matching parameters in URL template you can use regular expressions:

```php
new Route('/{arg}',
	['controller' => 'home', 'action' => 'index'], // default values
	['arg' => '/^\d+$/'] // arg can contains only numerics
)
```

Separate zones with subdirectories:

```php
(new Route('/admin/{controller}/{action}', ['action' => 'index']))
	->setControllersSubpath('zones/admin')
	// '/admin/home/index' will match 'controllers/zones/admin/home.php'
	// and class 'ControllerHome'
```

Use `RouteFilter` to prevent access to some routes:

```php
use Iassasin\Easyroute\RouteFilter;

class RouteFilterAdmin extends RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		if (!isCurrentUserAdmin()){
			echo 'Access denied!';
			return Router::COMPLETED; // Do not call controller's action
		}

		return Router::CONTINUE; // Call controller's action
	}
}
//...
(new Route('/admin/{controller}/{action}', ['action' => 'index']))
	->setFilter(new RouteFilterAdmin())
```

Set handler for 404 error:
```php
$router->setHandler404(function($path){
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	echo '<html><body><h1>404 Not Found</h1> The requested url "<i>'.htmlspecialchars($path).'</i>" not found!';
});
```

Set custom controller class name prefix (default - `Controller`):
```php
$router->setControllerClassPrefix('TheController');
```
