Простой маршрутизатор, не требующий кучи настроек, устанавливается за 5 минут и просто работает.

Для использования нужно выполнить всего 3 простых шага:

1. Скачать ядро маршрутизатора: `router.php`
2. Настроить маршруты:

		require_once '../res/dt/router.php';

		$router = new Router();
		$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/routes/controllers/');
		$router->addRoutes([
			new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
		]);
		$router->processRoute($_GET['url']);

3. Создать контроллер в `controllers/home.php`:

		class home {
			public function index($arg){
				echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
			}
		}

Все! Пользуйтесь на здоровье!
