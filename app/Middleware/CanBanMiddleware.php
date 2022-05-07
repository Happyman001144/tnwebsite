<?php
namespace App\Middleware;

class CanBanMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if (!$this->container->auth->canBan()) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
