<?php
namespace App\Middleware;

class AdminMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if (!$this->container->auth->isAdmin()) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
