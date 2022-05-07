<?php
namespace Forums\Models;
use App\Models\User;

class ForumUser extends User
{
    public $table = 'users';

    public function posts() {
        return $this->hasMany('Forums\Models\ForumPost', 'steamid', 'steamid');
    }

    public function getPostsPerDayAttribute() {
        $days_since = date_diff(date_create($this->created),date_create(date("Y-m-d H:i:s")))->format("%a") ?? 1;
        if ($days_since < 1) { $days_since = 1; }
        return $this->posts->count() / $days_since;
    }
}
