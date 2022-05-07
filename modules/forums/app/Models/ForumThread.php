<?php
namespace Forums\Models;

use App\Models\Model;

class ForumThread extends Model
{
    protected $primaryKey = 'tid';
    public $timestamps = false;
    protected $guarded = [];

    public function posts() {
        return $this->hasMany('Forums\Models\ForumPost', 'tid', 'tid');
    }

    public function latest_post() {
        return $this->hasOne('Forums\Models\ForumPost', 'tid', 'tid')->orderBy('timestamp','DESC');
    }

    public function first_post() {
        return $this->hasOne('Forums\Models\ForumPost', 'tid', 'tid')->orderBy('timestamp','ASC');
    }

    public function user() {
        return $this->hasOne('App\Models\User', 'steamid', 'steamid');
    }

    public function board() {
        return $this->belongsTo('Forums\Models\ForumBoard', 'bid', 'bid');
    }

    public function getReadTimestampAttribute() {
      return $this->read_timestamp_relation->timestamp ?? null;
    }

    public function read_timestamp_relation() {
        return $this->hasOne('Forums\Models\ForumThreadsRead', 'tid', 'tid')->where('steamid',$_SESSION['steamid']??null);
    }
}
