<?php
namespace App\Controllers;

use \Slim\Views\Twig as View;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Ban;
use App\Models\Server;
use App\Models\Feature;
use App\Models\User;
use App\Models\Group;
use App\Models\Setting;
use App\Models\Notification;
use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

class ApiController extends Controller
{

    function deleteCache($request, $response) {
        return $response->withJSON(['status'=>$this->cache->flush()]);
    }

    public function ban($request, $response) {
      $params = $request->getParams();
      $ban = Ban::create([
        'server' => null,
        'offender_steamid' => $params['steamid'],
        'created' => DB::raw('NOW()'),
        'expires' => $params['length'] != 0 ? DB::raw('DATE_ADD(NOW(), INTERVAL '.$params['length'].' MINUTE)'): null,
        'reason' => $params['reason'],
        'admin_steamid' => $this->container->auth->user()->steamid
      ]);
      $ban->flushCache();
      return $response->withJSON(['status'=>'success']);
    }

    public function pardonBan($request, $response) {
      $params = $request->getParams();
      Ban::where('id',$params['banid'])->update(['expires'=>DB::raw('NOW()')]);
      return $response->withJSON(['status'=>'success']);
    }

    public function removeBan($request, $response) {
      $params = $request->getParams();
      $ban = Ban::destroy($params['banid']);
      return $response->withJSON(['status'=>'success']);
    }

    public function bans($request, $response) {
      $params = $request->getParams();
      if (isset($params['offender_steamid'])) {
        $bans = Ban::where('offender_steamid', '=', $params['offender_steamid'])->orderBy('id','DESC')->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
      } else if (!empty($params['filterSteamID'])) {
        $bans = Ban::where('offender_steamid', 'like', "%{$params['filterSteamID']}%")
                  ->orWhere('admin_steamid', 'like', "%{$params['filterSteamID']}%")
                  ->orderBy('id','DESC')
                  ->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
      } else {
        $bans = Ban::orderBy('id','DESC')->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
      }
      $now = new \DateTime(DB::select(DB::raw('SELECT NOW() AS now'))[0]->now);
      foreach ($bans as $ban) {
        $offender = User::find($ban->offender_steamid);
        $admin = User::find($ban->admin_steamid);
        $ban->offender_steamid = (string)$ban->offender_steamid;
        $ban->offender_name = $offender->name ?? null;
        $ban->offender_avatarfull = $offender->steam->avatarfull ?? null;
        $ban->admin_steamid = (string)$ban->admin_steamid;
        $ban->admin_name = $admin->name ?? null;
        $ban->admin_avatarfull = $admin->steam->avatarfull ?? null;
        $server = Server::find($ban->server);
        $ban->server_name = $server->name ?? 'Global';
        $ban->expired = (new \DateTime($ban->expires) < $now);
      }
      return $response->withJSON($bans);
    }

    public function servers($request, $response) {
      if ($this->cache->has('api_servers')) {
          return $response->withJSON($this->cache->get('api_servers'));
      }

      $servers = Server::get();

      function queryServer ($ip, $queryport) {
      	$socket = @fsockopen("udp://".$ip, $queryport , $errno, $errstr, 1);
      	if ($socket) {
      		stream_set_timeout($socket, 1);
      		stream_set_blocking($socket, TRUE);
      		fwrite($socket, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00");
      		$response = fread($socket, 4096);
      		@fclose($socket);

      		$packet = explode("\x00", substr($response, 6), 5);
      		$server = null;

      		if (!empty($packet[0])) {
      			$server = array();
      			$server['name'] = $packet[0];
      			$server['map'] = $packet[1];
      			$server['game'] = $packet[2];
      			$server['description'] = $packet[3];
      			$inner = $packet[4];
      			$server['players'] = ord(substr($inner, 2, 1));
      			$server['playersmax'] = ord(substr($inner, 3, 1));
      			$server['password'] = ord(substr($inner, 7, 1));
      			$server['vac'] = ord(substr($inner, 8, 1));
      		}

      		return($server);
      	}
      }

      if (!empty($servers)) {
        foreach ($servers as $server) {
          if (isset($server['address'])) {
            if ($server['game'] !== "minecraft") {
                $serverQueryResponse[$server['id']] = queryServer($server['address'],$server['queryport']);
            } else {
                $Query = new MinecraftQuery();
              	try {
                		$Query->Connect($server->address, $server->queryport);
                    $mcInfo = $Query->GetInfo();

                    $srvInfo = array(); // mimic the source query response array
                    $srvInfo['players'] = $mcInfo['Players'];
                    $srvInfo['playersmax'] = $mcInfo['MaxPlayers'];
                    $srvInfo['map'] = $mcInfo['Map'];

                    $serverQueryResponse[$server['id']] = $srvInfo;
              	} catch(MinecraftQueryException $e) {
                    $serverQueryResponse[$server['id']] = null;
              	}
            }
          }
        }
      }
      $this->cache->put('api_servers', $serverQueryResponse??null, 5);
      return $response->withJSON($serverQueryResponse??null);
    }

    public function users($request, $response) {
        $params = $request->getParams();

        if (Setting::find('user_list_visibility')->value == 'hidden' && !$this->auth->isAdmin()) {
            return $response->withStatus(403);
        }

        $users = new User;
        if ($params['name']) {
            $users = $users->where('name', 'like', "%{$params['name']}%");
        }
        if ($params['steamid']) {
            $users = $users->where('steamid', 'like', "%{$params['steamid']}%");
        }
        if ($params['gid']) {
            $users = $users->where('gid', $params['gid']);
        }
        if (in_array($params['orderBy']??null, ['created', 'last_online', 'last_played', 'gid'])) {
            $users = $users->orderBy($params['orderBy'],$params['sortOrder'] == 'ASC' ? 'ASC': 'DESC');
        }
        $users = $users->with('group');
        $users = $users->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        if (!empty($users)) {
            foreach ($users as $user) {
                $user->steam = $user->steam;
            }
        }

        return $response->withJSON($users);
    }

    public function notificationread($request, $response) {
        $params = $request->getParams();
        if (isset($params['nid'])) {
            $notification = Notification::where('nid',$params['nid'])->where('steamid',$_SESSION['steamid']);
        } else {
          $notification = Notification::where('steamid',$_SESSION['steamid']);
        }
        $notification->update(['read'=>1]);
        return $response->withJSON(['success'=>true]);
    }
}
