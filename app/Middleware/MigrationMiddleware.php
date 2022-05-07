<?php
namespace App\Middleware;

use App\Controllers\MigrationController;

class MigrationMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if (file_exists(__DIR__.'/../ember.lock') && $request->getAttribute('route')->getName() != 'dependencycheck' && $request->getAttribute('route')->getName() != 'migrate') {
            return $response->withRedirect($this->container->router->pathFor('dependencycheck'));
        }

        return $next($request, $response);
    }
}
