<?php
namespace App\Controllers;

use \Slim\Views\Twig as View;
use App\Models\User;
use App\Models\Group;
use App\Models\Feature;
use App\Models\Setting;
use App\Models\Server;
use App\Models\StaffMember;
use App\Models\StorePackage;
use App\Models\LoadingScreen;

class PagesController extends Controller
{
    public function lang($request, $response) {
        $this->view; // initialized so that $this->lang can be used
        header_remove('Cache-Control'); header_remove('Pragma'); $cache_sec = 86400; $exp = gmdate("D, d M Y H:i:s", time() + $cache_sec) . " GMT";
        return $response->withJSON($this->lang)->withHeader('Cache-Control', "public, max-age=$cache_sec")->withHeader('Expires', $exp);
    }

    public function moduleAsset($request,$response,$args) {
        header_remove('Cache-Control'); header_remove('Pragma'); $cache_sec = 86400; $exp = gmdate("D, d M Y H:i:s", time() + $cache_sec) . " GMT";
        echo file_get_contents(__DIR__ . '/../../modules/'.$args['modulename'].'/public/'.$args['folder'].'/'.$args['file'].'.'.$args['folder']);
        $contentType = 'application/javascript'; if ($args['folder'] == 'css') { $contentType = 'text/css'; }
        return $response->withHeader('Cache-Control', "public, max-age=$cache_sec")->withHeader('Expires', $exp)->withHeader('Content-Type', $contentType);
    }

    public function landing($request, $response) {
        if (Setting::find('display_community')->value) {
            $community = $this->cache->get('community');
            if (empty($community)) {
                $community = [
                    'monthly_players' => User::whereNotNull('last_played')->whereRaw('MONTH(last_played) = MONTH(NOW())')->count(),
                    'total_players' => User::whereNotNull('last_played')->count(),
                    'registered_users' => User::whereNotNull('last_online')->count()
                ];
                $this->cache->put('community', $community, 15);
            }
            $this->view->getEnvironment()->addGlobal('community', $community);
        }
        $this->view->getEnvironment()->addGlobal('servers', Server::orderBy('order','asc')->get());
        $this->view->getEnvironment()->addGlobal('features', Feature::orderBy('order','asc')->get()->toArray());
        $this->view->getEnvironment()->addGlobal('staffmembers', StaffMember::orderBy('order','asc')->get());
        return $this->view->render($response, 'landing.twig');
    }

    public function bans($request, $response) {
        return $this->view->render($response, 'bans.twig');
    }

    public function users($request, $response) {
        $this->view->getEnvironment()->addGlobal('groups', Group::orderBy('order','ASC')->get());
        return $this->view->render($response, 'users.twig');
    }

    public function profile($request, $response, $args) {
        if (isset($this->container['ForumsPageController'])) {
            $user = \Forums\Models\ForumUser::find($args['steamid']);
        } else {
            $user = User::find($args['steamid']);
        }
        if (!isset($user)) {
            return $response->withStatus(404);
        }
        if ($this->auth->isAdmin()) {
            $this->view->getEnvironment()->addGlobal('groups', Group::orderBy('order','ASC')->get());
        }
        $this->view->getEnvironment()->addGlobal('user', $user);
        return $this->view->render($response, 'profile.twig');
    }

    public function tos($request, $response, $args) {
        return $this->view->render($response, 'legal/tos.twig');
    }

    public function privacy($request, $response, $args) {
        return $this->view->render($response, 'legal/privacy.twig');
    }

    public function impressum($request, $response, $args) {
        return $this->view->render($response, 'legal/impressum.twig');
    }

    public function loading($request, $response, $args) {
        $steamid = $request->getParam('steamid');
        if (empty($steamid) || $steamid == '%s') {
            $steamid = '76561198048341306';
        }
        if(!preg_match('/7656119[0-9]{10}/i', $steamid)) {
            die('Invalid SteamID');
        }
        $this->view->getEnvironment()->addGlobal('user', User::firstOrCreate(['steamid'=>$steamid]));
        $this->view->getEnvironment()->addGlobal('loadingscreen', LoadingScreen::find($args['id'])->toArray());
        return $this->view->render($response, 'loading.twig');
    }

    public function adminServers($request, $response) {
        $this->view->getEnvironment()->addGlobal('servers', Server::orderBy('order','asc')->get()->toArray());
        return $this->view->render($response, 'admin/servers.twig');
    }

    public function adminFeatures($request, $response) {
        $this->view->getEnvironment()->addGlobal('features', Feature::orderBy('order','asc')->get()->toArray());
        return $this->view->render($response, 'admin/features.twig');
    }

    public function adminLinks($request, $response) {
        return $this->view->render($response, 'admin/links.twig');
    }

    public function adminGroups($request, $response) {
        $groups = Group::orderBy('order','asc')->get()->toArray();
        foreach ($groups as &$group) {
          $group['id'] = $group['gid']; unset($group['gid']);
        }
        $this->view->getEnvironment()->addGlobal('groups', $groups);
        return $this->view->render($response, 'admin/groups.twig');
    }

    public function adminPackagesServerPicker($request, $response, $args) {
        $this->view->getEnvironment()->addGlobal('servers', Server::orderBy('order','asc')->get());
        return $this->view->render($response, 'store/serverpicker.twig');
    }

    public function adminPackages($request, $response, $args) {
        $storePackages = StorePackage::where('server', $args['server'])->orderBy('order','asc')->get();
        $server = Server::find($args['server']);
        $this->view->getEnvironment()->addGlobal('server', ['id'=>$args['server'],'name'=>$server->name,'game'=>$server->game]);
        $this->view->getEnvironment()->addGlobal('groups', Group::orderBy('order','asc')->get());
        $storePackagesTabbed = [];
        foreach ($storePackages as $k=>$sP) {
            $storePackagesTabbed[$k] = $sP;
            $storePackagesTabbed[$k]['perma_weapons'] = explode(',',$sP['perma_weapons'] ?? null);
            $storePackagesTabbed[$k]['activeTab'] = 'General';
        }

        $this->view->getEnvironment()->addGlobal('storepackagestabbed', $storePackagesTabbed);
        return $this->view->render($response, 'admin/packages.twig');
    }

    public function notifications($request, $response, $args) {
        $perPage = 25;
        $notifications = $this->auth->user()->notifications();
        $this->view->getEnvironment()->addGlobal('notifications', $notifications->paginate($perPage, ['*'], 'page', $args['page'] ?? 1));

        $totalPages = round($notifications->count()/$perPage) ?? 1;
        preg_match('/\/notifications/', $request->getUri()->getPath(), $urlbase);
        $this->view->getEnvironment()->addGlobal('pagination', [
            'current' => $args['page'] ?? 1,
            'total' => $totalPages,
            'urlbase' => $urlbase[0]
        ]);
        return $this->view->render($response, 'notifications.twig');
    }

    public function error($request, $response, $args) {
        $this->view->getEnvironment()->addGlobal('errorCode', $args['code']);
        return $this->view->render($response, 'error.twig');
    }
}
