<?php
namespace Forums\Middleware;

class CanModerateMiddleware extends \App\Middleware\Middleware
{
    public function __invoke($request, $response, $next)
    {
        if (!$this->container->auth->canModerateForums()) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
