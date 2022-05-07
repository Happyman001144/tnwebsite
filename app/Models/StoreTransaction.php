<?php
namespace App\Models;

class StoreTransaction extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function user() {
        return $this->hasOne('App\Models\User', 'steamid', 'steamid');
    }
}
