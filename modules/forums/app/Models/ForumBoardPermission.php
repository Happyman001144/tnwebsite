<?php
namespace Forums\Models;
use App\Models\Model;

class ForumBoardPermission extends Model
{
    protected $primaryKey = 'pid';
    public $timestamps = false;
    protected $guarded = [];
}
