<?php
require __DIR__ . '/../config.php';

ini_set('session.gc_maxlifetime', $settings['session_lifetime']);
ini_set('session.cookie_httponly', 1);
session_start();
setcookie(session_name(),session_id(),time()+$settings['session_lifetime'],'/');

$loader = require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__.'/../app/ember.lock')) { $settings['development_mode'] = true; }
if ($settings['development_mode']) {
    ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
    Tracy\Debugger::enable(Tracy\Debugger::DEVELOPMENT, __DIR__ . '/../logs');
} else {
    ini_set('display_errors', 0); ini_set('display_startup_errors', 0);
}

$app = new \Slim\App([
    'settings' => array_merge_recursive([
        'displayErrorDetails' => $settings['development_mode'],
        'determineRouteBeforeAppMiddleware' => true,
        'db' => [
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'timezone'  => '+00:00',
            'prefix' => ''
        ],
        'cache' => [
            'cache.stores.array' => [
                'driver' => 'array'
            ],
            'cache.stores.file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../cache'
            ],
            'cache.stores.redis' => [
                'driver' => 'redis',
                'connection' => 'default'
            ],
            'cache.stores.memcached' => [
                'driver' => 'memcached'
            ],
            'cache.prefix' => 'illuminate_non_laravel'
        ],
        'addContentLengthHeader' => !$settings['development_mode'],
        'tracy' => [
            'showPhpInfoPanel' => 0,
            'showSlimRouterPanel' => 0,
            'showSlimEnvironmentPanel' => 0,
            'showSlimRequestPanel' => 1,
            'showSlimResponsePanel' => 1,
            'showSlimContainer' => 0,
            'showEloquentORMPanel' => 1,
            'showTwigPanel' => 1,
            'showIdiormPanel' => 0,
            'showDoctrinePanel' => 0,
            'showProfilerPanel' => 0,
            'showVendorVersionsPanel' => 1,
            'showXDebugHelper' => 0,
            'showIncludedFiles' => 0,
            'showConsolePanel' => 0,
            'configs' => [
                'ConsoleNoLogin' => 0,
                'ProfilerPanel' => [
                    'show' => [
                        'memoryUsageChart' => 1, // or false
                        'shortProfiles' => true, // or false
                        'timeLines' => true // or false
                    ]
                ]
            ]
        ]
    ],$settings)
]);

$container = $app->getContainer();

if ($settings['development_mode']) {
    $app->add(new RunTracy\Middlewares\TracyMiddleware($app));
    $container['twig_profile'] = function () {
        return new Twig_Profiler_Profile();
    };
}

$ic = new \Illuminate\Container\Container;

$dispatcher = new \Illuminate\Events\Dispatcher($ic);
$container['dispatcher'] = function($c) use ($dispatcher) {
    return $dispatcher;
};

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setEventDispatcher($container['dispatcher']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
if ($settings['development_mode']) {
    $capsule::connection()->enableQueryLog();
}
$container['db'] = function($c) use($capsule) {
    return $capsule;
};

$ic['config'] = $container->get('settings')['cache'];
$ic['files'] = new \Illuminate\Filesystem\Filesystem;
$ic['redis'] = new \Illuminate\Redis\RedisManager(null, 'predis', $ic['config']['database.redis']);
$ic->bind('memcached.connector', 'Illuminate\Cache\MemcachedConnector');
$cacheManager = new \Illuminate\Cache\CacheManager($ic);
$cache = $cacheManager->driver();

$container['cache'] = function ($c) use ($cache) {
    return $cache;
};

function app($str) {
  global $app;
  if ($str == 'container') {
    return $app->getContainer();
  } if ($str == 'cache') {
    return $app->getContainer()['cache'];
  } else if ($str == 'db') {
    return $app->getContainer()['db']->getConnection();
  } else if ($str == 'request') {
    class fakeObj { function input() { return null; } }
    return new fakeObj;
  }
}
function config($str) {
  if ($str == 'laravel-model-caching.disabled') {
    return false;
  }
}

$container['auth'] = function() {
    return new \App\Auth\Auth;
};

$container['view'] = function($container) use ($settings) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => $settings['development_mode'] ? false: __DIR__ . '/../cache/twig',
    ]);

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container->router,
        $container->request->getUri()
    ));

    if ($settings['development_mode']) {
        $view->addExtension(new Twig_Extension_Profiler($container['twig_profile']));
        $view->addExtension(new Twig_Extension_Debug());
    }

    $view->getEnvironment()->addGlobal('auth', $container->auth);

    if (!file_exists(__DIR__.'/../app/ember.lock')) {
        $view->getEnvironment()->addGlobal('setting', App\Models\Setting::allWithBG()->toArray());
        $view->getEnvironment()->addGlobal('navbarlink', App\Models\NavbarLink::orderBy('order','asc')->get());
        $view->getEnvironment()->addGlobal('session', $_SESSION);
    }

    $mixFilter = new Twig_Filter('mix', function ($string) {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../public/mix-manifest.json'), true);
        return($manifest["/{$string}"]);
    });
    $view->getEnvironment()->addFilter($mixFilter);

    $view->getEnvironment()->addGlobal('script_version_hash', '0863415d08f911a8c9419969a6733e99');

    $lang = [];
    include __DIR__ . '/../resources/lang/en.php';
    if (file_exists(__DIR__ . '/../resources/lang/override.php')) {
        include __DIR__ . '/../resources/lang/override.php';
    }
    $container->lang = $lang;
    $langFilter = new Twig_Filter('lang', function ($string, $s='') use ($lang) {
        if (!empty($lang['override'][$string])) {
            $str = $lang['override'][$string];
        } else if (!empty($lang['en'][$string])) {
            $str = $lang['en'][$string];
        } else {
            $str = str_replace('_', ' ', ucfirst($string));
        }
        return str_replace('%s',$s,$str);
    });
    $view->getEnvironment()->addFilter($langFilter);

    return $view;
};

$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        return $container['view']->render($response->withStatus(404), 'error.twig', ['errorCode' => 404]);
    };
};
if (!$settings['development_mode']) {
    $container['errorHandler'] = function ($container) {
        return function ($request, $response, $exception) use ($container) {
            return $container['view']->render($response->withStatus(500), 'error.twig', ['errorCode' => 500]);
        };
    };
    $container['phpErrorHandler'] = function ($container) {
        return $container['errorHandler'];
    };
}

$controllers = ['AuthController','PagesController','AdminController','StoreController','ApiController','ServerApiController','MigrationController'];
foreach ($controllers as $c) {
    $cc = "\\App\\Controllers\\{$c}";
    $container[$c] = function($container) use ($cc) {
        return new $cc($container);
    };
}

$app->add(new \App\Middleware\MigrationMiddleware($container));
$app->add(new \App\Middleware\TrailingSlashMiddleware($container));
$app->add(new \App\Middleware\LastOnlineMiddleware($container));

require __DIR__ . '/../app/routes.php';

// Load additional modules
$modules = array_filter(glob(__DIR__.'/../modules/*/app'), 'is_dir');
foreach ($modules as $module) {
    preg_match('/modules\/(.*)\/app/', $module, $matches, PREG_OFFSET_CAPTURE);
    $moduleName = $matches[1][0];
    $loader->setPsr4(ucfirst($moduleName)."\\", $module);
    $container['view']->getLoader()->addPath($module .'/../resources/views');
    include __DIR__ . '/../modules/'.$moduleName.'/bootstrap/app.php';
}
