# Catatan Skema Database — Modul Pemilihan Supplier (SAW)

> Dokumen referensi untuk skripsi. Scope FINAL (disepakati bersama dosen pembimbing): **Sistem Pemilihan Supplier menggunakan metode SAW**, berdiri sendiri secara narasi, mencakup UC-01 (Kelola Kriteria & Bobot), UC-02 (Kelola Kinerja Supplier), UC-03 (Pemilihan Supplier — auto-run), UC-04 (Konfirmasi Supplier Terpilih). Login & master data = prasyarat/given.
>
> Struktur dokumen ini dipecah jadi 2 bagian: **(A)** 10 tabel yang masuk Class Diagram Bab 4 — detail lengkap (migration + model + kelas terkait). **(B)** tabel pendukung yang dipakai secara teknis tapi TIDAK digambar di Class Diagram — ringkas saja, untuk pemahaman alur data.
>
> Status per 2026-07-26: **4 perubahan yang disepakati sudah dieksekusi** (drop kolom, fix bug `sumber_c3`, rename+pindah 4 controller ke `App\Http\Controllers\SupplierSelection`) — lihat Rekap Perubahan di bagian akhir. Masih ada 1 hal yang sedang didiskusikan (penghapusan total fitur "Hitung Ulang"), belum dieksekusi.

---

## Daftar Isi
1. [Bagian A — Tabel yang Masuk Class Diagram (10 tabel final)](#bagian-a)
2. [Bagian B — Tabel Pendukung (di luar scope Class Diagram)](#bagian-b)
3. [Controller & View per Use Case](#controller-view)
4. [Rekap Perubahan yang Sudah Dieksekusi](#rekap-pending)
5. [Alur Kelas Ringkas](#alur-kelas)

---

<a id="bagian-a"></a>
## Bagian A — Tabel yang Masuk Class Diagram (10 tabel final)

### `needlists`

**Migration** — `database/migrations/2025_09_14_152513_create_needlists_table.php`
```php
Schema::create('needlists', function (Blueprint $table) {
    $table->id();
    $table->string('kode_needlist')->unique();
    $table->unsignedBigInteger('user_id');
    $table->enum('status', ['draft', 'submitted', 'revised', 'finalized'])->default('draft');
    $table->enum('approval_status', ['draft', 'waiting', 'approved', 'rejected'])->default('draft');
    $table->unsignedBigInteger('approved_by')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->text('approval_notes')->nullable();
    $table->timestamps();
});
```

**Alter** — `2026_05_02_000002_fix_needlists_status_enum.php`
```php
DB::statement("
    ALTER TABLE needlists MODIFY COLUMN status ENUM(
        'draft', 'submitted', 'approved', 'rejected',
        'inquiry_created', 'selection_in_progress', 'po_issued', 'completed'
    ) NOT NULL DEFAULT 'draft'
");
```

**Atribut final:** `id`, `kode_needlist` (unique), `user_id` (no FK constraint), `status` enum, `approval_status` enum, `approved_by`, `approved_at`, `approval_notes`, timestamps.

**Model** — `app/Models/NeedList.php`
```php
class Needlist extends Model
{
    protected $fillable = ['kode_needlist', 'user_id', 'status', 'approval_notes', 'approved_by', 'approved_at', 'approval_status'];
    public function details() { return $this->hasMany(NeedlistItem::class, 'needlist_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function supplierInquiries() { return $this->hasMany(SupplierInquiry::class, 'needlist_id'); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'needlist_id'); }
    public function sawPerhitungan() { return $this->hasMany(SawPerhitungan::class, 'needlist_id'); }
    public function sawRekomendasi() { return $this->hasMany(SawRekomendasi::class, 'needlist_id'); }
}
```

**Kelas terkait:** `SupplierRecommendationController` (UC-03), `SupplierConfirmationController` (UC-04), `SawBatchCalculator::calculateForNeedlist()`.

---

### `needlist_items`

**Migration** — `2025_09_14_152519_create_needlist_items_table.php`
```php
Schema::create('needlist_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('needlist_id');
    $table->unsignedBigInteger('id_variasi');
    $table->decimal('qty', 8, 2);
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('rejected_reason')->nullable();
    $table->text('keterangan')->nullable();
    $table->timestamps();
    $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
    $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
});
```

**Alter** — `2026_05_02_000005_add_is_reference_to_needlist_items_table.php`
```php
$table->boolean('is_reference')->default(false)->after('keterangan');
```

**Alter** — `2026_06_08_000000_change_qty_columns_to_integer_in_procurement_tables.php`
```php
DB::statement('ALTER TABLE needlist_items MODIFY qty INT NOT NULL');
```

**Atribut final:** `id`, `needlist_id` (FK cascade), `id_variasi` (FK cascade), `qty` int, `status` enum, `rejected_reason`, `keterangan`, `is_reference` bool, timestamps.

**Model** — `app/Models/NeedlistItem.php`
```php
class NeedlistItem extends Model
{
    protected $fillable = ['needlist_id', 'id_variasi', 'qty', 'status', 'rejected_reason', 'keterangan', 'is_reference'];
    protected $casts = ['is_reference' => 'boolean', 'qty' => 'integer'];
    public function needlist() { return $this->belongsTo(Needlist::class); }
    public function variasi() { return $this->belongsTo(Variasi::class, 'id_variasi'); }
    public function supplierBarang() { return $this->hasOne(SupplierVariasi::class, 'id_variasi', 'id_variasi'); }
    public function getSupplierAttribute() { return $this->supplierBarang ? $this->supplierBarang->supplier : null; }
}
```

**Kelas terkait:** `NeedlistSelectionGrouper::buildGroups()` (sumber item + flag `is_reference`).

---

### `variasis`

**Migration** — `2025_02_28_071537_create_variasis_table.php`
```php
Schema::create('variasis', function (Blueprint $table) {
    $table->id('id_variasi');
    $table->string('barcode', 50);
    $table->string('nama_variasi', 100);
    $table->unsignedBigInteger('id_barang');
    $table->foreign('id_barang')->references('id_barang')->on('m_barangs')->onDelete('cascade')->onUpdate('cascade');
    $table->unsignedBigInteger('id_unit');
    $table->foreign('id_unit')->references('id_unit')->on('units')->onDelete('cascade')->onUpdate('cascade');
    $table->decimal('harga_jual');
    $table->decimal('stock')->nullable;   // bug: properti bukan method call, nullable TIDAK berlaku
    $table->enum('status',['active', 'nonactive'])->default('active');
    $table->timestamps();
});
```

**Alter** — `2026_05_01_000003_add_fields_to_variasis_table.php`
```php
$table->string('part_number')->nullable()->after('nama_variasi');
$table->boolean('is_active')->default(true)->after('status');
```

**Alter** — `2026_05_02_000001_add_tier_to_variasis_table.php` → tambah `tier` enum(OEM/Original/Aftermarket/KW)
**Alter** — `2026_05_02_000003_fix_price_columns_precision.php` → `harga_jual` decimal(8,2)→decimal(15,2)
**Alter** — `2026_06_24_000003_add_lelangan_to_variasis_tier_enum.php` → tier + Lelangan
**Alter** — `2026_06_26_084517_add_aftermarket_subgrades_to_variasis_tier_enum.php` → tier + Aftermarket A/B/C

**Atribut final:** `id_variasi` (PK), `barcode`, `nama_variasi`, `part_number` nullable, `id_barang` (FK), `id_unit` (FK), `harga_jual` decimal(15,2), `stock` decimal(8,2) NOT NULL, `status` enum, `is_active` bool, `tier` enum(OEM/Original/Aftermarket/Aftermarket A/B/C/KW/Lelangan) nullable, timestamps.

**Model** — `app/Models/Variasi.php`
```php
class Variasi extends Model
{
    protected $primaryKey = 'id_variasi';
    protected $fillable = ['barcode', 'id_barang', 'nama_variasi', 'id_unit', 'harga_jual', 'stock', 'status', 'part_number', 'is_active', 'tier'];
    protected $casts = ['is_active' => 'boolean'];
    public function m_barang() { return $this->belongsTo(MBarang::class, 'id_barang'); }
    public function unit() { return $this->belongsTo(Unit::class, 'id_unit'); }
    public function suppliervariasi() { return $this->hasMany(SupplierVariasi::class, 'id_variasi', 'id_variasi'); }
    public function needlistItems() { return $this->hasMany(NeedlistItem::class, 'id_variasi'); }
    public function compatibilities() { return $this->hasMany(ProductVariantCompatibility::class, 'id_variasi', 'id_variasi'); }
    public function vehicleGenerations()
    {
        return $this->belongsToMany(VehicleGeneration::class, 'product_variant_compatibility', 'id_variasi', 'vehicle_generation_id')
            ->withPivot('compatibility_notes', 'is_compatible')->withTimestamps();
    }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
```

**Kelas terkait:** `NeedlistSelectionGrouper` (kunci sub-grouping via `tier`, dan `vehicleGenerations` untuk cluster kendaraan — lihat Bagian B soal `Vehicle`/`VehicleGeneration`).

---

### `m_barangs`

**Migration** — `2025_02_28_064255_create_mbarangs_table.php`
```php
Schema::create('m_barangs', function (Blueprint $table) {
    $table->id('id_barang');
    $table->string('kode_barang', 10);
    $table->string('nama_barang', 100);
    $table->unsignedBigInteger('id_kategori');
    $table->foreign('id_kategori')->references('id_kategori')->on('kategoris')->onDelete('cascade');
    $table->timestamps();
});
```

**Alter** — `2026_05_01_000002_add_description_is_active_to_m_barangs_table.php`
```php
$table->text('description')->nullable()->after('nama_barang');
$table->boolean('is_active')->default(true)->after('description');
```

**Atribut final:** `id_barang` (PK), `kode_barang`, `nama_barang`, `description` nullable, `is_active` bool, `id_kategori` (FK), timestamps.

**Model** — `app/Models/MBarang.php`
```php
class MBarang extends Model
{
    protected $primaryKey = 'id_barang';
    protected $fillable = ['kode_barang', 'nama_barang', 'id_kategori', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function variasi() { return $this->hasMany(Variasi::class, 'id_barang', 'id_barang'); }
    public function kategori() { return $this->belongsTo(Kategori::class, 'id_kategori'); }
}
```

**Kelas terkait:** `NeedlistSelectionGrouper` (level pengelompokan tertinggi), `SawPerhitungan.id_barang`.

---

### `suppliers`

**Migration** — `2025_02_28_064243_create_suppliers_table.php`
```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id('id_supplier');
    $table->string('kode_supplier',10);
    $table->string('nama_supplier',100);
    $table->string('no_telp',15);
    $table->string('alamat',50);
    $table->timestamps();
});
```
*(tidak ada migration alter)*

**Model** — `app/Models/Supplier.php`
```php
class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';
    protected $fillable = ['kode_supplier', 'nama_supplier', 'no_telp', 'alamat'];
    public function variasis() { return $this->hasMany(Variasi::class, 'id_supplier', 'id_supplier'); }
}
```

**Kelas terkait:** FK target di `saw_nilai_historis`, `saw_perhitungan_detail`, `saw_rekomendasi`.

---

### `saw_kriteria`

**Migration** — `2026_04_27_000001_create_saw_kriteria_table.php`
```php
Schema::create('saw_kriteria', function (Blueprint $table) {
    $table->id();
    $table->string('kode', 10)->unique();
    $table->string('nama', 100);
    $table->enum('jenis', ['cost', 'benefit']);
    $table->decimal('bobot', 5, 4);
    $table->string('satuan', 30)->nullable();
    $table->tinyInteger('is_active')->default(1);
    $table->integer('urutan')->default(0);
    $table->timestamps();
});
```
*(tidak ada migration alter)*

**Model** — `app/Models/SawKriteria.php`
```php
class SawKriteria extends Model
{
    protected $table = 'saw_kriteria';
    protected $fillable = ['kode', 'nama', 'jenis', 'bobot', 'satuan', 'is_active', 'urutan'];
    public function scopeAktif($query) { return $query->where('is_active', 1)->orderBy('urutan'); }
    public function isCost(): bool { return $this->jenis === 'cost'; }
    public function isBenefit(): bool { return $this->jenis === 'benefit'; }
}
```

**Kelas terkait:** `SawKriteriaController` (UC-01, CRUD + normalisasi bobot), `SawService::calculate()` (baca `SawKriteria::aktif()`), `SawService::validateBobot()`.

---

### `saw_nilai_historis`

**Migration** — `2026_04_27_000002_create_saw_nilai_historis_table.php`
```php
Schema::create('saw_nilai_historis', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('supplier_id');
    $table->unsignedBigInteger('id_variasi');              // di-drop migration berikutnya
    $table->date('periode_mulai');
    $table->date('periode_akhir');
    $table->decimal('total_biaya', 15, 2)->nullable();      // ✅ sudah di-drop, lihat migration 2026_07_25_000001
    $table->decimal('termin_pembayaran', 5, 2)->nullable();
    $table->decimal('lead_time', 5, 2)->nullable();
    $table->decimal('akurasi_kuantitas', 5, 2)->nullable();
    $table->decimal('tingkat_pemenuhan', 5, 2)->nullable();
    $table->decimal('komunikasi', 3, 1)->nullable();
    $table->integer('jumlah_transaksi')->default(0);
    $table->text('catatan')->nullable();
    $table->timestamps();
    $table->foreign('supplier_id')->references('id_supplier')->on('suppliers')->onDelete('cascade');
    $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
    $table->index(['supplier_id', 'id_variasi']);
});
```

**Alter** — `2026_04_28_030115_drop_id_variasi_from_saw_nilai_historis.php` — drop kolom + FK `id_variasi` (satu record historis per **supplier**, bukan per supplier+variasi).

**Alter** — `2026_04_30_061440_add_manual_seed_to_saw_nilai_historis.php`
```php
$table->decimal('lead_time_manual', 5, 2)->nullable()->after('lead_time');
$table->decimal('akurasi_kuantitas_manual', 5, 2)->nullable()->after('akurasi_kuantitas');
$table->decimal('tingkat_pemenuhan_manual', 5, 2)->nullable()->after('tingkat_pemenuhan');
$table->integer('jumlah_transaksi_manual')->default(0)->after('jumlah_transaksi');
```
*(kolom `*_manual` ini nyata ada di DB — dipakai seed untuk fitur sinkronisasi yang di luar scope skripsi. Tidak dimodelkan sebagai atribut Class Diagram, tapi tetap ada secara fisik di tabel)*

**Atribut final (DB):** `id`, `supplier_id` (FK), `periode_mulai`, `periode_akhir`, `termin_pembayaran`, `lead_time` + `lead_time_manual`, `akurasi_kuantitas` + `akurasi_kuantitas_manual`, `tingkat_pemenuhan` + `tingkat_pemenuhan_manual`, `komunikasi`, `jumlah_transaksi` + `jumlah_transaksi_manual`, `catatan`, timestamps. *(`total_biaya` sudah dihapus dari DB)*

**Atribut yang dimodelkan di Class Diagram (scope skripsi):** `id`, `supplier_id`, `periode_mulai`, `periode_akhir`, `termin_pembayaran` (C2), `lead_time` (C3), `akurasi_kuantitas` (C4), `tingkat_pemenuhan` (C5), `komunikasi` (C6), `jumlah_transaksi`, `catatan` — kolom `*_manual` tidak digambar.

**Model** — `app/Models/SawNilaiHistoris.php`
```php
class SawNilaiHistoris extends Model
{
    protected $table = 'saw_nilai_historis';
    protected $fillable = [
        'supplier_id', 'periode_mulai', 'periode_akhir',
        'termin_pembayaran', 'lead_time', 'lead_time_manual',
        'akurasi_kuantitas', 'akurasi_kuantitas_manual',
        'tingkat_pemenuhan', 'tingkat_pemenuhan_manual',
        'komunikasi', 'jumlah_transaksi', 'jumlah_transaksi_manual', 'catatan',
    ];
    protected $casts = ['periode_mulai' => 'date', 'periode_akhir' => 'date'];
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier'); }
}
```

**Kelas terkait:**
- `SawHistorisController` (UC-02) — CRUD manual. `store()`/`update()` mengisi kolom `*_manual` otomatis sama dengan nilai form (seed untuk fitur sinkronisasi yang tidak dibahas — tidak perlu dijelaskan di narasi kalau scope-nya manual-only).
- `SawBatchCalculator::mergeWithHistoris()` — sumber **C2, C4, C5, C6**, dan fallback **C3** (dipakai kalau `lead_time > 0`, menggantikan lead time inquiry). Ini juga tempat Aturan Bisnis #4 diimplementasikan (exclude tanpa historis).

---

### `saw_perhitungan`

**Migration** — `2026_04_27_000003_create_saw_perhitungan_table.php`
```php
Schema::create('saw_perhitungan', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('needlist_id');
    $table->unsignedBigInteger('id_variasi')->nullable();
    $table->unsignedBigInteger('id_barang')->nullable();
    $table->json('bobot_snapshot');
    $table->enum('status', ['draft', 'final'])->default('draft');
    $table->timestamp('calculated_at')->nullable();
    $table->unsignedBigInteger('calculated_by')->nullable();
    $table->timestamps();
    $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
    $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
    $table->foreign('id_barang')->references('id_barang')->on('m_barangs')->onDelete('cascade');
    $table->foreign('calculated_by')->references('id')->on('users')->onDelete('set null');
    $table->index(['needlist_id', 'id_barang']);
    $table->index(['needlist_id', 'id_variasi']);
});
```

**Alter** — `2026_05_07_111200_add_tier_key_to_saw_perhitungan.php`
```php
// Hash unik per kombinasi cluster+tier: md5(sorted variasi_ids)
$table->char('tier_key', 32)->nullable()->after('id_barang');
```

**Atribut final:** `id`, `needlist_id` (FK), `id_variasi` (FK nullable), `id_barang` (FK nullable), `tier_key` char(32) nullable, `bobot_snapshot` json, `status` enum, `calculated_at`, `calculated_by` (FK users), timestamps.

**Model** — `app/Models/SawPerhitungan.php`
```php
class SawPerhitungan extends Model
{
    protected $table = 'saw_perhitungan';
    protected $fillable = ['needlist_id', 'id_variasi', 'id_barang', 'tier_key', 'bobot_snapshot', 'status', 'calculated_at', 'calculated_by'];
    protected $casts = ['bobot_snapshot' => 'array', 'calculated_at' => 'datetime'];
    public function needlist() { return $this->belongsTo(Needlist::class, 'needlist_id'); }
    public function variasi() { return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi'); }
    public function mBarang() { return $this->belongsTo(MBarang::class, 'id_barang', 'id_barang'); }
    public function details() { return $this->hasMany(SawPerhitunganDetail::class, 'perhitungan_id'); }
    public function calculatedBy() { return $this->belongsTo(User::class, 'calculated_by'); }
    public function rekomendasi() { return $this->hasOne(SawRekomendasi::class, 'perhitungan_id'); }
}
```

**Kelas terkait:** `SawService::saveToDatabase()` — header hasil hitung (upsert per `needlist_id + id_barang + tier_key`); `SupplierRecommendationController::show()` — baca `tier_key` yang sudah dihitung.

---

### `saw_perhitungan_detail`

**Migration** — `2026_04_27_000004_create_saw_perhitungan_detail_table.php`
```php
Schema::create('saw_perhitungan_detail', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('perhitungan_id');
    $table->unsignedBigInteger('supplier_id');
    $table->decimal('nilai_c1', 15, 6)->default(0);
    $table->decimal('nilai_c2', 15, 6)->default(0);
    $table->decimal('nilai_c3', 15, 6)->default(0);
    $table->decimal('nilai_c4', 15, 6)->default(0);
    $table->decimal('nilai_c5', 15, 6)->default(0);
    $table->decimal('nilai_c6', 15, 6)->default(0);
    $table->decimal('norm_c1', 10, 6)->default(0);
    $table->decimal('norm_c2', 10, 6)->default(0);
    $table->decimal('norm_c3', 10, 6)->default(0);
    $table->decimal('norm_c4', 10, 6)->default(0);
    $table->decimal('norm_c5', 10, 6)->default(0);
    $table->decimal('norm_c6', 10, 6)->default(0);
    $table->decimal('weighted_c1', 10, 6)->default(0);
    $table->decimal('weighted_c2', 10, 6)->default(0);
    $table->decimal('weighted_c3', 10, 6)->default(0);
    $table->decimal('weighted_c4', 10, 6)->default(0);
    $table->decimal('weighted_c5', 10, 6)->default(0);
    $table->decimal('weighted_c6', 10, 6)->default(0);
    $table->decimal('nilai_vi', 10, 6)->default(0);
    $table->integer('ranking')->default(0);
    $table->tinyInteger('is_recommended')->default(0);
    $table->enum('sumber_c1', ['inquiry', 'historis', 'manual'])->default('inquiry');
    $table->enum('sumber_c3', ['inquiry', 'historis', 'manual'])->default('inquiry');
    $table->timestamps();
    $table->foreign('perhitungan_id')->references('id')->on('saw_perhitungan')->onDelete('cascade');
    $table->foreign('supplier_id')->references('id_supplier')->on('suppliers')->onDelete('cascade');
    $table->index(['perhitungan_id', 'ranking']);
});
```

**Alter** — `2026_06_08_093719_add_id_variasi_to_saw_perhitungan_detail_table.php`
```php
$table->unsignedBigInteger('id_variasi')->nullable()->after('supplier_id');
$table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
```

**Alter** — `2026_06_29_063000_add_has_historis_to_saw_perhitungan_detail_table.php`
```php
$table->boolean('has_historis')->default(false)->after('sumber_c3');
```

**Atribut final:** `id`, `perhitungan_id` (FK), `supplier_id` (FK), `id_variasi` (FK nullable), `nilai_c1..c6`, `norm_c1..c6`, `weighted_c1..c6`, `nilai_vi`, `ranking`, `is_recommended`, `sumber_c1`, `sumber_c3`, `has_historis`, timestamps.

**Model** — `app/Models/SawPerhitunganDetail.php`
```php
class SawPerhitunganDetail extends Model
{
    protected $table = 'saw_perhitungan_detail';
    protected $fillable = [
        'perhitungan_id', 'supplier_id', 'id_variasi',
        'nilai_c1', 'nilai_c2', 'nilai_c3', 'nilai_c4', 'nilai_c5', 'nilai_c6',
        'norm_c1', 'norm_c2', 'norm_c3', 'norm_c4', 'norm_c5', 'norm_c6',
        'weighted_c1', 'weighted_c2', 'weighted_c3', 'weighted_c4', 'weighted_c5', 'weighted_c6',
        'nilai_vi', 'ranking', 'is_recommended', 'sumber_c1', 'sumber_c3', 'has_historis',
    ];
    public function perhitungan() { return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier'); }
    public function variasi() { return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi'); }
}
```

**Kelas terkait:** `SawService` — seluruh method kalkulasi (`buildMatrix/normalize/weightedSum/rank`) menulis ke sini via `saveToDatabase()`. Hasil rekomendasi (`ranking=1`/`is_recommended=1`) tampil di `SupplierRecommendationController::show()`.

> ✅ **Bug sudah diperbaiki:** `SawBatchCalculator::mergeWithHistoris()` sekarang set `$row['_sumber_c3'] = 'historis';` saat nilai C3 diganti dari data historis, jadi label `sumber_c3` konsisten dengan sumber nilainya.

---

### `saw_rekomendasi`

**Migration** — `2026_04_27_000005_create_saw_rekomendasi_table.php`
```php
Schema::create('saw_rekomendasi', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('needlist_id');
    $table->unsignedBigInteger('id_variasi');
    $table->unsignedBigInteger('perhitungan_id');
    $table->unsignedBigInteger('supplier_id_saw');
    $table->unsignedBigInteger('supplier_id_dipilih')->nullable();
    $table->tinyInteger('mengikuti_rekomendasi')->default(0);
    $table->text('alasan_override')->nullable();          // ✅ sudah di-drop, lihat migration 2026_07_25_000002
    $table->decimal('nilai_vi_terpilih', 10, 6)->nullable();
    $table->timestamp('confirmed_at')->nullable();
    $table->unsignedBigInteger('confirmed_by')->nullable();
    $table->timestamps();
    $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
    $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
    $table->foreign('perhitungan_id')->references('id')->on('saw_perhitungan')->onDelete('cascade');
    $table->foreign('supplier_id_saw')->references('id_supplier')->on('suppliers')->onDelete('cascade');
    $table->foreign('supplier_id_dipilih')->references('id_supplier')->on('suppliers')->onDelete('set null');
    $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
    $table->unique(['needlist_id', 'id_variasi']);
});
```
*(tidak ada migration alter)*

**Atribut final:** `id`, `needlist_id` (FK), `id_variasi` (FK), `perhitungan_id` (FK), `supplier_id_saw` (FK), `supplier_id_dipilih` (FK set null), `mengikuti_rekomendasi`, `nilai_vi_terpilih`, `confirmed_at`, `confirmed_by` (FK users), timestamps. Unique `(needlist_id, id_variasi)`. *(`alasan_override` sudah dihapus dari DB)*

**Model** — `app/Models/SawRekomendasi.php`
```php
class SawRekomendasi extends Model
{
    protected $table = 'saw_rekomendasi';
    protected $fillable = [
        'needlist_id', 'id_variasi', 'perhitungan_id', 'supplier_id_saw', 'supplier_id_dipilih',
        'mengikuti_rekomendasi', 'alasan_override', 'nilai_vi_terpilih', 'confirmed_at', 'confirmed_by',
    ];
    protected $casts = ['confirmed_at' => 'datetime'];
    public function needlist() { return $this->belongsTo(Needlist::class, 'needlist_id'); }
    public function variasi() { return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi'); }
    public function perhitungan() { return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id'); }
    public function supplierSaw() { return $this->belongsTo(Supplier::class, 'supplier_id_saw', 'id_supplier'); }
    public function supplierDipilih() { return $this->belongsTo(Supplier::class, 'supplier_id_dipilih', 'id_supplier'); }
    public function confirmedBy() { return $this->belongsTo(User::class, 'confirmed_by'); }
}
```

**Kelas terkait:** `SupplierConfirmationController::recordSawRekomendasi()` (UC-04) — log/audit hasil konfirmasi (siapa direkomendasikan vs siapa dipilih), ditulis **setelah** `SupplierInquiryItem::status='selected'` di-set. Tidak mempengaruhi kalkulasi berikutnya.

```php
// SupplierConfirmationController::recordSawRekomendasi() — inti logikanya
SawRekomendasi::updateOrCreate(
    ['needlist_id' => $needlistId, 'id_variasi' => $idVariasi],
    [
        'perhitungan_id'        => $detail->perhitungan_id,
        'supplier_id_saw'       => $detail->supplier_id,
        'supplier_id_dipilih'   => $supplierIdDipilih,
        'mengikuti_rekomendasi' => $mengikuti ? 1 : 0,
        'nilai_vi_terpilih'     => $nilaiVi,
        'confirmed_at'          => now(),
        'confirmed_by'          => Auth::id(),
    ]
);
```

---

<a id="bagian-b"></a>
## Bagian B — Tabel Pendukung (di luar scope Class Diagram)

Tabel-tabel ini **dipakai secara teknis** oleh alur SAW (terutama sumber data C1/C3 dan clustering kendaraan), tapi **tidak digambar** di Class Diagram Bab 4 sesuai keputusan scope final. Dicatat ringkas di sini supaya alur datanya tetap bisa ditelusuri kalau dibutuhkan.

| Tabel | Peran teknis | Kenapa di luar scope |
|---|---|---|
| `supplier_inquiries` | Header permintaan penawaran per supplier (`needlist_id`, `supplier_id`, `status`) | Bagian alur pengadaan (permintaan penawaran) di luar Batasan Masalah |
| `supplier_inquiry_items` | **Sumber C1 (harga_penawaran) dan C3 (estimasi_pengiriman)** yang dipakai `SawBatchCalculator::getInquiryDataByCluster()`; kolom `status='selected'` juga penggerak nyata UC-04 | Datanya di-*copy* ke perhitungan, bukan direferensikan lewat FK langsung dari `saw_perhitungan_detail` |
| `vehicles`, `vehicle_generations`, `product_variant_compatibility` | Dipakai `NeedlistSelectionGrouper` untuk cluster kendaraan dominan (`dominantManufacturer()`, `clusterByVehicleGeneration()`) | Tidak ada di daftar 10 model final — kalau mau bahas mekanisme cluster kendaraan secara detail, perlu didiskusikan ulang cakupannya |
| `supplier_barang` (model `SupplierVariasi`) | Referensi harga default/statis, dipakai form respons inquiry (`fillModal()`) sebagai saran, BUKAN sumber harga SAW | Bukan bagian algoritma SAW — justru relevan sebagai pembanding "kenapa harga master tidak dipakai langsung" (lihat diskusi C1 sebelumnya), tapi bukan entity yang digambar |

---

<a id="controller-view"></a>
## Controller & View per Use Case

### UC-01 — Kelola Kriteria dan Bobot (`SawKriteriaController`)
**Route:** `procurement/saw-kriteria/*` → `saw.kriteria.*`
**View:** `saw_kriteria/index.blade.php`, `saw_kriteria/form.blade.php`

### UC-02 — Kelola Kinerja Supplier (`SawHistorisController`)
**Route:** `procurement/saw-historis/*` → `saw.historis.*`
**View:** `saw_historis/index.blade.php`, `saw_historis/form.blade.php`

### UC-03 — Pemilihan Supplier (`SupplierRecommendationController`)
**Route:** `procurement/pemilihan-supplier/*` → `pemilihan-supplier.*` (`index`, `ringkasan`, `show`)
**View:** `pemilihan_supplier/index.blade.php`, `ringkasan.blade.php`, `show.blade.php`

### UC-04 — Konfirmasi Supplier Terpilih (`SupplierConfirmationController`)
**Route:** `POST /needlist/{needlist}/save/selection` → `supplier.selection.save`
**View:** form checkbox pilihan ada di `pemilihan_supplier/show.blade.php` (bagian bawah, submit ke route ini)

---

<a id="rekap-pending"></a>
## Rekap Perubahan yang Sudah Dieksekusi (2026-07-26)

| # | Tabel/Kelas | Perubahan | Status |
|---|---|---|---|
| 1 | `saw_nilai_historis.total_biaya` | Drop kolom (dulu tidak pernah dipakai di `$fillable`/kode/dokumen manapun) | ✅ Selesai — di-drop manual oleh user (setelah backup), dicatat formal lewat migration `2026_07_25_000001_drop_total_biaya_from_saw_nilai_historis.php` |
| 2 | `saw_rekomendasi.alasan_override` | Drop kolom (dulu selalu `null`, fitur terkait sudah dihapus dari kode) | ✅ Selesai — di-drop manual oleh user, dicatat formal lewat migration `2026_07_25_000002_drop_alasan_override_from_saw_rekomendasi.php`. Referensi di `SawRekomendasi::$fillable`, `SupplierConfirmationController::recordSawRekomendasi()`, dan `saw_laporan/index.blade.php` sudah dibersihkan |
| 3 | `SawBatchCalculator::mergeWithHistoris()` | Tambah `$row['_sumber_c3'] = 'historis';` saat C3 diganti dari data historis | ✅ Selesai — bug label `sumber_c3` sudah diperbaiki |
| 4 | 4 controller (`SawKriteriaController`, `SawHistorisController`, `PemilihanSupplierController`, `SupplierSelectionController`) | Pindah dari `App\Http\Controllers\Procurement` ke `App\Http\Controllers\SupplierSelection` (folder sempat bernama `Saw`, diganti final ke `SupplierSelection`); `PemilihanSupplierController`→`SupplierRecommendationController`, `SupplierSelectionController`→`SupplierConfirmationController` | ✅ Selesai — supaya modul skripsi yang "berdiri sendiri" tidak tampak menumpang di folder sistem pengadaan existing. Route name tidak berubah (`saw.*`, `pemilihan-supplier.*`, `supplier.selection.save`), diverifikasi lewat `route:list` |

Migration baru dibuat dengan guard `Schema::hasColumn()` supaya aman dijalankan di database mana pun (sudah di-drop manual atau belum) — kalau nanti ada yang jalankan `migrate:fresh` di environment lain, skema akhirnya tetap konsisten (kolom langsung tidak ada). Semua file sudah dicek `php -l` (pakai PHP 8.3, bukan PHP 7.4 yang ada di PATH default — lihat catatan versi PHP di awal sesi) dan lolos tanpa error.

**Keputusan lain yang dibahas tapi tidak dieksekusi:** sempat dipertimbangkan untuk melepas prefix "Saw" dari 5 model (`SawKriteria`, `SawNilaiHistoris`, `SawPerhitungan`, `SawPerhitunganDetail`, `SawRekomendasi`) supaya konsisten dengan controller yang baru di-rename. Diputuskan **tidak** — beda masalah dari controller (yang direfactor karena isu struktural namespace), model sudah flat di `app/Models/` tanpa masalah itu, dan prefix "Saw" justru informatif. Blast radius rename model juga jauh lebih besar (semua service, controller lain, belasan view, seeder).

⏳ **Sedang didiskusikan, belum dieksekusi:** menghapus total fitur "Hitung Ulang Rekomendasi" (`SupplierSelectionSawController::hitungSemua()`, route `pemilihan-supplier.rekomendasi-semua`, tombol di `pemilihan_supplier/show.blade.php`) supaya `SupplierRecommendationController::show()` selalu auto-hitung ulang setiap dibuka tanpa tombol manual — sesuai Aturan Bisnis #3. Terhambat isu teknis: recalculate tanpa syarat berisiko menghapus `SawRekomendasi` yang sudah dikonfirmasi lewat cascade delete (`saw_rekomendasi.perhitungan_id` `ON DELETE CASCADE` ke `saw_perhitungan`). Opsi yang diusulkan: skip kelompok yang sudah dikonfirmasi (cek `SawPerhitungan::whereHas('rekomendasi')`), bukan skip berdasar "sudah pernah dihitung" seperti sekarang.

---

<a id="alur-kelas"></a>
## Alur Kelas Ringkas (Controller → Service)

```
SupplierRecommendationController::show($id)                 [UC-03]
  ├─ NeedlistSelectionGrouper::buildGroups()            → pengelompokan
  ├─ SawBatchCalculator::calculateForNeedlist()          → orkestrasi per kelompok (auto-run)
  │    ├─ getInquiryDataByCluster()                      → baca supplier_inquiry_items (Bagian B)
  │    ├─ mergeWithHistoris()                            → baca saw_nilai_historis, Aturan Bisnis #4
  │    └─ SawService::calculate()
  │         ├─ buildMatrix() / normalize() / weightedSum() / rank()
  │         └─ saveToDatabase()                          → tulis saw_perhitungan + saw_perhitungan_detail
  └─ (tampil di pemilihan_supplier/show.blade.php)

SupplierConfirmationController::saveSelection()            [UC-04]
  ├─ update SupplierInquiryItem::status = 'selected'     (Bagian B, penggerak proses lanjutan)
  └─ recordSawRekomendasi()
       └─ SawRekomendasi::updateOrCreate(...)            → log/audit, TIDAK mempengaruhi kalkulasi
```
