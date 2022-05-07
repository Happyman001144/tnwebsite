<?php
namespace Forums\Models;
use App\Models\Model;

class ForumReaction extends Model
{
    protected $primaryKey = 'rid';
    public $timestamps = false;
    protected $guarded = [];

    public function post() {
        return $this->hasOne('Forums\Models\ForumPost', 'pid', 'pid');
    }
}
