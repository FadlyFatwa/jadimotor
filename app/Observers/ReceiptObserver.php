<?php

namespace App\Observers;

use App\Models\Receipt;
use App\Services\SupplierPerformanceService;

class ReceiptObserver
{
    public function created(Receipt $receipt): void
    {
        $supplierId = $receipt->purchaseOrder?->supplier_id;
        if ($supplierId) {
            app(SupplierPerformanceService::class)->recalculate($supplierId);
        }
    }
}
