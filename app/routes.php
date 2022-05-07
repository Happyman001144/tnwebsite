<?php
use \App\Middleware\AuthMiddleware;
use \App\Middleware\GuestMiddleware;
use \App\Middleware\CanBanMiddleware;
use \App\Middleware\AdminMiddleware;
use \App\Middleware\ServerTokenMiddleware;

$app->get('/lang', 'PagesController:lang');
$app->get('/modules/{modulename}/{folder}/{file}', 'PagesController:moduleAsset');
$app->get('/dependencycheck', 'MigrationController:dependencycheck')->setName('dependencycheck');
$app->get('/migrate', 'MigrationController:migrate')->setName('migrate');

$app->get('/', 'PagesController:landing')->setName('landing');
$app->get('/tos', 'PagesController:tos')->setName('tos');
$app->get('/privacy', 'PagesController:privacy')->setName('privacy');
$app->get('/impressum', 'PagesController:impressum')->setName('impressum');
$app->get('/bans', 'PagesController:bans')->setName('bans');
$app->get('/users', 'PagesController:users')->setName('users');
$app->get('/loading/{id}', 'PagesController:loading')->setName('loading');
$app->get('/error/{code}', 'PagesController:error')->setName('error');

$app->get('/store/credits', 'StoreController:credits')->setName('store');
$app->get('/store/servers', 'StoreController:servers')->setName('store_servers');
$app->get('/store/packages/{serverid}', 'StoreController:packages')->setName('store_packages');
$app->post('/store/packages/purchase/{id}', 'StoreController:purchasePackage');
$app->post('/store/ipn', 'StoreController:ipn')->setName('store_ipn');

$app->get('/profile/{steamid}', 'PagesController:profile')->setName('profile');
$app->get('/auth', 'AuthController:authenticate')->add(new GuestMiddleware($container))->setName('auth');
$app->post('/auth', 'AuthController:logout')->add(new AuthMiddleware($container))->setName('logout');

$app->get('/notifications[/page/{page}]', 'PagesController:notifications')->add(new AuthMiddleware($container))->setName('notifications');

$app->group('/admin/', function() {
    $this->get('dashboard', 'AdminController:dashboard')->setName('admin_dashboard');
    $this->get('misc', 'AdminController:misc')->setName('admin_misc');
    $this->get('users', 'PagesController:users')->setName('admin_users');
    $this->get('servers', 'PagesController:adminServers')->setName('admin_servers');
    $this->get('features', 'PagesController:adminFeatures')->setName('admin_features');
    $this->get('groups', 'PagesController:adminGroups')->setName('admin_groups');
    $this->get('links', 'PagesController:adminLinks')->setName('admin_links');
    $this->get('packages', 'PagesController:adminPackagesServerPicker')->setName('admin_packages');
    $this->get('packages/{server}', 'PagesController:adminPackages');
    $this->get('team', 'AdminController:team')->setName('admin_team');
    $this->get('loadingscreens', 'AdminController:loadingScreens')->setName('admin_loading_screens');

    $this->post('save', 'AdminController:save')->setName('admin_save');
    $this->post('card/save', 'AdminController:cardSave')->setName('admin_card_save');
    $this->delete('card/save', 'AdminController:cardSave')->setName('admin_card_save');
})->add(new AdminMiddleware($container));


$app->group('/api/', function() use ($container) {
    $this->post('users/changegroup', 'AdminController:changeGroup');
    $this->post('users/updatecredits', 'AdminController:updateCredits');
    $this->get('store/packagepurchases', 'StoreController:packagePurchases');
    $this->get('store/transactions', 'StoreController:transactions');
    $this->get('store/transactionlogs', 'StoreController:transactionLogs');
    $this->delete('cache', 'ApiController:deleteCache');
})->add(new AdminMiddleware($container));

$app->group('/api/', function() use ($container) {
    $this->post('bans/new', 'ApiController:ban')->setName('api_bans_new');
    $this->post('bans/pardon', 'ApiController:pardonBan');
    $this->post('bans/remove', 'ApiController:removeBan');
})->add(new CanBanMiddleware($container));

$app->group('/api/', function() use ($container) {
    $this->get('users', 'ApiController:users')->setName('api_users');
    $this->get('servers', 'ApiController:servers')->setName('api_servers');
    $this->get('bans', 'ApiController:bans')->setName('api_bans');
    $this->post('notificationread', 'ApiController:notificationread')->setName('api_notificationread');
});

$app->group('/api/server/{token}/', function() {
    $this->get('connectioncheck', 'ServerApiController:getConnectionCheck');
    $this->get('users/{steamid}/details', 'ServerApiController:getUserDetails');
    $this->post('users/{steamid}/bans', 'ServerApiController:postUserBan');
    $this->post('users/{steamid}/group', 'ServerApiController:postUserGroup');
})->add(new ServerTokenMiddleware($container));
