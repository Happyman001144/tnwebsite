<?php
namespace App\Models;

class Group extends Model
{
    protected $primaryKey = 'gid';
    public $timestamps = false;
    protected $guarded = [];
    protected $dispatchesEvents = [
        'saving' => \App\Events\GroupSaving::class,
    ];
    public $ignoreSavingEvent;

    static function getExtended () {
        $groups = self::get()->keyBy('gid');
        $groupsExtended = array_merge([-1=>['gid'=>-1,'name'=>'Banned'],0=>['gid'=>0,'name'=>'None']],$groups->toArray());
        return $groupsExtended;
    }

    public function getColorAttribute($value)
    {
        $color = $value ?? '007BFF';
        return "#$color";
    }

    public function setColorAttribute($value)
    {
        $this->attributes['color'] = str_replace('#','',$value);
    }
}
