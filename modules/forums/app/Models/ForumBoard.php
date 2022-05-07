<?php
namespace Forums\Models;

use App\Models\Model;
use App\Models\Group;
use Forums\Models\ForumBoardPermission;

class ForumBoard extends Model
{
    protected $primaryKey = 'bid';
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['permissions_extended'];

    public function getPermissionsAttribute() {
        return ForumBoardPermission::where('bid',$this->bid)->get()->keyBy('gid');
    }

    public function permissions_relation() {
        return $this->hasMany('Forums\Models\ForumBoardPermission', 'bid', 'bid');
    }

    public function getPermissionsExtendedAttribute() {
        $returnArr = [];
        $groupsExtended = Group::getExtended();
        foreach ($groupsExtended as $group) {
            if (isset($this->permissions[$group['gid']])) {
                $perms = $this->permissions[$group['gid']];
            } else {
                if ($group['gid'] === -1) {
                    $perms = ['cannot_view'=>null, 'cannot_post_thread'=>1, 'cannot_post_reply'=>1, 'cannot_react'=>1];
                } else {
                    $perms = ['cannot_view'=>null, 'cannot_post_thread'=>null, 'cannot_post_reply'=>null, 'cannot_react'=>null];
                }
            }
            $returnArr[$group['gid']] = ['cannot_view'=>$perms['cannot_view'], 'cannot_post_thread'=>$perms['cannot_post_thread'], 'cannot_post_reply'=>$perms['cannot_post_reply'], 'cannot_react'=>$perms['cannot_react']];
        }
        return $returnArr;
    }

    public function threads() {
        return $this->hasMany('Forums\Models\ForumThread', 'bid', 'bid');
    }

    public function getTotalThreadsAttribute() {
        return $this->threads()->count();
    }

    public function latest_thread() {
        return $this->hasOne('Forums\Models\ForumThread', 'bid', 'bid')->orderBy('last_posted','DESC');
    }

    public function getTotalPostsAttribute() {
        return ForumPost::whereIn('tid', $this->threads()->pluck('tid'))->count();
    }
}
