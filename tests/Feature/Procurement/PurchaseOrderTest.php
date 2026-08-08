<?php

use App\Models\Needlist;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use Tests\Feature\Procurement\Concerns\ProcurementTestHelpers;

uses(ProcurementTestHelpers::class);

test('createFromNeedlist builds a purchase order from selected inquiry items and issues the needlist', function () {
    $user = $this->procurementUser();
    $ready = $this->needlistReadyForPo($user, qty: 8, harga: 55000);

    $response = $this->actingAs($user)->post(route('supplier.create.po', $ready['needlist']->id));

    $response->assertRedirect(route('needlist.show', $ready['needlist']->id));

    $po = PurchaseOrder::where('needlist_id', $ready['needlist']->id)->first();
    expect($po)->not->toBeNull();
    expect($po->status)->toBe('open');
    expect($po->supplier_id)->toBe($ready['supplier']->id_supplier);

    $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)->first();
    expect($poItem->id_variasi)->toBe($ready['variasi']->id_variasi);
    expect($poItem->qty_order)->toBe(8);
    expect((float) $poItem->harga_beli)->toBe(55000.0);

    expect($ready['needlist']->fresh()->status)->toBe('po_issued');
});

test('createFromNeedlist creates one PO per supplier when items are split across suppliers', function () {
    $user = $this->procurementUser();
    [$variasiA, $supplierA] = $this->variasiWithSupplier();
    [$variasiB, $supplierB] = $this->variasiWithSupplier();

    $needlist = \App\Models\Needlist::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
    \App\Models\NeedlistItem::factory()->create(['needlist_id' => $needlist->id, 'id_variasi' => $variasiA->id_variasi, 'qty' => 3, 'status' => 'approved']);
    \App\Models\NeedlistItem::factory()->create(['needlist_id' => $needlist->id, 'id_variasi' => $variasiB->id_variasi, 'qty' => 4, 'status' => 'approved']);

    $inquiryA = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplierA->id_supplier, 'status' => 'responded']);
    SupplierInquiryItem::factory()->create(['inquiry_id' => $inquiryA->id, 'id_variasi' => $variasiA->id_variasi, 'qty' => 3, 'harga_penawaran' => 10000, 'status' => 'selected']);

    $inquiryB = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplierB->id_supplier, 'status' => 'responded']);
    SupplierInquiryItem::factory()->create(['inquiry_id' => $inquiryB->id, 'id_variasi' => $variasiB->id_variasi, 'qty' => 4, 'harga_penawaran' => 20000, 'status' => 'selected']);

    $this->actingAs($user)->post(route('supplier.create.po', $needlist->id));

    expect(PurchaseOrder::where('needlist_id', $needlist->id)->count())->toBe(2);
});

test('createFromNeedlist refuses when no inquiry item has been selected', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'approved', 'approved');
    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier, 'status' => 'responded']);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 5,
        'harga_penawaran' => 10000,
        'status' => 'pending', // not selected
    ]);

    $response = $this->actingAs($user)->post(route('supplier.create.po', $needlist->id));

    $response->assertSessionHas('error');
    expect(PurchaseOrder::where('needlist_id', $needlist->id)->count())->toBe(0);
});

test('createFromNeedlist refuses a needlist that already has a PO', function () {
    $user = $this->procurementUser();
    $ready = $this->needlistReadyForPo($user);
    PurchaseOrder::factory()->create(['needlist_id' => $ready['needlist']->id, 'supplier_id' => $ready['supplier']->id_supplier]);

    $response = $this->actingAs($user)->post(route('supplier.create.po', $ready['needlist']->id));

    $response->assertSessionHas('error');
    expect(PurchaseOrder::where('needlist_id', $ready['needlist']->id)->count())->toBe(1);
});

test('createFromNeedlist refuses when the needlist has no inquiries at all', function () {
    $user = $this->procurementUser();
    [$variasi] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'approved', 'approved');

    $response = $this->actingAs($user)->post(route('supplier.create.po', $needlist->id));

    $response->assertSessionHas('error');
    expect(PurchaseOrder::count())->toBe(0);
});

test('purchase order show and print render', function () {
    $user = $this->procurementUser();
    $ready = $this->needlistReadyForPo($user);
    $po = PurchaseOrder::factory()->create(['needlist_id' => $ready['needlist']->id, 'supplier_id' => $ready['supplier']->id_supplier]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'inquiry_id' => $ready['inquiry']->id,
        'id_variasi' => $ready['variasi']->id_variasi,
    ]);

    $this->actingAs($user)->get(route('purchase-order.show', $po->id))->assertOk();

    $printResponse = $this->actingAs($user)->get(route('purchase-order.print', $po->id));
    $printResponse->assertOk();
    $printResponse->assertHeader('content-type', 'application/pdf');
});
