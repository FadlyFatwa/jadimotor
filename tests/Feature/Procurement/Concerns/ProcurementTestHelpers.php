<?php

namespace Tests\Feature\Procurement\Concerns;

use App\Models\Needlist;
use App\Models\NeedlistItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\SupplierVariasi;
use App\Models\User;
use App\Models\Variasi;

trait ProcurementTestHelpers
{
    protected function procurementUser(array $attributes = []): User
    {
        return User::factory()->role('procurement')->create($attributes);
    }

    protected function supervisorUser(array $attributes = []): User
    {
        return User::factory()->role('supervisor')->create($attributes);
    }

    /**
     * Create a Variasi that a Supplier is able to sell (a supplier_barang row).
     *
     * @return array{0: Variasi, 1: Supplier, 2: SupplierVariasi}
     */
    protected function variasiWithSupplier(?Supplier $supplier = null, array $svOverrides = []): array
    {
        $variasi = Variasi::factory()->create();
        $supplier = $supplier ?? Supplier::factory()->create();
        $sv = SupplierVariasi::factory()->create(array_merge([
            'id_variasi' => $variasi->id_variasi,
            'id_supplier' => $supplier->id_supplier,
        ], $svOverrides));

        return [$variasi, $supplier, $sv];
    }

    /**
     * Build a Needlist owned by $user with a single NeedlistItem for $variasi.
     */
    protected function needlistWithItem(
        User $user,
        Variasi $variasi,
        int $qty = 5,
        string $needlistStatus = 'draft',
        string $itemStatus = 'pending'
    ): Needlist {
        $needlist = Needlist::factory()->create([
            'user_id' => $user->id,
            'status' => $needlistStatus,
        ]);

        NeedlistItem::factory()->create([
            'needlist_id' => $needlist->id,
            'id_variasi' => $variasi->id_variasi,
            'qty' => $qty,
            'status' => $itemStatus,
        ]);

        return $needlist;
    }

    /**
     * Build a full chain up to and including a "responded + selected" inquiry item,
     * ready to be turned into a Purchase Order via createFromNeedlist.
     *
     * @return array{needlist: Needlist, variasi: Variasi, supplier: Supplier, inquiry: SupplierInquiry, inquiryItem: SupplierInquiryItem}
     */
    protected function needlistReadyForPo(User $user, int $qty = 5, float $harga = 50000): array
    {
        [$variasi, $supplier] = $this->variasiWithSupplier();

        $needlist = $this->needlistWithItem($user, $variasi, $qty, 'approved', 'approved');

        $inquiry = SupplierInquiry::factory()->create([
            'needlist_id' => $needlist->id,
            'supplier_id' => $supplier->id_supplier,
            'status' => 'responded',
        ]);

        $inquiryItem = SupplierInquiryItem::factory()->create([
            'inquiry_id' => $inquiry->id,
            'id_variasi' => $variasi->id_variasi,
            'qty' => $qty,
            'harga_penawaran' => $harga,
            'status' => 'selected',
        ]);

        return compact('needlist', 'variasi', 'supplier', 'inquiry', 'inquiryItem');
    }

    /**
     * Build a full PurchaseOrder + single PurchaseOrderItem ready for goods receipt testing.
     *
     * @return array{po: PurchaseOrder, poItem: PurchaseOrderItem, needlist: Needlist, variasi: Variasi, supplier: Supplier}
     */
    protected function poReadyForReceipt(User $user, int $qtyOrder = 10, float $harga = 50000): array
    {
        $ready = $this->needlistReadyForPo($user, $qtyOrder, $harga);

        $po = PurchaseOrder::factory()->create([
            'supplier_id' => $ready['supplier']->id_supplier,
            'needlist_id' => $ready['needlist']->id,
            'status' => 'open',
        ]);

        $poItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'inquiry_id' => $ready['inquiry']->id,
            'id_variasi' => $ready['variasi']->id_variasi,
            'qty_order' => $qtyOrder,
            'harga_beli' => $harga,
            'qty_received' => 0,
        ]);

        return [
            'po' => $po,
            'poItem' => $poItem,
            'needlist' => $ready['needlist'],
            'variasi' => $ready['variasi'],
            'supplier' => $ready['supplier'],
        ];
    }
}
