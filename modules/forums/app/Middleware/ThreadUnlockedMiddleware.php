<?php
namespace Forums\Middleware;
use Forums\Models\ForumThread;
use Forums\Models\ForumPost;

class ThreadUnlockedMiddleware extends \App\Middleware\Middleware
{
    public function __invoke($request, $response, $next)
    {
        $params = $request->getParams();

        if (isset($params['tid'])) { // for reply route
            $thread = ForumThread::find($params['tid']);
        } else { // for react & edit routes
            $thread = ForumPost::find($params['pid'])->thread;
        }

        if ($thread->locked) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
