<?php
namespace App\Models;

class StoreCredit extends Model
{
    protected $primaryKey = 'steamid';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
