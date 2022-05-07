<?php
namespace App\Models;

class StorePackagePurchase extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $dispatchesEvents = [
        'saving' => \App\Events\StorePackagePurchaseSaving::class,
    ];

    public function user() {
        return $this->hasOne('App\Models\User', 'steamid', 'steamid');
    }

    public function storePackage() {
        return $this->hasOne('App\Models\StorePackage', 'id', 'package');
    }
}
