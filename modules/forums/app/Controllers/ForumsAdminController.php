<?php
namespace Forums\Controllers;

use App\Controllers\Controller;
use Forums\Models\ForumCategory;
use Forums\Models\ForumBoard;
use Forums\Models\ForumBoardPermission;
use Forums\Models\ForumThread;

class ForumsAdminController extends Controller
{
    public function postCategory($request, $response, $args) {
        $params = $request->getParams();
        ForumCategory::updateOrCreate(['cid'=>$params['cid']],['name'=>$params['name']])->flushCache();
        return $response->withJSON(['status'=>'success']);
    }
    public function deleteCategory($request, $response, $args) {
        $params = $request->getParams();
        $category = ForumCategory::where('cid',$params['cid'])->first();
        if ($category->boards->count() > 0) {
            return $response->withJSON(['status'=>'error_category_contains_boards']);
        } else {
            $category->delete();
            return $response->withJSON(['status'=>'success']);
        }
    }

    public function postBoard($request, $response, $args) {
        $params = $request->getParams();
        ForumBoard::updateOrCreate(
          ['bid'=>$params['bid'],'cid'=>$params['cid']],
          [$params['field']=>!empty($params['value']) ? $params['value'] : null]
        )->flushCache();
        return $response->withJSON(['status'=>'success']);
    }
    public function deleteBoard($request, $response, $args) {
        $params = $request->getParams();
        $board = ForumBoard::where('bid',$params['bid'])->first();
        if ($board->threads->count() > 0) {
            return $response->withJSON(['status'=>'error_board_contains_threads']);
        } else {
            $board->delete();
            return $response->withJSON(['status'=>'success']);
        }
    }

    public function postUpdateBoardPositions($request, $response, $args) {
        $params = $request->getParams();
        foreach ($params['boardOrders'] as $bO) {
            ForumBoard::find($bO['bid'])->update(['cid'=>$bO['cid'],'order'=>$bO['order']]);
        }
        return $response->withJSON(['status'=>'success']);
    }

    public function postPermission($request, $response, $args) {
        $params = $request->getParams();

        if ($params['gid'] == -1) {
            $perms = ForumBoardPermission::where(['bid'=>$params['bid'],'gid'=>$params['gid']])->first();
            if (!isset($perms)) {
                ForumBoardPermission::create(['bid'=>$params['bid'],'gid'=>$params['gid'],'cannot_view'=>null,'cannot_post_thread'=>1,'cannot_post_reply'=>1,'cannot_react'=>1])->flushCache();
            }
        }

        $perms = ForumBoardPermission::updateOrCreate(
          ['bid'=>$params['bid'],'gid'=>$params['gid']],
          [$params['pname']=>!empty($params['newVal']) ? null : 1]
        )->flushCache();
        return $response->withJSON(['status'=>'success']);
    }
}
