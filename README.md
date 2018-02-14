# Easyroute
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/iassasin/easyroute.svg?branch=master)](https://travis-ci.org/iassasin/easyroute)
[![Coverage Status](https://coveralls.io/repos/github/iassasin/easyroute/badge.svg?branch=master)](https://coveralls.io/github/iassasin/easyroute?branch=master)

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
	new Route('/(:controller:(/:action:(/:arg)?)?)?', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
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
		return '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
		// or use "return new Response('...')" which is more flexible
	}
}
```

That's it!

Note that file name must match `:controller` name from URL template and controller class name must match `:controller` name with prefix `Controller`. In example above route `/home/index` matches file `controllers/home.php` and class `ControllerHome`.

## Useful features

### Route with parameters

Route is a regex string with simpler syntax for naming route arguments. For example:

- `/:controller/:action/:arg` (all parameters are required)
- `/(foo|bar)-:arg/gotcha` (regex and partial parameter matching. Valid are `/foo-123/gotcha`, `/bar-qwerty/gotcha`, but not `/qwerty/gotcha`)
- `/:arg?` (parameter `arg` can be ommited, but not `/`. Valid are `/`, `/param`, but not `/sub/param`)
- `/(:controller:(/:action:(/:arg)?)?)?` (all parameters not required. Valid are `/`, `/home`, `/home/index/val`, but not `/home/`, `/home/index/`)

Parameter name must match regex `[a-zA-Z_0-9]+`.  
By default `:parameter` match regex `[^\/]+`, but you can use your own filter:

```php
new Route('/:arg(\d+)?', // arg not required and can contains only numerics
	['controller' => 'home', 'action' => 'index', 'arg' => null], // default values
)
```

If you need to use `(` to match group, but not to filter parameter, use trailing `:` in parameter name: `/:controller:(postfix1|postfix2)` will match `/homepostfix1` with `controller = 'home'`.

### Built-in simple dependency injection container

You can use built-in dependency injection container to split app code into services (which will be loaded via autoload) and simply use it in controller's actions:

```php
class ControllerHome {
	public function index($arg, Request $request, DatabaseService $db){
		// ...
	}
}
```

Services constructors can use dependency injection too and require another services via same syntax.

Class `SampleContainer` impletents `Psr\Container\ContainerInterface`.

```php
use Iassasin\Easyroute\Router;

$router = new Router();
/** @var SimpleContainer $container */
$container = $router->getContainer(); // get default container

// Instantiate and add services by hand
$container->setService(Database::class, new Database('login', 'password'));
// Disable automatic instantiation for services
$container->setAutowireEnabled(false);
```

Also you can use another dependency injection container:

```php
use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Http\Request;

$router = new Router();
$request = Request::createFromGlobals();

// some implementation of ContainerInterface
$container = new MyContainer();
// Router don't know how to register Request class in your container implementation
// You should to do it by self if you want to use Request class in controllers
// See next section for details of Request class
$container->register(Request::class, $request);

$router->setContainer($container);
```

### Request class

Using dependency injection container you have access to `Request` service that provides access to request parameters.

`Request` has fields listed below:

- `query` - `$_GET` array
- `request` - `$_POST` array
- `attributes` - assoc array with any data, which you can set at `Request` instance creation
- `cookies` - `$_COOKIE` array
- `files` - `$_FILES` array
- `server` - `$_SERVER` array

Each field is instance of `Parameters` class and has methods:

- `get(string $name)` - get parameter by it's name
- `has(string $name)` - does parameter with `$name` exists
- `all()` - get all parameters in array

`Request` provide some useful methods:

- `getContent()` - get whole http post content as string
- `getClientIP()` - returns `$this->server->get('REMOTE_ADDR')`
- `getScriptName()` - returns `$this->server->get('SCRIPT_NAME')`
- `getScheme()` - returns `$this->server->get('REQUEST_SCHEME')`
- `getHost()` - returns `$this->server->get('SERVER_NAME')`
- `getUri()` - returns `$this->server->get('REQUEST_URI')`
- `getMethod()` - returns `$this->server->get('REQUEST_METHOD')`
- `getProtocol()` - returns `$this->server->get('SERVER_PROTOCOL')`

### Response class

`Response` class used to return data to client. Return `Response` instances from controller's actions instead of manual data send using echo or etc.

#### Built-in response classes

There is 3 built-in response classes:

- `Response` - base class for all responses, has methods:
  - `__construct(string $content, int $statusCode = 200, array $headers = [])`
  - `getStatusCode()`, `setStatusCode(int $code)` - get/set http status code
  - `getContent()`, `setContent(string $content)` - get/set http response content
  - `getHeaders()`, `setHeaders(array $headers)` - get/set http headers
  - `setHeader(string $name, string $value)` - set single header
  - `send()` - send response to client, called from handlers
- `Response404` - extends `Request` for default http 404 error with:
  - `__construct(string $url, array $headers = [])`
  - `getUrl()` - get URL caused 404 error
- `Response500` - extends `Request` for default http 500 error. Generated if any exception thrown during route processing.
  - `__construct(\Throwable $exception = null, array $headers = [])`
  - `getException()` - get exception thrown during route processing
- `ResponseJson` - extends `Request` for sending json responses, also set correct http `Content-Type` header.
  - `__construct(object|array $data, int $statusCode = 200, array $headers = [])`
  - `getData()`, `setData(object|array $data)` - set source data to send to client
  - Note that `setContent` can't be used and throws `LogicException`

#### Custom handlers for response classes

Set custom handlers for `Response` classes:

```php
use Iassasin\Easyroute\Http\Responses\Response404;

$router->setResponseHandler(Response404::class, function(Response404 $resp){
	$resp->setContent('<html><body><h1>Custom 404 Not Found</h1> The requested url "<i>'.htmlspecialchars($resp->getUrl()).'</i>" not found!');
	$resp->send();
	return true; // do not call other handlers
});
```

And custom handlers for any http status code:

```php
use Iassasin\Easyroute\Http\Response;

$router->setStatusHandler(302, function(Response $resp){
	$resp->setContent('You have redirected');
	$resp->send();
	return true; // do not call other handlers
});
```

> Note 1: you can use both handlers set, but response handlers will be called first (and can stop calling status handlers).
>
> Note 2: you can set handler for parent response class to match all childs, child handlers will be always called first: from most child to first parent. So, you can match **all** responses setting handler for `Response::class`.

### Subdirectories for controllers

Separate zones with subdirectories:

```php
(new Route('/admin/:controller/:action?', ['action' => 'index']))
	->setControllersSubpath('zones/admin')
	// '/admin/home/index' will match 'controllers/zones/admin/home.php'
	// and class 'ControllerHome'
```

### Filtering access

Use `RouteFilter` to prevent access to some routes:

```php
use Iassasin\Easyroute\RouteFilter;

class RouteFilterAdmin extends RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		if (!isCurrentUserAdmin()){
			(new Response('Access denied!', 403))->send();
			return Router::COMPLETED; // Do not call controller's action
		}

		return Router::CONTINUE; // Call controller's action
	}
}
//...
(new Route('/admin/:controller/:action?', ['action' => 'index']))
	->setFilter(new RouteFilterAdmin())
```

### Custom controller class name prefix

Set custom controller class name prefix (default - `Controller`):

```php
$router->setControllerClassPrefix('TheController');
```
