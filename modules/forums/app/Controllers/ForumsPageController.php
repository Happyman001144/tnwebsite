<?php
namespace Forums\Controllers;

use App\Controllers\Controller;
use \Slim\Views\Twig as View;
use App\Models\Group;
use Forums\Models\ForumCategory;
use Forums\Models\ForumBoard;
use Forums\Models\ForumPost;

class ForumsPageController extends Controller
{
    public function admin($request, $response) {
        $groupsExtended = Group::getExtended();
        $this->view->getEnvironment()->addGlobal('groups', $groupsExtended);
        $categories = ForumCategory::orderBy('cid','asc')->with('boards')->get();
        $this->view->getEnvironment()->addGlobal('categories', $categories->toArray());

        $basePermissions = array();
        foreach ($groupsExtended as $group) {
            $basePermissions[$group['gid']] = ['cannot_view'=>null, 'cannot_post_thread'=>null, 'cannot_post_reply'=>null, 'cannot_react'=>null];
        }
        $this->view->getEnvironment()->addGlobal('basePermissions', $basePermissions);

        return $this->view->render($response, 'admin/forums.twig');
    }

    public function spa($request, $response) {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../public/mix-manifest.json'), true);
        $this->view->getEnvironment()->addGlobal('forumsspajsmix', '/modules/forums'.str_replace('.js','',$manifest['/js/forumsspa.js']));
        $this->view->getEnvironment()->addGlobal('boards', ForumBoard::get());
        return $this->view->render($response, 'forumsspa.twig');
    }

    public function post($request, $response, $args) {
        $perPage = 15;
        $post = ForumPost::find($args['pid']);
        if ($post == null) {
            return $response->withStatus(404);
        }
        $thread = $post->thread;

        $postIndex = 1;
        foreach ($thread->posts as $post) {
            if ($args['pid'] == $post->pid) {
                $page = ceil($postIndex/$perPage);
                break;
            }
            $postIndex++;
        }

        return $response->withRedirect($this->router->pathFor('thread', [
            'tid' => $thread->tid,
            'page' => $page
        ]).'#post-'.$args['pid']);
    }
}
