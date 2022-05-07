<?php
namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\User;
use Hybridauth;

class AuthController extends Controller
{
    public function authenticate($request, $response) {
        $params = $request->getParams();
        $uri = $request->getUri();
        $fwdS = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' && $uri->getScheme() == 'http') ? 's': '';
        $callback = $uri->getScheme().$fwdS.'://'.$uri->getHost().$uri->getPath().'?redirect='.$params['redirect'];

        $config = [
            'callback' => $callback
        ];

        $adapter = new Hybridauth\Provider\Steam($config);
        $adapter->authenticate();

        $userProfile = $adapter->getUserProfile();

        User::updateOrCreate(['steamid'=>$userProfile->identifier],['name'=>$userProfile->displayName,'last_online'=>DB::raw('NOW()')]);

        $_SESSION['steamid'] = $userProfile->identifier;
        $_SESSION['avatarfull'] = $userProfile->photoURL;
        return $response->withRedirect($params['redirect'] ?? $this->router->pathFor('landing'));
    }

    public function logout($request, $response) {
        $params = $request->getParams();
        session_destroy();
        return $response->withRedirect($params['redirect'] ?? $this->router->pathFor('landing'));
    }
}
