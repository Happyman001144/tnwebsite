<?php
namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
    use Cachable;
    protected $casts = [
        'steamid' => 'string'
    ];
}
