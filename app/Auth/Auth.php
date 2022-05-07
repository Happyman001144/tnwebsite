<?php
namespace App\Auth;

use App\Models\User;
use App\Models\Group;
use App\Models\Ban;

class Auth
{
    public function user() {
        if ($this->check()) {
            return User::find($_SESSION['steamid']);
        }
    }

    public function check() {
        return isset($_SESSION['steamid']);
    }

    public function isOwner() {
      if($this->check() && $_SESSION['steamid'] === 'steamID64') {
          return true;
      } else {
          return false;
      }
    }

    public function isBanned() {
        $unexpiredBan = Ban::where('offender_steamid',$_SESSION['steamid'])->where('server',null)->whereRaw('expires > now() OR expires IS NULL')->first();
        if (isset($unexpiredBan)) {
            return true;
        } else {
            return false;
        }
    }

    public function isAdmin() {
        if ($this->isOwner()) {
            return true;
        } else if ($this->check()) {
            $group = Group::find($this->user()->gid);
            if (isset($group) && $group->perms_site_admin == 1) {
                return true;
            } else {
                return false;
            }
        } else {
          return false;
        }
    }

    public function canBan() {
        if ($this->isAdmin()) {
            return true;
        } else if ($this->check()) {
            $group = Group::find($this->user()->gid);
            if (isset($group) && $group->perms_ban_user == 1) {
                return true;
            } else {
                return false;
            }
        } else {
          return false;
        }
    }

    public function canModerateForums() {
        if (!$this->check()) { return false; }
        if ($this->isAdmin()) {
            return true;
        } else {
            $group = Group::find($this->user()->gid);
            if (isset($group) && $group->perms_forum_moderate == 1) {
                return true;
            } else {
                return false;
            }
        }
    }
}
