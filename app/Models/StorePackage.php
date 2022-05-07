<?php
namespace App\Models;

class StorePackage extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function serverRelation() {
        return $this->hasOne('App\Models\Server', 'id', 'server');
    }
}
