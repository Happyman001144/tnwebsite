<?php
namespace Forums\Models;
use App\Models\Model;

class ForumCategory extends Model
{
    protected $primaryKey = 'cid';
    public $timestamps = false;
    protected $guarded = [];

    public function boards() {
        return $this->hasMany('Forums\Models\ForumBoard', 'cid', 'cid')->orderBy('order','ASC');
    }
}
