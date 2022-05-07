<?php
namespace Forums\Middleware;
use Forums\Models\ForumBoardPermission;
use Forums\Models\ForumThread;

class CanViewMiddleware extends \App\Middleware\Middleware
{
    public function __invoke($request, $response, $next)
    {
        $args = $request->getAttribute('routeInfo')[2];

        if (isset($args['tid'])) { // for thread view
            $bid = ForumThread::where('tid',$args['tid'])->first()->bid;
        } else { // for board & create views
            $bid = $args['bid'];
        }

        if ($this->container->auth->check()) {
          $gid = $this->container->auth->user()->gid_extended;
        } else {
          $gid = 0;
        }

        $perms = ForumBoardPermission::where('bid',$bid)->where('gid',$gid)->first();

        if (!empty($perms) && $perms->cannot_view === 1) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
