<?php

use App\Models\Needlist;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\Variasi;
use Tests\Feature\Procurement\Concerns\ProcurementTestHelpers;

uses(ProcurementTestHelpers::class);

test('storeAllFromNeedlist creates one inquiry per capable supplier with matching items', function () {
    $user = $this->procurementUser();
    [$variasi, $supplierA] = $this->variasiWithSupplier();
    $supplierB = Supplier::factory()->create();
    \App\Models\SupplierVariasi::factory()->create([
        'id_variasi' => $variasi->id_variasi,
        'id_supplier' => $supplierB->id_supplier,
    ]);

    $needlist = $this->needlistWithItem($user, $variasi, 6, 'approved', 'approved');

    $response = $this->actingAs($user)->post(route('supplier-inquiry.storeAllFromNeedlist', $needlist->id));

    $response->assertRedirect(route('needlist.show', $needlist->id));

    expect(SupplierInquiry::where('needlist_id', $needlist->id)->count())->toBe(2);
    expect(SupplierInquiryItem::whereIn('inquiry_id', SupplierInquiry::where('needlist_id', $needlist->id)->pluck('id'))->count())->toBe(2);

    foreach (SupplierInquiry::where('needlist_id', $needlist->id)->get() as $inquiry) {
        expect($inquiry->status)->toBe('waiting_response');
        expect($inquiry->items()->first()->qty)->toBe(6);
    }

    expect($needlist->fresh()->status)->toBe('inquiry_created');
});

test('storeAllFromNeedlist skips items marked as reference', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'approved', 'approved');
    $needlist->details()->update(['is_reference' => true]);

    $response = $this->actingAs($user)->post(route('supplier-inquiry.storeAllFromNeedlist', $needlist->id));

    $response->assertRedirect(route('needlist.show', $needlist->id));
    $response->assertSessionHas('error');
    expect(SupplierInquiry::where('needlist_id', $needlist->id)->count())->toBe(0);
});

test('storeAllFromNeedlist requires the needlist to be approved', function () {
    $user = $this->procurementUser();
    [$variasi] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'draft', 'pending');

    $this->actingAs($user)->post(route('supplier-inquiry.storeAllFromNeedlist', $needlist->id))
        ->assertNotFound();
});

test('storeAllFromNeedlist does not duplicate inquiries for a supplier already invited', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'approved', 'approved');

    SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplier->id_supplier,
    ]);

    $this->actingAs($user)->post(route('supplier-inquiry.storeAllFromNeedlist', $needlist->id));

    expect(SupplierInquiry::where('needlist_id', $needlist->id)->where('supplier_id', $supplier->id_supplier)->count())->toBe(1);
});

test('storeResponse records offered prices and marks the inquiry responded', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'inquiry_created', 'approved');

    $inquiry = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplier->id_supplier,
        'status' => 'waiting_response',
    ]);
    $item = SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 6,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->post("/inquiry/{$inquiry->id}/store-response", [
        'items' => [
            $item->id => ['harga_penawaran' => 45000, 'estimasi_pengiriman' => now()->addDays(3)->format('Y-m-d')],
        ],
    ]);

    $response->assertRedirect(route('needlist.show', $needlist->id));
    expect($inquiry->fresh()->status)->toBe('responded');
    expect((float) $item->fresh()->harga_penawaran)->toBe(45000.0);
});

test('storeResponse auto-transitions the needlist to selection_in_progress once every inquiry has responded', function () {
    $user = $this->procurementUser();
    [$variasi, $supplierA] = $this->variasiWithSupplier();
    $supplierB = Supplier::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'inquiry_created', 'approved');

    $inquiryA = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplierA->id_supplier,
        'status' => 'waiting_response',
    ]);
    $itemA = SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryA->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 6,
    ]);

    $inquiryB = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplierB->id_supplier,
        'status' => 'waiting_response',
    ]);
    $itemB = SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryB->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 6,
    ]);

    // Respond to only the first inquiry: needlist should stay inquiry_created.
    $this->actingAs($user)->post("/inquiry/{$inquiryA->id}/store-response", [
        'items' => [$itemA->id => ['harga_penawaran' => 40000]],
    ]);
    expect($needlist->fresh()->status)->toBe('inquiry_created');

    // Respond to the second (last) inquiry: needlist should now flip to selection_in_progress.
    $this->actingAs($user)->post("/inquiry/{$inquiryB->id}/store-response", [
        'items' => [$itemB->id => ['harga_penawaran' => 42000]],
    ]);
    expect($needlist->fresh()->status)->toBe('selection_in_progress');
});

test('fillModal and previewModal render for an existing inquiry', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'inquiry_created', 'approved');
    $inquiry = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplier->id_supplier,
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 6,
    ]);

    $this->actingAs($user)->get("/inquiry/{$inquiry->id}/fill-modal")->assertOk();
    $this->actingAs($user)->get("/inquiry/{$inquiry->id}/preview-modal")->assertOk();
});

test('generatePdf streams a pdf for an inquiry', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();
    $needlist = $this->needlistWithItem($user, $variasi, 6, 'inquiry_created', 'approved');
    $inquiry = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id,
        'supplier_id' => $supplier->id_supplier,
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 6,
    ]);

    $response = $this->actingAs($user)->get(route('inquiry.pdf', $inquiry->id));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
