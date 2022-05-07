<?php
namespace App\Models;

use App\Models\User;

class Notification extends Model
{
    protected $primaryKey = 'nid';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'json' => 'array'
    ];

    public function getUserAttribute() {
        return User::find($this->json['ref_steamid']);
    }
}
