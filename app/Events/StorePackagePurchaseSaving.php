<?php
namespace App\Events;

use App\Models\StorePackagePurchase;

class StorePackagePurchaseSaving
{
    public $storePackagePurchase;

    public function __construct(StorePackagePurchase $storePackagePurchase)
    {
        $this->storePackagePurchase = $storePackagePurchase;
    }
}
