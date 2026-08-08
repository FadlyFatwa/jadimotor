# Daftar Kelas — Modul Pemilihan Supplier (SAW)

> Referensi untuk class diagram skripsi. Scope FINAL sudah disepakati bersama dosen pembimbing (lihat catatan di bawah) — dokumen ini disusun ulang supaya persis mengikuti daftar itu. Atribut & method sudah diverifikasi langsung ke kode (visibility `+`/`-` dan signature parameter sesuai implementasi asli).

## Scope Skripsi (acuan)

Modul berdiri sendiri: **Sistem Pemilihan Supplier menggunakan metode SAW**. Empat use case:
- UC-01 Kelola Kriteria dan Bobot Kepentingan
- UC-02 Kelola Kinerja Supplier
- UC-03 Pemilihan Supplier (auto-run saat halaman dibuka)
- UC-04 Konfirmasi Supplier Terpilih

Login & pengelolaan master data (barang, supplier) = prasyarat/given, bukan UC tersendiri.

**Aturan bisnis final (5 poin):**
1. Rekomendasi ditampilkan untuk kelompok item dengan >1 alternatif supplier; kelompok dengan 1 alternatif langsung ditetapkan sistem tanpa perhitungan.
2. Perhitungan memakai harga terkonfirmasi pada Daftar Kebutuhan Pengadaan yang diproses + data kinerja historis supplier hasil rekapitulasi (batasan periode data historis ditulis di Bab 1, bukan aturan yang dikunci di kode).
3. Perhitungan berjalan otomatis saat Bagian Pembelian memilih Daftar Kebutuhan Pengadaan yang diproses, mencakup seluruh kelompok item dengan >1 alternatif.
4. Supplier tanpa data kinerja historis dikecualikan dari perbandingan SAW untuk kelompok itu (ditandai "Belum Ada Riwayat"). Kalau pengecualian menyisakan 1 alternatif, supplier itu ditetapkan langsung tanpa Perhitungan SAW.
5. Keputusan akhir tetap di Bagian Pembelian — boleh ikut rekomendasi atau pilih manual.

Kelima poin ini sudah diverifikasi cocok dengan kode aktual (dicek ulang di sesi ini).

> **Penjelasan tambahan untuk poin #3 & #5 (2026-08-05, bukan poin ke-6 baru — cuma memperjelas kalimat yang sudah ada):** "otomatis" pada poin #3 berarti sistem menghitung ulang sendiri kelompok yang belum dikonfirmasi setiap kali data sumbernya berubah (harga penawaran, nilai kinerja supplier, atau bobot kriteria) — bukan cuma sekali di kunjungan pertama. Begitu Bagian Pembelian mengonfirmasi pilihan (UC-04), kelompok itu terkunci permanen sesuai poin #5 ("keputusan akhir tetap di Bagian Pembelian") — tidak pernah dihitung ulang lagi walau data berubah setelahnya. Detail teknis: `SawBatchCalculator::determineSkipTierKeys()`.

---

## A. Kelas yang MASUK Skripsi (Class Diagram Bab 4)

> **Keputusan (dibahas, tidak dieksekusi):** setelah controller di-rename, sempat muncul pertanyaan apakah model (`SawKriteria`, `SawNilaiHistoris`, `SawPerhitungan`, `SawPerhitunganDetail`, `SawRekomendasi`) juga perlu ikut dilepas prefix "Saw"-nya supaya konsisten. **Diputuskan tidak** — beda kasus dengan controller: controller di-refactor karena masalah struktural (namespace `Procurement\...` menaruh kode di folder sistem lain), sementara model sudah flat di `app/Models/` tanpa masalah itu. Prefix "Saw" di model justru informatif (menandakan entitas domain algoritma SAW), dan blast radius rename 5 model jauh lebih besar (menyentuh semua service, controller lain termasuk yang di luar scope, belasan view, seeder) untuk manfaat yang sifatnya kosmetik saja.

> **Keputusan (2026-08-05):** model ke-11, `SawNilaiHistorisDetail`, ditambahkan ke Class Diagram — mengubah angka "10 tabel final" yang tadinya disepakati bersama dosen pembimbing. Keputusan sadar dari user: tabel ini sekarang punya relasi nyata (FK) ke `SawNilaiHistoris` dan `SawKriteria`, dan menyimpan SEMUA nilai kriteria (bukan cuma kriteria custom lagi setelah C2-C6 ikut dipindah ke sana), jadi cukup sentral untuk digambar eksplisit alih-alih dianggap tabel pendukung teknis biasa. **Perlu dikomunikasikan ulang ke dosen pembimbing** kalau chapter penulisan sudah terlanjur menyebut angka "10".

### A.1 Model (11)

#### `Supplier`
**Atribut:** `id_supplier:int (PK)`, `kode_supplier:string`, `nama_supplier:string`, `no_telp:string`, `alamat:string`
**Method:** `+variasis(): HasMany`

#### `Needlist` *(file: NeedList.php)*
**Atribut:** `id:int (PK)`, `kode_needlist:string`, `user_id:int`, `status:enum`, `approval_status:enum`, `approved_by:int`, `approved_at:datetime`, `approval_notes:text`
**Method:**
`+details(): HasMany`
`+user(): BelongsTo`
`+supplierInquiries(): HasMany`
`+purchaseOrders(): HasMany`
`+sawPerhitungan(): HasMany`
`+sawRekomendasi(): HasMany`

#### `NeedlistItem`
**Atribut:** `id:int (PK)`, `needlist_id:int`, `id_variasi:int`, `qty:int`, `status:enum`, `rejected_reason:text`, `keterangan:text`, `is_reference:bool`
**Method:**
`+needlist(): BelongsTo`
`+variasi(): BelongsTo`
`+supplierBarang(): HasOne`
`+getSupplierAttribute(): Supplier|null` *(accessor)*

#### `Variasi`
**Atribut:** `id_variasi:int (PK)`, `barcode:string`, `id_barang:int`, `nama_variasi:string`, `id_unit:int`, `harga_jual:decimal`, `stock:decimal`, `status:enum`, `part_number:string`, `is_active:bool`, `tier:enum`
**Method:**
`+m_barang(): BelongsTo`
`+unit(): BelongsTo`
`+suppliervariasi(): HasMany`
`+needlistItems(): HasMany`
`+compatibilities(): HasMany`
`+vehicleGenerations(): BelongsToMany`
`+scopeActive($query)`

#### `MBarang`
**Atribut:** `id_barang:int (PK)`, `kode_barang:string`, `nama_barang:string`, `description:text`, `is_active:bool`, `id_kategori:int`
**Method:**
`+scopeActive($query)`
`+variasi(): HasMany`
`+kategori(): BelongsTo`

#### `SawKriteria`
**Atribut:** `id:int (PK)`, `kode:string`, `nama:string`, `jenis:enum`, `bobot:decimal`, `satuan:string`, `is_active:bool`, `urutan:int`
**Method:**
`+scopeAktif($query)` *(dipanggil `SawKriteria::aktif()`)*
`+isCost(): bool`
`+isBenefit(): bool`

#### `SawNilaiHistoris`
**Atribut:** `id:int (PK)`, `supplier_id:int`, `periode_mulai:date`, `periode_akhir:date`, `jumlah_transaksi:int`, `catatan:text`
**Method:**
`+supplier(): BelongsTo`
`+details(): HasMany`
*(kolom `total_biaya` sudah dihapus dari DB. Sejak 2026-08-05, kolom nilai kriteria C2-C6 — `termin_pembayaran`, `lead_time`, `akurasi_kuantitas`, `tingkat_pemenuhan`, `komunikasi` — dan seluruh kolom `*_manual`-nya juga sudah dihapus dari tabel ini, dipindah ke `SawNilaiHistorisDetail`. Lihat Rekap Perubahan di `skema_pemilihan_supplier.md`.)*

#### `SawNilaiHistorisDetail` *(model baru, 2026-08-05)*
**Atribut:** `id:int (PK)`, `historis_id:int (FK → SawNilaiHistoris)`, `kriteria_id:int (FK → SawKriteria)`, `nilai:decimal`
**Method:**
`+historis(): BelongsTo`
`+kriteria(): BelongsTo`

Tabel generik "nilai kriteria per historis" — satu baris = satu nilai kriteria (kode apa pun di luar C1, termasuk C2-C6 dan kriteria custom seperti C7) untuk satu record `SawNilaiHistoris`. Menggantikan kolom fixed yang sebelumnya ada langsung di `SawNilaiHistoris`, supaya jumlah kriteria bisa berubah (lewat UC-01) tanpa perlu ubah skema tabel lagi. Sengaja **tidak** ada kolom seed manual terpisah (`nilai_manual`) — satu-satunya fitur yang butuh itu (`SupplierPerformanceService`, sinkronisasi otomatis dari PO) sudah dikonfirmasi dead code (tidak ada route yang memanggilnya).

#### `SawPerhitungan`
**Atribut:** `id:int (PK)`, `needlist_id:int`, `id_variasi:int`, `id_barang:int`, `tier_key:string`, `bobot_snapshot:json`, `status:enum`, `calculated_at:datetime`, `calculated_by:int`
**Method:**
`+needlist(): BelongsTo`
`+variasi(): BelongsTo`
`+mBarang(): BelongsTo`
`+details(): HasMany`
`+calculatedBy(): BelongsTo`
`+rekomendasi(): HasOne`

#### `SawPerhitunganDetail`
**Atribut:** `id:int (PK)`, `perhitungan_id:int`, `supplier_id:int`, `id_variasi:int`, `rincian_kriteria:json`, `nilai_vi:decimal`, `ranking:int`, `is_recommended:bool`, `sumber_c1:enum`, `sumber_c3:enum`, `has_historis:bool`
**Method:**
`+perhitungan(): BelongsTo`
`+supplier(): BelongsTo`
`+variasi(): BelongsTo`
`+nilai(string $kode): float`
`+norm(string $kode): float`
`+weighted(string $kode): float`

*(2026-08-05: 18 kolom wide `nilai_c1..c6`/`norm_c1..c6`/`weighted_c1..c6` diganti satu kolom `rincian_kriteria` json — `{kode: {nilai, norm, weighted}}` — mengikuti pola `bobot_snapshot` yang sudah ada di `SawPerhitungan`. 3 method accessor baru dipakai pemanggil, misalnya `pemilihan_supplier/show.blade.php` yang sebelumnya baca `$detail->nilai_c1` sekarang baca `$detail->nilai('C1')`.)*
> ✅ **Bug sudah diperbaiki:** `sumber_c3` sekarang konsisten ter-update jadi `'historis'` saat nilainya diganti dari data historis.

#### `SawRekomendasi`
**Atribut:** `id:int (PK)`, `needlist_id:int`, `id_variasi:int`, `perhitungan_id:int`, `supplier_id_saw:int`, `supplier_id_dipilih:int`, `mengikuti_rekomendasi:bool`, `nilai_vi_terpilih:decimal`, `confirmed_at:datetime`, `confirmed_by:int`
**Method:**
`+needlist(): BelongsTo`
`+variasi(): BelongsTo`
`+perhitungan(): BelongsTo`
`+supplierSaw(): BelongsTo`
`+supplierDipilih(): BelongsTo`
`+confirmedBy(): BelongsTo`
*(kolom `alasan_override` sudah dihapus dari DB)*

---

### A.2 Service (3)

#### `NeedlistSelectionGrouper`
*Mengimplementasikan proses "Identifikasi Item beserta Alternatif Supplier per Item" pada flowchart Bab 3.*
**Method:**
`+buildGroups(Collection $groupedItems, array $referenceVariasiIds = []): array`
`-dominantManufacturer(Collection $vehicleGenerations): ?string`
`-clusterByVehicleGeneration(array $items): array`

#### `SawBatchCalculator`
**Dependency:** `SawService`, `NeedlistSelectionGrouper`
**Method:**
`+calculateForNeedlist(Needlist $needlist, array $skipTierKeys = []): array` — orkestrator auto-run (dipanggil `SupplierRecommendationController::show()`), TIDAK terikat ke tombol manual
`+determineSkipTierKeys(Needlist $needlist): array` *(baru, 2026-08-05)* — tentukan kelompok mana yang AMAN dilewati: sudah dikonfirmasi user (kunci permanen), atau belum dikonfirmasi tapi data sumbernya (harga penawaran, nilai kinerja supplier, bobot kriteria) belum berubah sejak kalkulasi terakhir. Hasilnya dipakai sebagai `$skipTierKeys` di atas — inilah pengganti tombol "Hitung Ulang" manual yang sudah dihapus
`+calculateGroup(int $needlistId, array $variasiIds, int $masterBarangId): array` — cek jumlah alternatif (awal & setelah exclude), tetapkan langsung atau lanjut ke `SawService`
`-getInquiryDataByCluster(int $needlistId, array $variasiIds): array`
`-mergeWithHistoris(array $inquiryData): array` — sumber Aturan Bisnis #4 (exclude tanpa historis)

#### `SawService`
**Method:**
`+calculate(int $needlistId, ?int $idVariasi, array $supplierData, ?int $idBarang = null, ?string $tierKey = null): array`
`-candidateKey(array $s): string`
`-buildMatrix(array $supplierData, Collection $kriterias): array`
`-normalize(array $matrix, Collection $kriterias): array`
`-weightedSum(array $normalized, Collection $kriterias, array $supplierData): Collection`
`-rank(Collection $viScores): Collection`
`-saveToDatabase(int $needlistId, ?int $idVariasi, Collection $kriterias, array $supplierData, array $matrix, array $normalized, Collection $ranked, ?int $idBarang = null, ?string $tierKey = null): SawPerhitungan`
`-validateBobot(Collection $kriterias): void`

---

### A.3 Controller (4)

#### `SawKriteriaController` `<<control>>` — UC-01
**Method:**
`+index(): View`
`+create(): View`
`+store(Request $request): RedirectResponse`
`+edit(SawKriteria $kriteria): View`
`+update(Request $request, SawKriteria $kriteria): RedirectResponse`
`+destroy(SawKriteria $kriteria): RedirectResponse`
`-bobotTerlampaui(array $validated, ?SawKriteria $kriteria = null): ?string`
`-validateData(Request $request, ?SawKriteria $kriteria = null): array`

*(2026-08-05: `+normalize(): RedirectResponse` — fitur "Normalisasi Otomatis" — **dihapus total** atas permintaan user, bukan cuma dikecualikan dari diagram; route & tombol UI ikut dihapus. `destroy()` sekarang menolak hapus kriteria inti C1-C6 dan kriteria custom yang sudah punya data di `SawNilaiHistorisDetail` — signature tidak berubah, tapi aturan bisnisnya baru.)*

#### `SawHistorisController` `<<control>>` — UC-02
**Method:**
`+index(Request $request): View`
`+create(): View`
`+store(Request $request): RedirectResponse`
`+edit(SawNilaiHistoris $historis): View`
`+update(Request $request, SawNilaiHistoris $historis): RedirectResponse`
`+destroy(SawNilaiHistoris $historis): RedirectResponse`
`-denyViewOnly(): void`
`-normalizeDecimalInputs(Request $request): void`
`-kriteriaCustomAktif(): Collection` — kriteria aktif di luar C1-C6, dipakai `index()` untuk kolom ekstra di tabel (C7 dst)
`-kriteriaDinamisAktif(): Collection` — kriteria aktif di luar C1 (C2 dst), dipakai `create()`/`edit()`/form untuk field dinamis
`-kriteriaDinamisValidationRules(): array`
`-syncNilaiKriteria(SawNilaiHistoris $historis, array $nilaiKriteria): void`

*(2026-08-05: field form C2-C6 yang tadinya hardcode disatukan ke alur dinamis yang sama dengan kriteria custom — `syncKriteriaCustom()` di-rename jadi `syncNilaiKriteria()` dan sekarang menangani semua kriteria aktif di luar C1, bukan cuma C7 dst.)*

#### `SupplierRecommendationController` `<<control>>` — UC-03
*(namespace `App\Http\Controllers\SupplierSelection` — sebelumnya `PemilihanSupplierController` di `App\Http\Controllers\Procurement`, sudah di-rename & dipindah 2026-07-26)*
**Dependency:** `NeedlistSelectionGrouper`, `SawBatchCalculator` *(`SupplierLeadTimeResolver` tidak dimasukkan — di luar scope, lihat bagian B)*
**Method:**
`+index(): View` — daftar needlist tahap Pemilihan Supplier, tiap baris disertai badge progres pemilihan (lihat `statusPemilihanDariGroups()`)
`+ringkasan($id): View`
`+show($id): View` — memanggil `SawBatchCalculator::calculateForNeedlist()` otomatis (Aturan Bisnis #3); juga kirim flag "sudah pernah dipilih lengkap" ke view untuk peringatan sebelum menimpa pilihan lama
`-statusPemilihanDariGroups(array $groups): string` *(baru, 2026-08-05)* — hitung progres pemilihan per KELOMPOK (bukan per variasi): `belum_konfirmasi` / `belum_dipilih` / `sebagian_dipilih` / `sudah_dipilih`. Dipakai `index()` (badge per needlist) dan `show()` (deteksi sudah final). Per kelompok, bukan per variasi, karena satu kelompok bisa punya beberapa variasi sebagai alternatif yang saling bersaing — cuma satu yang akan dipilih, sisanya wajar tetap `pending` selamanya (menghitung per variasi salah mengira alternatif yang kalah itu "belum dipilih")

#### `SupplierConfirmationController` `<<control>>` — UC-04
*(namespace `App\Http\Controllers\SupplierSelection` — sebelumnya `SupplierSelectionController` di `App\Http\Controllers\Procurement`, sudah di-rename & dipindah 2026-07-26)*
**Dependency:** `NeedlistSelectionGrouper`
**Method:**
`+saveSelection(Request $request, $needlist_id): RedirectResponse`
`-buildGroupsForNeedlist(Needlist $needlist): array`
`-recordSawRekomendasi(int $needlistId, $inquiryIds): void`

> **Riwayat penamaan (sudah dieksekusi):** kedua controller ini dulu bernama `PemilihanSupplierController`/`SupplierSelectionController` dan berada di `App\Http\Controllers\Procurement` — namanya mirip dan lokasinya di folder Procurement berpotensi menimbulkan pertanyaan kenapa modul skripsi yang "berdiri sendiri" punya kode di dalam folder sistem pengadaan. Sudah di-rename ke `SupplierRecommendationController` (baca/hitung, UC-03) dan `SupplierConfirmationController` (tulis/konfirmasi, UC-04), sekaligus dipindah ke namespace baru `App\Http\Controllers\SupplierSelection` bersama `SawKriteriaController` dan `SawHistorisController`. Route name (`pemilihan-supplier.*`, `supplier.selection.save`, `saw.*`) tidak berubah.

---

## B. Kelas TIDAK Masuk Skripsi (referensi teknis — jangan digambar di Class Diagram)

| Kelas | Alasan dikecualikan |
|---|---|
| `SupplierInquiry`, `SupplierInquiryItem` (model) | Bagian alur pengadaan (permintaan penawaran) di luar Batasan Masalah. Harga C1 memang bersumber dari sini, tapi tidak ada FK langsung dari `saw_perhitungan_detail` — datanya di-*copy*, bukan direferensikan |
| `SupplierInquiryController` | Controller yang mengelola inquiry (buat, PDF, simpan respons) — bagian dari alur di luar scope |
| `SupplierSelectionSawController` (`detailSaw()`, `laporan()`) | Halaman breakdown matriks & Laporan SAW tidak dibahas — keluaran sistem menurut skripsi berhenti di pemberian rekomendasi |
| `SupplierLeadTimeResolver` | Kelas pendukung tampilan (estimasi lead time baris yang belum dihitung SAW), bukan bagian algoritma SAW yang dianalisis di Bab 3 |
| `SupplierPerformanceService` (seluruh class) | Sinkronisasi otomatis data historis dari transaksi PO — di luar scope |
| `SawHistorisController::syncSupplier()`, `::syncAll()` | Sama seperti di atas — sinkronisasi otomatis. Tidak ada route terdaftar (dead code), jangan disarankan diaktifkan |
| `Vehicle`, `VehicleGeneration`, `ProductVariantCompatibility` | Dipakai `NeedlistSelectionGrouper` untuk cluster kendaraan, tapi tidak ada di daftar 10 model final — kalau mau dijelaskan mekanisme cluster kendaraan secara detail di Bab 3/4, ini perlu dipertimbangkan ulang (belum ada keputusan eksplisit darimu soal 3 kelas ini) |
| `SupplierVariasi` (tabel `supplier_barang`) | Cuma referensi harga default di form respons inquiry, bukan sumber data SAW |

---

## Ringkasan Perubahan dari Draft Sebelumnya

| Ditambahkan/dipertahankan | Dihapus dari scope final |
|---|---|
| Struktur 2 bagian: A (masuk skripsi) vs B (referensi teknis) | `SupplierInquiry`, `SupplierInquiryItem`, `SupplierInquiryController` dari bagian A |
| Pemetaan Controller ↔ UC (01-04) eksplisit | `SupplierSelectionSawController` dari bagian A |
| Aturan bisnis final (5 poin) sebagai acuan di bagian atas | `SupplierLeadTimeResolver` dari bagian A |
| Rename `SupplierRecommendationController` / `SupplierConfirmationController` + pindah ke `App\Http\Controllers\SupplierSelection` (dieksekusi 2026-07-26) | Kolom `total_biaya`, `alasan_override` (sudah di-drop dari DB), dan seluruh kolom `*_manual` |

### Revisi 2026-08-05 — skema dinamis per-kriteria

| Ditambahkan/diubah | Dihapus |
|---|---|
| Model ke-11: `SawNilaiHistorisDetail` (tabel generik nilai kriteria, relasi ke `SawNilaiHistoris` & `SawKriteria`) | 8 kolom fixed C2-C6 (+`*_manual`) di `SawNilaiHistoris` |
| `SawPerhitunganDetail.rincian_kriteria` (json, mengikuti pola `bobot_snapshot`) + 3 method accessor (`nilai()`, `norm()`, `weighted()`) | 18 kolom wide `nilai_c1..c6`/`norm_c1..c6`/`weighted_c1..c6` di `SawPerhitunganDetail` |
| `SawHistorisController`: `kriteriaDinamisAktif()`, `kriteriaDinamisValidationRules()`, `syncNilaiKriteria()` — field form C2-C6 disatukan dengan alur kriteria custom | `SawKriteriaController::normalize()` (fitur "Normalisasi Otomatis" — method, route, tombol, test, dihapus total) |
| `SawKriteriaController::destroy()` — guard baru (C1-C6 tak bisa dihapus, kriteria custom terpakai tak bisa dihapus) | — |
| `SawBatchCalculator::determineSkipTierKeys()` — dirty-check otomatis (skip kalau sudah dikonfirmasi ATAU belum dikonfirmasi tapi data belum berubah; hitung ulang otomatis kalau harga/kinerja supplier/bobot kriteria berubah) | `SupplierSelectionSawController::hitungSemua()` (endpoint AJAX tombol "Hitung Ulang" manual), route `pemilihan-supplier.rekomendasi-semua`, tombol & JS `applyRekomendasi()`/`#btnRekomendasiSemua`, tombol "Detail Needlist" di halaman Pemilihan Supplier |
| `SupplierRecommendationController::statusPemilihanDariGroups()` — badge progres pemilihan per needlist (`belum_konfirmasi`/`belum_dipilih`/`sebagian_dipilih`/`sudah_dipilih`) di `pemilihan_supplier/index.blade.php`, dihitung per kelompok bukan per variasi | Badge status needlist lama di list (`selection_in_progress` → selalu "Pemilihan Supplier", nol informasi karena list ini memang cuma isi needlist berstatus itu) |
| Peringatan SweetAlert di `show.blade.php`/`_scripts.blade.php` — kalau needlist sudah "sudah_dipilih" lengkap dan user klik "Simpan Pilihan" lagi, dikonfirmasi dulu sebelum menimpa pilihan lama. **Bukan hard lock** — tetap bisa diedit sampai PO terbit (Aturan Bisnis #5), cuma tambah kesadaran sebelum menimpa | — |

**Catatan use case:** perubahan `determineSkipTierKeys()` **tidak menambah use case baru**. Syarat use case adalah ada aktor yang memicu interaksi untuk mencapai tujuan — begitu tombol "Hitung Ulang" dihapus dan diganti mekanisme otomatis, tidak ada lagi aksi eksplisit dari Bagian Pembelian yang perlu digambar terpisah. Logikanya jadi bagian dari perilaku internal UC-03, setara dengan Aturan Bisnis #4 (exclude tanpa historis) — nyata dan cukup kompleks, tapi bukan sesuatu yang "dilakukan" aktor. Yang perlu ditambahkan cuma **penjelasan kecil di narasi Aturan Bisnis #3/#5** (bukan skenario baru): kelompok yang sudah dikonfirmasi (UC-04) tidak pernah dihitung ulang lagi, walau data berubah setelahnya.

Badge progres pemilihan dan peringatan "sudah dipilih lengkap" juga **bukan use case baru** dengan alasan sama — badge cuma tampilan informasi tambahan di UC-03 (`index()`), dan peringatannya cuma dialog konfirmasi ekstra sebelum aksi "Simpan Pilihan" yang SUDAH ada di UC-04, bukan langkah/aksi baru. Keputusan desain (bukan lock keras, tetap bisa diedit sampai PO terbit) konsisten dengan Aturan Bisnis #5 yang sudah ada.
