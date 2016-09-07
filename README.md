Простой маршрутизатор, не требующий кучи настроек, устанавливается за 5 минут и просто работает.

Для использования нужно выполнить всего 3 простых шага:

1. Скачать ядро маршрутизатора: `router.php`
2. Настроить маршруты:

		require_once 'router.php';

		$router = new Router();
		$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/controllers/');
		$router->addRoutes([
			new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
		]);
		$router->processRoute($_GET['url']);

3. Создать контроллер в `controllers/home.php`:

		class ControllerHome {
			public function index($arg){
				echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
			}
		}

Все! Пользуйтесь на здоровье!

Если требуется более тонкая настройка маршрутов, можно использовать фильтры для аргументов (используются регулярные выражения):

		new Route('/{arg}',
			['controller' => 'home', 'action' => 'index'],
			['arg' => '/^\d+$/'] //в arg могут быть только цифры
		)

Свой обработчик ошибки 404:

		$router->setHandler404(function($path){
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
			echo '<html><body><h1>404 Not Found</h1> The requested url "<i>'.htmlspecialchars($path).'</i>" not found!';
		});

А также любой префикс имени класса контроллера:

		$router->setControllerClassPrefix('TheController');
		

