<?php
namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Server;
use App\Models\User;
use App\Models\Group;
use App\Models\Ban;
use App\Models\StorePackage;
use App\Models\StorePackagePurchase;

class ServerApiController extends Controller
{

    function getConnectionCheck($request, $response, $args) {
        return $response->withJSON(['status'=>'success']);
    }

    function getUserDetails($request, $response, $args) {
        $params = $request->getParams();

        $user = User::firstOrCreate(['steamid'=>$args['steamid']]);
        $user->update(['name'=>$user->steam->personaname??null, 'last_played'=>DB::raw('CURRENT_TIMESTAMP()')]);

        if(!isset($params['banScope']) || isset($params['banScope']) && $params['banScope'] != 'none') {
            $banned = Ban::where('offender_steamid',$args['steamid'])->where(function ($q) use ($request,$args,$params) {
                if(!isset($params['banScope']) || $params['banScope'] == 'server') {
                    $q->where('server',$request->getAttribute('server')->id)->orWhereNull('server');
                }
            })->where(function($q) {
                $q->whereRaw('expires > NOW()')->orWhereNull('expires');
            })->exists();
        } else {
            $banned = false;
        }

        if (!$banned) {
            $unredeemedPackages = StorePackagePurchase::where('steamid',$args['steamid'])
                          ->where('redeemed',0)->orWhere('redeemed',null)
                          ->whereHas('storePackage', function ($q) use ($request,$args) {
                              $q->where('server',$request->getAttribute('server')->id);
                          })->get()->keyBy('id');
            foreach ($unredeemedPackages as $unredeemedP) {
                $unredeemedP->update(['redeemed'=>1]);
                if ($unredeemedP->storePackage->gid != null) {
                    $unredeemedP->storePackage->group = Group::find($unredeemedP->storePackage->gid)->ingame_equivalent??null; // used for granting group in-game
                }
            }

            $expiringPackages = [];
            $timedPackages = StorePackagePurchase::where('steamid',$args['steamid'])
                          ->whereHas('storePackage', function ($q) use ($request,$args) {
                              $q->where('server',$request->getAttribute('server')->id);
                          })
                          ->where('redeemed',1)
                          ->where('expired',0)->get();
            foreach ($timedPackages as $timedPackage) {
                $datediff = time() - strtotime($timedPackage->purchase_timestamp);
                $daysSince = round($datediff / (60 * 60 * 24));
                if ($daysSince > $timedPackage->storePackage->valid_for) {
                    $expiringPackages[$timedPackage->id] = $timedPackage;
                    $timedPackage->update(['expired'=>1]);
                    if ($timedPackage->storePackage->gid != null) {
                        $user->update(['gid'=>null]);
                        $timedPackage->storePackage->group = Group::find($timedPackage->storePackage->gid)->ingame_equivalent??null; // used for revoking group in-game
                    }
                    if (isset($unredeemedPackages[$timedPackage->id])) { // prevent unredeemed but already expired packages from being redeemed
                        unset($unredeemedPackages[$timedPackage->id]);
                    }
                }
            }

            $permaWeapons = [];
            $weaponPackagePurchases = StorePackagePurchase::where('steamid',$args['steamid'])
                          ->where(function ($q) {
                              $q->where('expired',0)->orWhere('expired',null);
                          })
                          ->whereHas('storePackage', function ($q) use ($request,$args) {
                              $q->where('server',$request->getAttribute('server')->id);
                              $q->whereNotNull('perma_weapons');
                          })->get();
            foreach ($weaponPackagePurchases as $weaponPackagePurchase) {
                $weaponPackage = StorePackage::find($weaponPackagePurchase->package);
                $permaWeapons = array_merge(explode(',', $weaponPackage->perma_weapons),$permaWeapons);
            }
        }

        if ($user->gid == null) {
            if ($user->group_sync_revoked) {
                $group = 'revoked';
            }
        } else {
            $group = $user->group->ingame_equivalent;
        }

        return $response->withJSON([
            'status'=>'success',
            'data' => [
                'banned' => $banned,
                'group' => $group ?? null,
                'unredeemed_packages' => $unredeemedPackages? array_values($unredeemedPackages->toArray()): [],
                'expiring_packages' => array_values($expiringPackages) ?? [],
                'perma_weapons' => $permaWeapons ?? []
            ]
        ]);
    }

    function postUserBan($request, $response, $args) {
        $params = $request->getParams();

        Ban::create([
          'offender_steamid' => $args['steamid'],
          'server' => isset($params['global'])? null: $request->getAttribute('server')->id,
          'reason' => $params['reason'],
          'admin_steamid' => $params['admin_steamid'],
          'expires' => $params['expiry_minutes'] > 0? DB::raw('DATE_ADD(NOW(), INTERVAL '. $params['expiry_minutes'] .' MINUTE)'): null
        ])->flushCache();

        return $response->withJSON(['status'=>'success']);
    }

    function postUserGroup($request, $response, $args) {
        $params = $request->getParams();
        if ($params['group'] == "user") { $params['group'] = null; }
        if ($params['group'] != null) {
            $group = Group::where(['ingame_equivalent' => $params['group']])->first();
            if (!isset($group)) {
                $group = Group::updateOrCreate(
                    ['name' => ucfirst($params['group'])],
                    ['ingame_equivalent' => $params['group']]
                );
                $group->flushCache();
            }
        }
        User::find($args['steamid'])->update(['gid'=>$group->gid??null]);
        return $response->withJSON(['status'=>'success']);
    }
}
