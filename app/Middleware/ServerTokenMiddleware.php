<?php
namespace App\Middleware;

use App\Models\Server;

class ServerTokenMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        $args = $request->getAttribute('routeInfo')[2];
        $server = Server::where('token',$args['token']);
        if (!$server->exists()) {
            return $response->withJSON(['status'=>'unauthenticated'])->withStatus(403);
        } else {
            $request = $request->withAttribute('server', $server->first());
        }

        return $next($request, $response);
    }
}
