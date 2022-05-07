<?php
namespace App\Controllers;

use \Slim\Views\Twig as View;
use Illuminate\Database\Capsule\Manager as DB;
use \overint\PaypalIPN;
use App\Models\Setting;
use App\Models\StoreCredit;
use App\Models\StorePackage;
use App\Models\StorePackagePurchase;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionLog;
use App\Models\Server;
use App\Helpers;

class StoreController extends Controller
{
    public function credits($request, $response) {
        $monthly_revenue = StoreTransaction::select([DB::raw('SUM(cost)')])->whereRaw('MONTH(timestamp) = MONTH(CURDATE())')->first()['SUM(cost)'];
        $this->view->getEnvironment()->addGlobal('monthly_revenue', $monthly_revenue);
        $topPurchases = StoreTransaction::select(['steamid',DB::raw('SUM(cost)')])->groupBy('steamid')->orderByRaw('SUM(cost) DESC')->take(5)->get();
        $this->view->getEnvironment()->addGlobal('top_purchases', $topPurchases);
        $recentPurchases = StoreTransaction::orderBy('id','DESC')->take(5)->get();
        $this->view->getEnvironment()->addGlobal('recent_purchases', $recentPurchases);
        return $this->view->render($response, 'store/credits.twig');
    }

    public function servers($request, $response, $args) {
        $servers = Server::orderBy('order','asc')->has('packages')->get();
        if (count($servers) === 1) {
            return $response->withRedirect($this->router->pathFor('store_packages', ['serverid'=>$servers->first()->id]));
        }
        $this->view->getEnvironment()->addGlobal('servers', $servers);
        return $this->view->render($response, 'store/serverpicker.twig');
    }

    public function packages($request, $response, $args) {
        $storePackages = StorePackage::where('server',$args['serverid'])->orderBy('order','ASC')->get()->toArray();
        $storePackages = Helpers::whitelist_keys($storePackages, ['*'=>['id','name','cost','valid_for','image','description','short_description']]);
        $this->view->getEnvironment()->addGlobal('storepackages', $storePackages);
        return $this->view->render($response, 'store/packages.twig');
    }

    public function packagePurchases($request, $response) {
        $params = $request->getParams();

        if (!empty($params['filterSteamID'])) {
          $storePackagePurchases = StorePackagePurchase::where('steamid', 'like', "%{$params['filterSteamID']}%")
                    ->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        } else {
          $storePackagePurchases = StorePackagePurchase::paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        }

        if (!empty($storePackagePurchases)) {
          foreach ($storePackagePurchases as $spp) { // fetch the hasOne relations for Vue
            $spp->user = $spp->user;
            $spp->user->steam = $spp->user->steam;
            $spp->storePackage = $spp->storePackage;
          }
        }

        return $response->withJSON($storePackagePurchases);
    }

    public function transactions($request, $response) {
        $params = $request->getParams();

        if (!empty($params['filterSteamID'])) {
          $data = StoreTransaction::where('steamid', 'like', "%{$params['filterSteamID']}%")
                    ->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        } else {
          $data = StoreTransaction::paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        }

        if (!empty($data)) {
          foreach ($data as $item) { // fetch the hasOne relations for Vue
            $item->user = $item->user;
            $item->user->steam = $item->user->steam;
          }
        }

        return $response->withJSON($data);
    }

    public function transactionLogs($request, $response) {
        $params = $request->getParams();

        if (!empty($params['filter_txn_id'])) {
          $data = StoreTransactionLog::where('txn_id', 'like', "%{$params['filter_txn_id']}%")
                    ->paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        } else {
          $data = StoreTransactionLog::paginate($params['perPage'], ['*'], 'page', $params['targetPage']);
        }

        return $response->withJSON($data);
    }

    public function purchasePackage($request, $response, $args) {
        $package = StorePackage::find($args['id']);

        if ($package) {
            $user = $this->container->auth->user();
            if ($package->purchase_limit) {
                $purchases = StorePackagePurchase::where('package',$package->id)
                                                  ->where('steamid',$user->steamid)
                                                  ->where(function ($q) {
                                                      $q->where('expired',0)->orWhereNull('expired');
                                                  });
                if ($purchases) {
                    if ($purchases->count() >= $package->purchase_limit) {
                        return $response->withJSON(['status'=>'purchase_limit_reached']);
                    }
                }
            }
            if ($package->cost <= $user->credits->credits ?? 0) {
                $cost = $package->cost;
                $updated = StoreCredit::find($user->steamid)->update(['credits'=>DB::raw("credits-'${cost}'")]);
                if ($updated) {
                    if ($package->valid_for) {
                        $expired = 0;
                    }
                    $created = StorePackagePurchase::create([
                        'steamid' => $user->steamid,
                        'package' => $package->id,
                        'expired' => $expired ?? null
                    ]);
                    if ($created) {
                        if ($package->gid) {
                            $user->update(['gid'=>$package->gid]);
                        }
                    }
                }
            } else {
                return $response->withJSON(['status'=>'insufficient_funds']);
            }
        } else {
            return $response->withStatus(404);
        }

        return $response->withJSON(['status'=>'success']);
    }

    public function ipn($request, $response, $args) {
      $storeSettings = Setting::where('category','store')->get()->keyBy('setting')->toArray();
      $params = $request->getParams();

      $ipn = new PaypalIPN();

      $sandboxMode = $storeSettings['enable_sandbox']['value'];

      if ($sandboxMode) {
          $ipn->useSandbox();
      }

      $logMsg = 'Unauthorized';
      $verified = $ipn->verifyIPN();

      if ($verified) {
          $logMsg = 'Unknown error';
          $receiverEmail = strtolower($params['receiver_email']);
          if ($receiverEmail == strtolower($storeSettings['paypal_email']['value'])) {
              if (StoreTransaction::where('txn_id',$params['txn_id'])->exists()) {
                  $logMsg = 'Duplicate txn_id';
              } else {
                  if ($params['payment_status'] == 'Completed') {
                      if ($params['item_name'] == 'Credits') {
                          if ($params['mc_currency'] == $storeSettings['paypal_currency']['value']) {
                              $amount_minimum = $storeSettings['minimum_purchase']['value']/100;
                              if ($params['mc_gross'] >= $amount_minimum) {
                                  $credits = $params['mc_gross']*100; // calculate credits to give based on the tx amount (client supplied fields can't be trusted)
                                  $newTx = StoreTransaction::create([
                                    'txn_id' => $params['txn_id'],
                                    'name' => $params['first_name'].' '. $params['last_name'],
                                    'email' => $params['payer_email'],
                                    'currency' => $params['mc_currency'],
                                    'cost' => $params['mc_gross'],
                                    'credits' => $credits,
                                    'steamid' => $params['custom'],
                                  ]);
                                  if ($newTx) {
                                      $addCredits = StoreCredit::updateOrCreate(['steamid'=>$params['custom']],['credits'=>DB::raw("credits+'${credits}'")]);
                                      if ($addCredits) {
                                          $logMsg = 'Success';
                                      } else {
                                          $logMsg = 'Failed to give credits';
                                      }
                                  } else {
                                      $logMsg = 'Failed to create transaction entry';
                                  }
                              } else {
                                  $logMsg = "Paid amount under {$amount_minimum} {$storeSettings['paypal_currency']['value']} minimum";
                              }
                          } else {
                              $logMsg = "Invalid currency ({$params['mc_currency']})";
                          }
                      } else {
                          $logMsg = "Unrecognized item name ({$params['item_name']})";
                      }
                  } else {
                      $logMsg = "Payment not completed (status: {$params['payment_status']})";
                  }
              }
          } else {
              $logMsg = "Receiver email mismatch ({$receiverEmail})";
          }
      } elseif (!$sandboxMode && isset($params['test_ipn'])) {
          $logMsg = 'Received from sandbox while live';
      }

      StoreTransactionLog::create([
          'txn_id' => $params['txn_id'],
          'status' => $logMsg
      ]);

      return $response->withStatus(200);
    }
}
