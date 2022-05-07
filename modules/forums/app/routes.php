<?php
use \App\Middleware\AuthMiddleware;
use \App\Middleware\AdminMiddleware;
use \Forums\Middleware\CanViewMiddleware;
use \Forums\Middleware\CanModerateMiddleware;
use \Forums\Middleware\ThreadUnlockedMiddleware;

$app->group('/admin/forums', function() {
    $this->get('', 'ForumsPageController:admin')->setName('admin_forums');
    $this->post('/category', 'ForumsAdminController:postCategory');
    $this->delete('/category', 'ForumsAdminController:deleteCategory');
    $this->post('/board', 'ForumsAdminController:postBoard');
    $this->delete('/board', 'ForumsAdminController:deleteBoard');
    $this->post('/updateboardpositions', 'ForumsAdminController:postUpdateBoardPositions');
    $this->post('/permission', 'ForumsAdminController:postPermission');
})->add(new AdminMiddleware($container));

$app->group('/forums', function() use ($container) {
    $this->get('', 'ForumsPageController:spa')->setName('forums');
    $this->get('/search', 'ForumsPageController:spa');
    $this->get('/posts/{pid}', 'ForumsPageController:post')->setName('post');
    $this->get('/boards/{bid}[/page/{page}]', 'ForumsPageController:spa')->add(new CanViewMiddleware($container))->setName('board');
    $this->get('/create/{bid}', 'ForumsPageController:spa')->add(new CanViewMiddleware($container))->setName('create');
    $this->get('/threads/{tid}[/page/{page}]', 'ForumsPageController:spa')->add(new CanViewMiddleware($container))->setName('thread');

    $this->group('/api', function() use ($container) {
        $this->get('/index', 'ForumsApiController:getIndex');
        $this->get('/search', 'ForumsApiController:getSearch');
        $this->get('/posts/{pid}', 'ForumsApiController:getPost');
        $this->get('/boards/{bid}[/page/{page}]', 'ForumsApiController:getBoard')->add(new CanViewMiddleware($container));
        $this->get('/threads/{tid}[/page/{page}]', 'ForumsApiController:getThread')->add(new CanViewMiddleware($container));
    });
});

$app->group('/forums', function() use ($container) {
    $this->post('/create', 'ForumsApiController:create');
    $this->post('/reply', 'ForumsApiController:reply')->add(new ThreadUnlockedMiddleware($container));
    $this->post('/edit', 'ForumsApiController:edit')->add(new ThreadUnlockedMiddleware($container));
    $this->delete('/delete', 'ForumsApiController:delete');
    $this->post('/mentionAutoComplete', 'ForumsApiController:mentionAutoComplete');
    $this->post('/react', 'ForumsApiController:react')->add(new ThreadUnlockedMiddleware($container));
    $this->post('/toggleThreadState', 'ForumsApiController:toggleThreadState')->add(new CanModerateMiddleware($container));
    $this->post('/moveThread', 'ForumsApiController:moveThread')->add(new CanModerateMiddleware($container));
})->add(new AuthMiddleware($container));
