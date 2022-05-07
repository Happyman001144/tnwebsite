<?php
namespace App\Models;

use Illuminate\Database\Capsule\Manager as DB;

class User extends Model
{
    protected $primaryKey = 'steamid';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['status'];
    protected $casts = [
        'steamid' => 'string'
    ];
    protected $dispatchesEvents = [
        'saving' => \App\Events\UserSaving::class,
    ];
    public $ignoreSavingEvent;

    public function group() {
        return $this->hasOne('App\Models\Group', 'gid', 'gid');
    }

    public function credits() {
        return $this->hasOne('App\Models\StoreCredit', 'steamid', 'steamid');
    }

    public function notifications() {
        return $this->hasMany('App\Models\Notification', 'steamid', 'steamid')->orderBy('nid','DESC');
    }

    public function unread_notifications() {
        return $this->notifications()->where('read',NULL);
    }

    public function getGidExtendedAttribute() {
        if ($this->banned) {
            return -1;
        } else {
            return $this->gid ?? 0;
        }
    }

    public function getBannedAttribute() {
        $unexpiredBan = Ban::withCacheCooldownSeconds(60)->where('offender_steamid',$this->steamid)
                            ->whereNull('server')->where(function($q) {
                                $q->whereRaw('expires > NOW()')->orWhereNull('expires');
                            })->first();
        if (isset($unexpiredBan)) {
            return true;
        } else {
            return false;
        }
    }

    public function getSteamAttribute() {
        $cacheKey = 'steamProfile_'.$this->steamid;
        $container = app('container');
      	$steam = $container->cache->get($cacheKey);
        if (empty($steam)) {
            $steam_api_key = $container->settings['steam_api_key'];
            $steamAPIresponse = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$steam_api_key."&steamids=".$this->steamid), true);
            $steam = $steamAPIresponse['response']['players'][0];
            $container->cache->put($cacheKey, $steam, 60*24);
        }
        return (object) $steam;
    }

    public function setGidAttribute($value) {
        $this->attributes['gid'] = $value;
        if ($value == null) {
            $this->attributes['group_sync_revoked'] = 1;
        }
    }

    public function getStatusAttribute() {
        if (strtotime($this->last_online) > time()-(60*3)) {
            return 'online';
        } else if (strtotime($this->last_online) > time()-(60*15)) {
            return 'away';
        } else {
            return 'offline';
        }
    }
}
