# Catatan Skema Database — Modul Pemilihan Supplier (SAW)

> Dokumen referensi untuk skripsi. Scope FINAL (disepakati bersama dosen pembimbing): **Sistem Pemilihan Supplier menggunakan metode SAW**, berdiri sendiri secara narasi, mencakup UC-01 (Kelola Kriteria & Bobot), UC-02 (Kelola Kinerja Supplier), UC-03 (Pemilihan Supplier — auto-run), UC-04 (Konfirmasi Supplier Terpilih). Login & master data = prasyarat/given.
>
> Struktur dokumen ini dipecah jadi 2 bagian: **(A)** 11 tabel yang masuk Class Diagram Bab 4 — detail lengkap (migration + model + kelas terkait). **(B)** tabel pendukung yang dipakai secara teknis tapi TIDAK digambar di Class Diagram — ringkas saja, untuk pemahaman alur data.
>
> Status per 2026-07-26: **4 perubahan yang disepakati sudah dieksekusi** (drop kolom, fix bug `sumber_c3`, rename+pindah 4 controller ke `App\Http\Controllers\SupplierSelection`). Masih ada 1 hal yang sedang didiskusikan (penghapusan total fitur "Hitung Ulang"), belum dieksekusi.
>
> Status per 2026-08-05: **skema dirombak jadi dinamis per-kriteria** — tabel baru `saw_nilai_historis_detail` (jadi tabel ke-11 di Class Diagram, keputusan sadar karena sekarang punya relasi nyata ke `saw_nilai_historis` & `saw_kriteria`) menampung SEMUA nilai kriteria C2 dst (bukan cuma kriteria custom); 8 kolom fixed lama di `saw_nilai_historis` sudah di-drop (data lama sudah dimigrasikan, bukan hilang). `saw_perhitungan_detail` juga dirombak dari 18 kolom `nilai_c1..weighted_c6` jadi satu kolom JSON `rincian_kriteria`, mengikuti pola `bobot_snapshot` yang sudah ada. Fitur "Normalisasi Otomatis" bobot kriteria (`SawKriteriaController::normalize()`) **dihapus total** atas permintaan user (method, route, tombol, test — bukan cuma dikecualikan dari diagram). Lihat Rekap Perubahan di bagian akhir untuk daftar lengkap.

---

## Daftar Isi
1. [Bagian A — Tabel yang Masuk Class Diagram (11 tabel final)](#bagian-a)
2. [Bagian B — Tabel Pendukung (di luar scope Class Diagram)](#bagian-b)
3. [Controller & View per Use Case](#controller-view)
4. [Rekap Perubahan yang Sudah Dieksekusi](#rekap-pending)
5. [Alur Kelas Ringkas](#alur-kelas)

---

<a id="bagian-a"></a>
## Bagian A — Tabel yang Masuk Class Diagram (11 tabel final)

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
    $table->decimal('termin_pembayaran', 5, 2)->nullable(); // ✅ sudah di-drop, lihat migration 2026_08_05_100200
    $table->decimal('lead_time', 5, 2)->nullable();         // ✅ sudah di-drop
    $table->decimal('akurasi_kuantitas', 5, 2)->nullable(); // ✅ sudah di-drop
    $table->decimal('tingkat_pemenuhan', 5, 2)->nullable(); // ✅ sudah di-drop
    $table->decimal('komunikasi', 3, 1)->nullable();        // ✅ sudah di-drop
    $table->integer('jumlah_transaksi')->default(0);
    $table->text('catatan')->nullable();
    $table->timestamps();
    $table->foreign('supplier_id')->references('id_supplier')->on('suppliers')->onDelete('cascade');
    $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
    $table->index(['supplier_id', 'id_variasi']);
});
```

**Alter** — `2026_04_28_030115_drop_id_variasi_from_saw_nilai_historis.php` — drop kolom + FK `id_variasi` (satu record historis per **supplier**, bukan per supplier+variasi).

**Alter** — `2026_04_30_061440_add_manual_seed_to_saw_nilai_historis.php` — tambah `lead_time_manual`, `akurasi_kuantitas_manual`, `tingkat_pemenuhan_manual`, `jumlah_transaksi_manual`. *(kolom `*_manual` untuk C3/C4/C5 ikut di-drop bersama kolom utamanya di migrasi 2026-08-05 di bawah — sudah tidak relevan setelah nilai kriteria dipindah ke tabel `saw_nilai_historis_detail` yang cuma punya satu kolom `nilai`, tanpa duality manual/aktual)*

**Alter** — `2026_08_05_100200_migrate_kinerja_columns_to_saw_nilai_historis_detail.php` — **perombakan besar (2026-08-05)**: 8 kolom (`termin_pembayaran`, `lead_time`+`_manual`, `akurasi_kuantitas`+`_manual`, `tingkat_pemenuhan`+`_manual`, `komunikasi`) di-drop dari tabel ini. Isinya dimigrasikan (bukan dihapus) ke tabel baru `saw_nilai_historis_detail` — satu baris per (historis, kriteria). Alasan: kolom fixed per-kriteria tidak bisa mengikuti jumlah kriteria yang dinamis (waktu itu C7 "Garansi Produk" baru ditambah lewat UC-01 dan tidak ada tempat menyimpan nilainya). `jumlah_transaksi`/`jumlah_transaksi_manual` **tidak ikut pindah** — itu metadata jumlah transaksi (dipakai sebagai bobot di `SupplierPerformanceService`, bukan "nilai kriteria"), tetap kolom langsung di sini.

**Atribut final (DB):** `id`, `supplier_id` (FK), `periode_mulai`, `periode_akhir`, `jumlah_transaksi`, `jumlah_transaksi_manual`, `catatan`, timestamps. *(`total_biaya` dan 8 kolom nilai kriteria C2-C6 sudah dihapus dari DB — lihat tabel `saw_nilai_historis_detail` untuk nilai kriterianya sekarang)*

**Model** — `app/Models/SawNilaiHistoris.php`
```php
class SawNilaiHistoris extends Model
{
    protected $table = 'saw_nilai_historis';
    protected $fillable = [
        'supplier_id', 'periode_mulai', 'periode_akhir',
        'jumlah_transaksi', 'jumlah_transaksi_manual', 'catatan',
    ];
    protected $casts = ['periode_mulai' => 'date', 'periode_akhir' => 'date'];
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier'); }
    public function details() { return $this->hasMany(SawNilaiHistorisDetail::class, 'historis_id'); }
}
```

**Kelas terkait:**
- `SawHistorisController` (UC-02) — CRUD manual. `store()`/`update()` memvalidasi & menyimpan nilai tiap kriteria aktif (C2 dst) lewat `syncNilaiKriteria()` ke tabel `saw_nilai_historis_detail`, bukan lagi kolom langsung di tabel ini.
- `SawBatchCalculator::mergeWithHistoris()` — baca semua kriteria aktif di luar C1 dari `->details` (relasi ke `saw_nilai_historis_detail`), termasuk fallback **C3** (dipakai kalau nilainya `> 0`, menggantikan lead time inquiry). Ini juga tempat Aturan Bisnis #4 diimplementasikan (exclude tanpa historis).

---

### `saw_nilai_historis_detail` *(tabel baru, 2026-08-05)*

Tabel generik "nilai kriteria per historis" — menggantikan kolom fixed C2-C6 yang sebelumnya ada di `saw_nilai_historis`. Satu baris = satu nilai kriteria untuk satu record historis supplier. Dipakai untuk SEMUA kriteria di luar C1 (yang selalu dari `SupplierInquiry`, tidak pernah diinput manual) — baik C2-C6 (kriteria bawaan) maupun C7 dst (kriteria custom yang ditambah lewat UC-01).

**Migration** — `2026_08_05_100000_create_saw_nilai_historis_detail_table.php` (dibuat untuk kriteria custom C7 dst, lalu diperluas cakupannya jadi semua C2-Cn oleh migrasi `2026_08_05_100200_migrate_kinerja_columns_to_saw_nilai_historis_detail.php` di atas)
```php
Schema::create('saw_nilai_historis_detail', function (Blueprint $table) {
    $table->id();
    $table->foreignId('historis_id')->constrained('saw_nilai_historis')->cascadeOnDelete();
    $table->foreignId('kriteria_id')->constrained('saw_kriteria')->cascadeOnDelete();
    $table->decimal('nilai', 15, 4);
    $table->timestamps();
    $table->unique(['historis_id', 'kriteria_id']);
});
```

**Atribut final:** `id`, `historis_id` (FK → `saw_nilai_historis`, cascade delete), `kriteria_id` (FK → `saw_kriteria`, cascade delete), `nilai` decimal(15,4), timestamps. Unique `(historis_id, kriteria_id)` — satu kriteria cuma boleh punya satu nilai per record historis.

**Kenapa cuma satu kolom `nilai` (tidak ada `nilai_manual`/seed terpisah):** desain awal sempat mempertimbangkan duality nilai-aktif vs nilai-seed-manual (mengikuti pola lama `lead_time`/`lead_time_manual`), tapi diputuskan disederhanakan jadi murni satu nilai manual — karena satu-satunya fitur yang butuh duality itu (`SupplierPerformanceService`, sinkronisasi otomatis dari transaksi PO) sudah dikonfirmasi **dead code** (tidak ada route yang memanggilnya). Method itu tetap disesuaikan supaya tidak error kalau suatu saat dipanggil, tapi sumber "seed"-nya sekarang dibaca dari `nilai` yang ada, bukan kolom terpisah.

**Model** — `app/Models/SawNilaiHistorisDetail.php`
```php
class SawNilaiHistorisDetail extends Model
{
    protected $table = 'saw_nilai_historis_detail';
    protected $fillable = ['historis_id', 'kriteria_id', 'nilai'];
    public function historis() { return $this->belongsTo(SawNilaiHistoris::class, 'historis_id'); }
    public function kriteria() { return $this->belongsTo(SawKriteria::class, 'kriteria_id'); }
}
```

**Kelas terkait:**
- `SawHistorisController` (UC-02) — `syncNilaiKriteria()` upsert/hapus baris di sini sesuai input form. Form render field dinamis per kriteria aktif (`kriteriaDinamisAktif()`), C2 & C6 tetap dropdown skala 1-5 (bukan angka bebas), sisanya input desimal.
- `SawBatchCalculator::mergeWithHistoris()` — baca nilai tiap kriteria lewat relasi `SawNilaiHistoris::details` untuk dimasukkan ke matriks keputusan SAW.
- `SupplierLeadTimeResolver` — baca nilai kriteria C3 dari sini untuk estimasi lead time di tabel Pemilihan Supplier.
- `SawKriteriaController::destroy()` — cek keberadaan baris di tabel ini (per `kriteria_id`) sebagai syarat sebelum mengizinkan hapus kriteria custom secara permanen (kalau sudah ada data, hapus ditolak — arahkan ke nonaktifkan saja).

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
    $table->decimal('nilai_c1', 15, 6)->default(0);   // ✅ sudah di-drop, lihat migration 2026_08_05_100100
    $table->decimal('nilai_c2', 15, 6)->default(0);   // ✅ sudah di-drop
    // ... nilai_c3..c6, norm_c1..c6, weighted_c1..c6 — 18 kolom, semua sudah di-drop
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

**Alter** — `2026_08_05_100100_convert_saw_perhitungan_detail_to_dynamic.php` — **perombakan besar (2026-08-05)**: 18 kolom wide (`nilai_c1..c6`, `norm_c1..c6`, `weighted_c1..c6`) di-drop, diganti SATU kolom `rincian_kriteria` (json), berisi array per kode kriteria: `{kode: {nilai, norm, weighted}}`. Ini mengikuti pola yang **sudah ada** di `saw_perhitungan.bobot_snapshot` (json, `casts => array`) — bukan pendekatan baru, cuma diterapkan konsisten ke tabel saudaranya. Alasan: kolom wide per-kriteria berarti skema tabel harus diubah lagi setiap kali kriteria SAW berubah (tambah C7 dst), dan baris-baris lama jadi tidak bisa merepresentasikan kriteria yang berbeda dari saat itu. Kolom JSON per-baris adalah snapshot literal (bukan referensi hidup ke `saw_kriteria`), jadi laporan lama tidak pernah rusak walau kriteria yang dipakai saat itu belakangan dihapus/diubah.

**Atribut final:** `id`, `perhitungan_id` (FK), `supplier_id` (FK), `id_variasi` (FK nullable), `rincian_kriteria` json, `nilai_vi`, `ranking`, `is_recommended`, `sumber_c1`, `sumber_c3`, `has_historis`, timestamps. *(`sumber_c1`/`sumber_c3` tetap kolom terpisah — cuma C1 & C3 yang punya semantik sumber inquiry-vs-historis, jadi tidak digeneralisasi ke kolom lain)*

**Model** — `app/Models/SawPerhitunganDetail.php`
```php
class SawPerhitunganDetail extends Model
{
    protected $table = 'saw_perhitungan_detail';
    protected $fillable = [
        'perhitungan_id', 'supplier_id', 'id_variasi',
        'rincian_kriteria', // ['C1' => ['nilai'=>x,'norm'=>x,'weighted'=>x], 'C2' => [...], ...]
        'nilai_vi', 'ranking', 'is_recommended', 'sumber_c1', 'sumber_c3', 'has_historis',
    ];
    protected $casts = [
        'rincian_kriteria' => 'array',
        'is_recommended' => 'boolean',
        'has_historis' => 'boolean',
    ];
    public function perhitungan() { return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier'); }
    public function variasi() { return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi'); }

    public function nilai(string $kode): float    { return (float) ($this->rincian_kriteria[$kode]['nilai'] ?? 0); }
    public function norm(string $kode): float      { return (float) ($this->rincian_kriteria[$kode]['norm'] ?? 0); }
    public function weighted(string $kode): float  { return (float) ($this->rincian_kriteria[$kode]['weighted'] ?? 0); }
}
```

**Kelas terkait:** `SawService` — seluruh method kalkulasi (`buildMatrix/normalize/weightedSum/rank`) menulis ke sini via `saveToDatabase()` (loop generik per kriteria aktif, bukan hardcode C1-C6 lagi). Hasil rekomendasi (`ranking=1`/`is_recommended=1`) tampil di `SupplierRecommendationController::show()`. `SupplierSelectionSawController::detailSaw()` & view `saw_laporan/index.blade.php` (modal breakdown) baca `rincian_kriteria` untuk render tabel Xij/Rij/W×R per kriteria, otomatis mengikuti berapa pun jumlah kriteria aktif.

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

Catatan (2026-08-05): fitur "Normalisasi Otomatis" (dulu `+normalize(): RedirectResponse`, route `saw.kriteria.normalize`, tombol di halaman index) **dihapus total** atas permintaan user — bukan cuma dikecualikan dari diagram. Total bobot kriteria aktif tetap ditampilkan (banner hijau/kuning), tapi penyesuaian ke 100% sekarang manual lewat form edit tiap kriteria.

`destroy()` sekarang punya pengaman: kriteria inti (C1-C6) tidak bisa dihapus sama sekali (kode-nya dipakai langsung sebagai sumber data spesifik di `SawBatchCalculator`); kriteria custom (C7 dst) tidak bisa dihapus kalau sudah ada baris di `saw_nilai_historis_detail` untuk kriteria itu. Kedua kasus diarahkan untuk nonaktifkan (`is_active=0`) saja — reversibel, tidak menyentuh riwayat.

### UC-02 — Kelola Kinerja Supplier (`SawHistorisController`)
**Route:** `procurement/saw-historis/*` → `saw.historis.*`
**View:** `saw_historis/index.blade.php`, `saw_historis/form.blade.php`

Catatan (2026-08-05): form sekarang render field secara **dinamis** mengikuti kriteria aktif (`kriteriaDinamisAktif()` = semua kriteria aktif di luar C1), bukan lagi 5 field hardcode C2-C6. C2 & C6 tetap dropdown skala 1-5 (label termin/komunikasi persis seperti sebelumnya), kriteria lain (C3, C4, C5, C7 dst) input desimal generik. Kalau ada kriteria baru ditambah lewat UC-01, field-nya otomatis muncul di form ini tanpa perlu ubah kode lagi.

### UC-03 — Pemilihan Supplier (`SupplierRecommendationController`)
**Route:** `procurement/pemilihan-supplier/*` → `pemilihan-supplier.*` (`index`, `ringkasan`, `show`) — route `rekomendasi-semua` (tombol "Hitung Ulang" manual) sudah dihapus
**View:** `pemilihan_supplier/index.blade.php`, `ringkasan.blade.php`, `show.blade.php`

Catatan (2026-08-05): tidak ada lagi tombol manual untuk memicu hitung ulang. `show()` selalu auto-hitung lewat `SawBatchCalculator::determineSkipTierKeys()` + `calculateForNeedlist()` — kelompok yang sudah dikonfirmasi (UC-04) terkunci permanen, kelompok yang belum dikonfirmasi dihitung ulang otomatis kalau ada perubahan harga/kinerja supplier/bobot kriteria sejak kalkulasi terakhir. Ini tetap satu use case yang sama (bukan use case baru) — tidak ada aksi aktor yang berubah, cuma detail implementasi "otomatis" yang diperjelas.

Catatan tambahan (2026-08-05): `index()` sekarang menampilkan badge progres pemilihan per needlist (`statusPemilihanDariGroups()`) — "Belum Ada Konfirmasi" / "Belum Dipilih" / "Sebagian Dipilih" / "Sudah Dipilih", dihitung per KELOMPOK (bukan per variasi, karena satu kelompok bisa punya beberapa variasi yang saling bersaing — cuma satu yang dipilih, sisanya wajar tetap `pending`). `show()` juga memakai status yang sama untuk menampilkan peringatan konfirmasi (bukan blokir) kalau user mau mengubah needlist yang sudah "Sudah Dipilih" — sesuai Aturan Bisnis #5, keputusan tetap fleksibel sampai PO terbit.

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

✅ **Sudah dieksekusi (2026-08-05):** fitur "Hitung Ulang Rekomendasi" dihapus total — `SupplierSelectionSawController::hitungSemua()`, route `pemilihan-supplier.rekomendasi-semua`, tombol & JS pendukung (`applyRekomendasi()`, `#btnRekomendasiSemua`) di `pemilihan_supplier/show.blade.php`/`_scripts.blade.php` semua dihapus. Sebagai gantinya, `SupplierRecommendationController::show()` sekarang selalu auto-hitung ulang lewat `SawBatchCalculator::determineSkipTierKeys()` — kelompok yang sudah dikonfirmasi (`SawPerhitungan::whereHas('rekomendasi')`) selalu dikunci, kelompok yang belum dikonfirmasi dihitung ulang otomatis HANYA kalau data sumbernya (harga penawaran, nilai kinerja supplier, bobot kriteria) berubah sejak kalkulasi terakhir — persis opsi yang diusulkan di catatan lama ini, plus tambahan dirty-check supaya tidak boros hitung ulang tanpa alasan. Tombol "Detail Needlist" di halaman yang sama juga dihapus atas permintaan user (simplifikasi UI, cuma sisa "Kembali ke Ringkasan").

---

## Rekap Perubahan yang Sudah Dieksekusi (2026-08-05)

Latar belakang: user menambah kriteria ke-7 ("C7 — Garansi Produk") lewat UC-01, lalu menemukan form Kinerja Supplier (UC-02) belum bisa menampung nilainya karena masih hardcode C2-C6. Perbaikan ini meluas jadi perombakan skema, bukan cuma tambal field.

| # | Tabel/Kelas | Perubahan | Status |
|---|---|---|---|
| 1 | `saw_nilai_historis_detail` | **Tabel baru** — penyimpanan generik nilai kriteria (historis_id, kriteria_id, nilai). Awalnya cuma untuk kriteria custom (C7 dst), migrasi kedua di hari yang sama memperluasnya menampung C2-C6 juga | ✅ Selesai — jadi tabel ke-11 di Class Diagram (keputusan sadar: sekarang punya relasi nyata ke `saw_nilai_historis` & `saw_kriteria`, bukan cuma pendukung teknis) |
| 2 | `saw_nilai_historis` | 8 kolom fixed (`termin_pembayaran`, `lead_time`+`_manual`, `akurasi_kuantitas`+`_manual`, `tingkat_pemenuhan`+`_manual`, `komunikasi`) di-drop, datanya dimigrasikan ke `saw_nilai_historis_detail` (bukan hilang) | ✅ Selesai — migration `2026_08_05_100200_migrate_kinerja_columns_to_saw_nilai_historis_detail.php`, terverifikasi 14 record lama → 71 baris detail |
| 3 | `saw_perhitungan_detail` | 18 kolom wide (`nilai_c1..c6`, `norm_c1..c6`, `weighted_c1..c6`) diganti 1 kolom JSON `rincian_kriteria`, mengikuti pola `bobot_snapshot` yang sudah ada di `saw_perhitungan` | ✅ Selesai — migration `2026_08_05_100100_convert_saw_perhitungan_detail_to_dynamic.php`. Konsekuensi: baris lama (sebelum migrasi) kehilangan breakdown per-kriteria (jadi 0), tapi `nilai_vi`/`ranking` tidak tersentuh — bisa dipulihkan dengan klik "Hitung Ulang" |
| 4 | `SawBatchCalculator::mergeWithHistoris()` | Loop terpisah C2-C6 (kolom fixed) + kriteria custom disatukan jadi satu loop generik atas semua kriteria aktif di luar C1 | ✅ Selesai |
| 5 | `SupplierPerformanceService::recalculate()` | Disesuaikan baca/tulis lewat `saw_nilai_historis_detail` (bukan kolom `*_manual` yang sudah dihapus) — method ini tetap **dead code**, tidak ada route yang memanggilnya | ✅ Selesai, cuma supaya tidak error kalau dipanggil |
| 6 | `SawKriteriaController::destroy()` | Tambah pengaman: C1-C6 tidak bisa dihapus sama sekali; kriteria custom yang sudah punya data di `saw_nilai_historis_detail` juga tidak bisa dihapus. Diarahkan nonaktifkan saja | ✅ Selesai |
| 7 | `SawKriteriaController::normalize()` | **Dihapus total** (method, route `saw.kriteria.normalize`, tombol "Normalisasi Otomatis" di index, test terkait) atas permintaan user — bukan cuma dikecualikan dari Class Diagram | ✅ Selesai |
| 8 | `SupplierLeadTimeResolver::resolveDays()` | Baca lead time (C3) dari `saw_nilai_historis_detail`, bukan lagi `$historis->lead_time` | ✅ Selesai |
| 9 | `pemilihan_supplier/show.blade.php` | Tombol "Hitung Ulang Rekomendasi" & "Detail Needlist" dihapus dari header halaman — tinggal "Kembali ke Ringkasan" | ✅ Selesai, atas permintaan user |
| 10 | `SawBatchCalculator::determineSkipTierKeys()` | Method baru — pengganti tombol "Hitung Ulang" manual. Kelompok yang sudah dikonfirmasi selalu dikunci; kelompok yang belum dikonfirmasi dihitung ulang otomatis kalau harga penawaran, nilai kinerja supplier, atau bobot kriteria berubah sejak kalkulasi terakhir; kalau tidak ada perubahan, tetap di-skip (tidak boros) | ✅ Selesai — 6 test baru (`SawSkipTierKeysTest.php`) |
| 11 | `SupplierSelectionSawController::hitungSemua()` | Dihapus total (method, route `pemilihan-supplier.rekomendasi-semua`, JS `applyRekomendasi()`/`#btnRekomendasiSemua`) — sudah jadi dead code sejak tombolnya dihapus, dan fungsinya sudah digantikan `determineSkipTierKeys()` | ✅ Selesai |
| 12 | `SupplierRecommendationController::statusPemilihanDariGroups()` | Method baru — hitung progres pemilihan per needlist per KELOMPOK (bukan per variasi). Dipakai `index()` buat badge 4 status di `pemilihan_supplier/index.blade.php`, dan `show()` buat deteksi "sudah final" | ✅ Selesai — 3 test baru (2 di `SupplierRecommendationTest.php` termasuk regresi kelompok alternatif-bersaing) |
| 13 | `show.blade.php` / `_scripts.blade.php` | Peringatan SweetAlert (bukan blokir) sebelum "Simpan Pilihan" kalau needlist sudah "Sudah Dipilih" lengkap sebelumnya — tetap bisa diedit sampai PO terbit, cuma tambah kesadaran sebelum menimpa | ✅ Selesai |

Semua perubahan sudah lolos `php -l`, `php artisan migrate` (terverifikasi data lama pindah utuh), dan full test suite (249 lolos, 1 gagal — bug pre-existing di modul Sales/Penerimaan yang tidak terkait sama sekali).

---

<a id="alur-kelas"></a>
## Alur Kelas Ringkas (Controller → Service)

```
SupplierRecommendationController::show($id)                 [UC-03]
  ├─ NeedlistSelectionGrouper::buildGroups()            → pengelompokan
  ├─ SawBatchCalculator::determineSkipTierKeys()          → kelompok mana yang AMAN dilewati:
  │                                                          sudah dikonfirmasi (kunci permanen), ATAU belum
  │                                                          dikonfirmasi tapi harga/kinerja/bobot belum berubah
  ├─ SawBatchCalculator::calculateForNeedlist($needlist, $skipTierKeys)   → orkestrasi per kelompok (auto-run)
  │    ├─ getInquiryDataByCluster()                      → baca supplier_inquiry_items (Bagian B), sumber C1
  │    ├─ mergeWithHistoris()                            → baca saw_nilai_historis + ->details (generik
  │    │                                                     semua kriteria aktif di luar C1), Aturan Bisnis #4
  │    └─ SawService::calculate()
  │         ├─ buildMatrix() / normalize() / weightedSum() / rank()   (generik per kriteria, tidak hardcode C1-C6)
  │         └─ saveToDatabase()                          → tulis saw_perhitungan + saw_perhitungan_detail
  │                                                          (rincian_kriteria json, bukan kolom c1-c6 lagi)
  └─ (tampil di pemilihan_supplier/show.blade.php — tanpa tombol "Hitung Ulang" manual lagi)

SupplierConfirmationController::saveSelection()            [UC-04]
  ├─ update SupplierInquiryItem::status = 'selected'     (Bagian B, penggerak proses lanjutan)
  └─ recordSawRekomendasi()
       └─ SawRekomendasi::updateOrCreate(...)            → log/audit, TIDAK mempengaruhi kalkulasi

SawHistorisController::store()/update()                     [UC-02]
  └─ syncNilaiKriteria()
       └─ SawNilaiHistorisDetail::updateOrCreate(...)    → satu baris per kriteria aktif yang diisi di form
```
