<?php

$container['ForumsPageController'] = function($container) {
    return new \Forums\Controllers\ForumsPageController($container);
};

$container['ForumsApiController'] = function($container) {
    return new \Forums\Controllers\ForumsApiController($container);
};

$container['ForumsAdminController'] = function($container) {
    return new \Forums\Controllers\ForumsAdminController($container);
};

$container['view']->getEnvironment()->addGlobal('forums', true);

$container['dispatcher']->listen(App\Events\UserSaving::class, Forums\Listeners\UserSaving::class);

require __DIR__ . '/../app/routes.php';
