<?php
namespace Forums\Models;

use App\Models\Model;

class ForumThreadsRead extends Model
{
    protected $primaryKey = 'tid';
    protected $table = 'forum_threads_read';
    public $timestamps = false;
    protected $guarded = [];

    public function thread() {
        return $this->hasOne('Forums\Models\ForumThread', 'tid', 'tid');
    }

    public function user() {
        return $this->hasOne('App\Models\User', 'steamid', 'steamid');
    }
}
