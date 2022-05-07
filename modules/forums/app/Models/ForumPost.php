<?php
namespace Forums\Models;
use App\Models\Model;

class ForumPost extends Model
{
    protected $primaryKey = 'pid';
    public $timestamps = false;
    protected $guarded = [];

    public function quotedPost() {
        return $this->hasOne('Forums\Models\ForumPost', 'pid', 'reply_to_pid');
    }

    public function thread() {
        return $this->hasOne('Forums\Models\ForumThread', 'tid', 'tid');
    }

    public function user() {
        return $this->hasOne('Forums\Models\ForumUser', 'steamid', 'steamid');
    }

    public function reactions() {
        return $this->hasMany('Forums\Models\ForumReaction', 'pid', 'pid');
    }
}
