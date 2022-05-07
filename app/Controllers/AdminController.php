<?php
namespace App\Controllers;

use \Slim\Views\Twig as View;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\User;
use App\Models\Group;
use App\Models\StaffMember;
use App\Models\Setting;
use App\Models\Server;
use App\Models\LoadingScreen;
use App\Models\StorePackage;
use App\Models\StoreCredit;
use App\Models\StoreTransaction;

class AdminController extends Controller
{
    public function save($request, $response) {
      $params = $request->getParams();
      $category = $params['category'];
      unset ($params['category']);
      foreach($params as $setting=>$val) {
        if (empty($val)) { $val = null; }
        Setting::updateOrCreate(['setting'=>$setting],['category'=>$category],['value'=>$val])->update(['value'=>$val]);
      }
      return $response->withJSON(['status'=>'success']);
    }

    public function cardSave($request, $response) {
      $params = $request->getParams();

      $modelWhitelist = ['server','feature','group','navbarLink','storePackage','staffMember','loadingScreen'];

      $idCol = 'id';
      if ($params['type'] == 'group') {
        $idCol = 'gid';
      } else if ($params['type'] == 'staffMember') {
        $idCol = 'steamid';
      } else if ($params['type'] == 'server') {
          $this->cache->forget('api_servers');
      }

      if (in_array($params['type'],$modelWhitelist)) {
          $c = 'App\\Models\\'.ucfirst($params['type']);
      } else {
          return $response->withStatus(403);
      }

      if ($request->isPost()) {
          $updateFields = [];
          foreach ($params as $k=>$param) {
              if ($k != 'type') {
                  if (empty($param ?? null)) { $param = null; }
                  $updateFields[$k] = $param;
              }
          }
          if ($params['type'] == 'storePackage' && empty($params[$idCol])) {
              $package = $c::create($updateFields);
              $package->serverRelation->flushCache();
              return $response->withJSON(['status'=>'success', 'id'=>$package->id]);
          } else {
              $c::updateOrCreate([$idCol => $params[$idCol]], $updateFields)->flushCache();
          }
      } else if ($request->isDelete()) {
          if ($params['type'] == 'storePackage') {
              StorePackage::find($params['id'])->serverRelation->flushCache();
          }
          $c::destroy([$idCol=>$params['id']]);
      }
      return $response->withJSON(['status'=>'success']);
    }

    public function dashboard($request,$response) {
      $total_revenue = StoreTransaction::select(DB::raw('SUM(cost)'))->first()['SUM(cost)'];
      $this->view->getEnvironment()->addGlobal('total_revenue', $total_revenue);

      $monthly_revenue = StoreTransaction::select([DB::raw('SUM(cost)')])->whereRaw('MONTH(timestamp) = MONTH(CURDATE())')->first()['SUM(cost)'];
      $this->view->getEnvironment()->addGlobal('monthly_revenue', $monthly_revenue);

      $weekly_revenue = StoreTransaction::select([DB::raw('SUM(cost)')])->whereRaw('WEEK(timestamp) = WEEK(CURDATE())')->first()['SUM(cost)'];
      $this->view->getEnvironment()->addGlobal('weekly_revenue', $weekly_revenue);

      $revenue_graph_data_raw = StoreTransaction::select([DB::raw('SUM(cost)'),DB::raw('MONTH(timestamp)')])->whereRaw('YEAR(timestamp) = YEAR(CURDATE())')->groupBy(DB::raw('MONTH(timestamp)'))->get();

      foreach ($revenue_graph_data_raw as $rgd) {
          $revenue_graph_data[$rgd['MONTH(timestamp)']] = $rgd['SUM(cost)'];
      }
      for ($i = 1; $i <= 12; $i++) {
          if (!isset($revenue_graph_data[$i])) {
              $revenue_graph_data[$i] = 0;
          }
      }
      ksort($revenue_graph_data);

      $this->view->getEnvironment()->addGlobal('revenue_graph_data', $revenue_graph_data);
      return $this->view->render($response, 'admin/dashboard.twig');
    }

    public function misc($request, $response) {
        $pp_currency_options = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'ILS', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
        $this->view->getEnvironment()->addGlobal('pp_currency_options', $pp_currency_options);
        return $this->view->render($response, 'admin/misc.twig');
    }

    public function loadingScreens($request, $response) {
        $this->view->getEnvironment()->addGlobal('loadingscreens', LoadingScreen::/*orderBy('order','asc')->*/get()->toArray());
        return $this->view->render($response, 'admin/loadingscreens.twig');
    }

    public function changeGroup($request, $response) {
        $params = $request->getParams();
        $targetGid = null; if ($params['gid'] != 0) { $targetGid = $params['gid']; }
        User::find($params['steamid'])->update(['gid'=>$targetGid]);
        return $response->withJSON(['status'=>'success']);
    }

    public function updateCredits($request, $response) {
        $params = $request->getParams();
        StoreCredit::firstOrCreate(['steamid'=>$params['steamid']])->update(['credits'=>$params['amount']]);
        return $response->withJSON(['status'=>'success']);
    }

    public function team($request, $response) {
        $staffMembers = StaffMember::orderBy('order','asc')->get();
        foreach ($staffMembers as &$staffMember) {
            $staffMember['user'] = $staffMember->user;
        }
        $this->view->getEnvironment()->addGlobal('staffmembers', $staffMembers->toArray());
        $this->view->getEnvironment()->addGlobal('servers', Server::orderBy('order','asc')->get()->toArray());
        return $this->view->render($response, 'admin/team.twig');
    }

}
