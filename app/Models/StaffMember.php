<?php
namespace App\Models;

class StaffMember extends Model
{
    protected $table = 'team';
    protected $primaryKey = 'steamid';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'steamid' => 'string'
    ];

    public function user() {
        return $this->hasOne('App\Models\User', 'steamid', 'steamid');
    }

    public function serverR() { /* R suffix due to relationship name colliding with column name */
        return $this->hasOne('App\Models\Server', 'id', 'server');
    }
}
