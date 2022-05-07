<?php
namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as DB;

class LastOnlineMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if ($this->container->auth->check()) {
            $this->container->auth->user()->update(['last_online'=>DB::raw('NOW()')]);
        }

        return $next($request, $response);
    }
}
