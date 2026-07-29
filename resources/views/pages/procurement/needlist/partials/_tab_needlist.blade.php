{{-- ========================= TAB NEEDLIST ========================= --}}
<div class="tab-pane fade show active" id="pane-needlist" role="tabpanel">
    <div class="card shadow-sm">
        <div class="card-body">

            {{-- ACTION BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-1 text-secondary"></i>
                    Informasi Needlist
                </h5>
                <div class="d-flex gap-2">
                    @if(in_array($needlist->status, ['draft', 'rejected']))
                        <a href="{{ route('needlist.edit', $needlist->id) }}"
                           class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    @endif
                    @if($needlist->status === 'draft')
                        <form action="{{ route('needlist.submit', $needlist->id) }}" method="POST"
                              data-confirm="Ajukan needlist ke supervisor?">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-paper-plane me-1"></i> Ajukan
                            </button>
                        </form>
                    @endif
                    @if($needlist->status === 'approved')
                        <form action="{{ route('supplier-inquiry.storeAllFromNeedlist', $needlist->id) }}"
                              method="POST"
                              data-confirm="Buat permintaan konfirmasi harga untuk semua supplier?">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-envelope me-1"></i> Minta Konfirmasi Harga
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- INFO NEEDLIST --}}
            <table class="table table-sm table-bordered mb-4" style="max-width:500px;">
                <tr>
                    <th class="bg-light" style="width:35%">Kode</th>
                    <td>{{ $needlist->kode_needlist }}</td>
                </tr>
                <tr>
                    <th class="bg-light">Tanggal</th>
                    <td>{{ $needlist->created_at->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th class="bg-light">Dibuat Oleh</th>
                    <td>{{ $needlist->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">Status</th>
                    <td>
                        <span class="badge badge-{{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                    </td>
                </tr>
                <tr>
                    <th class="bg-light">Catatan</th>
                    <td>{{ $needlist->catatan ?? '-' }}</td>
                </tr>
            </table>

            {{-- DAFTAR BARANG — grouped per Master Barang, sub-grouped per pabrikan kendaraan --}}
            <h6 class="mb-3">Daftar Barang</h6>

            @php
                $tierColors = ['OEM'=>'primary','Original'=>'success','Aftermarket'=>'warning','KW'=>'secondary'];

                // Group by master barang id
                $grouped = $needlist->details->groupBy(fn($d) => $d->variasi->id_barang ?? 0);

                /**
                 * Tentukan pabrikan dominan untuk suatu variasi.
                 * Pabrikan dengan jumlah generasi terbanyak = dominan.
                 * Jika tidak ada kompatibilitas kendaraan → null (Universal).
                 */
                $getDominantManufacturer = function($vehicleGenerations) {
                    if ($vehicleGenerations->isEmpty()) return null;
                    $counts = $vehicleGenerations
                        ->groupBy(fn($g) => $g->vehicle->manufacturer ?? 'Lainnya')
                        ->map->count();
                    return $counts->sortByDesc(fn($c) => $c)->keys()->first();
                };
            @endphp

            @php
                // Lookup: id_variasi => collection of inquiry items (harga konfirmasi per supplier)
                $inquiryItemsByVariasi = collect();
                foreach ($needlist->supplierInquiries ?? collect() as $inq) {
                    foreach ($inq->items as $inqItem) {
                        if (!$inquiryItemsByVariasi->has($inqItem->id_variasi)) {
                            $inquiryItemsByVariasi->put($inqItem->id_variasi, collect());
                        }
                        $inquiryItemsByVariasi->get($inqItem->id_variasi)->push([
                            'supplier_nama'    => $inq->supplier->nama_supplier ?? '-',
                            'harga_konfirmasi' => $inqItem->harga_penawaran,
                            'qty'              => $inqItem->qty,
                            'estimasi'         => $inqItem->estimasi_pengiriman,
                            'status'           => $inqItem->status,
                        ]);
                    }
                }
            @endphp

            @forelse($grouped as $barangId => $items)
                @php
                    $masterBarang = $items->first()->variasi->m_barang ?? null;

                    // ── Langkah 1: sub-group per pabrikan dominan ──────────────────────
                    $byMfr = [];
                    foreach ($items as $detail) {
                        $gens = $detail->variasi->vehicleGenerations ?? collect();
                        $mfr  = $getDominantManufacturer($gens) ?? '__universal__';
                        $byMfr[$mfr][] = $detail;
                    }
                    ksort($byMfr);

                    // ── Langkah 2: dalam setiap pabrikan, cluster per generasi yang sama ─
                    // Dua item masuk kluster yang sama jika share ≥1 vehicle_generation id.
                    // Algoritma: union-find sederhana.
                    $subGroups = [];
                    foreach ($byMfr as $mfrKey => $mfrItems) {
                        $n       = count($mfrItems);
                        $mfrItems = array_values($mfrItems);
                        $parent  = range(0, $n - 1);

                        // Kumpulkan set generasi per item
                        $genSets = [];
                        foreach ($mfrItems as $idx => $d) {
                            $genSets[$idx] = ($d->variasi->vehicleGenerations ?? collect())
                                ->pluck('id')->toArray();
                        }

                        // Union
                        $find = null;
                        $find = function(int $x) use (&$parent, &$find): int {
                            while ($parent[$x] !== $x) {
                                $parent[$x] = $parent[$parent[$x]];
                                $x = $parent[$x];
                            }
                            return $x;
                        };
                        for ($i = 0; $i < $n; $i++) {
                            for ($j = $i + 1; $j < $n; $j++) {
                                if (!empty(array_intersect($genSets[$i], $genSets[$j]))) {
                                    $ri = $find($i); $rj = $find($j);
                                    if ($ri !== $rj) $parent[$ri] = $rj;
                                }
                            }
                        }

                        // Kumpulkan kluster
                        $clusters = [];
                        foreach ($mfrItems as $idx => $d) {
                            $clusters[$find($idx)][] = $d;
                        }

                        // Label kluster: kendaraan unik dalam kluster
                        foreach ($clusters as $clusterItems) {
                            $clusterVehicles = collect($clusterItems)
                                ->flatMap(fn($d) => $d->variasi->vehicleGenerations ?? collect())
                                ->map(fn($g) => $g->vehicle->name ?? '')
                                ->unique()->filter()->sort()->values()->implode(' / ');

                            $subGroups[] = [
                                'manufacturer' => $mfrKey,
                                'vehicles'     => $clusterVehicles ?: ($mfrKey === '__universal__' ? 'Universal' : $mfrKey),
                                'items'        => $clusterItems,
                            ];
                        }
                    }

                    $hasMultipleGroups = count($subGroups) > 1;
                @endphp

                @php $nlCollapseId = 'nl-mb-' . $barangId; @endphp
                <div class="card mb-2 border-left border-primary" style="border-left:3px solid #007bff !important;">
                    {{-- Group Header — clickable toggle --}}
                    <div class="card-header py-2 px-3 d-flex align-items-center"
                         data-toggle="collapse"
                         data-target="#{{ $nlCollapseId }}"
                         aria-expanded="false"
                         style="cursor:pointer; background:rgba(0,123,255,.06); user-select:none;">
                        <strong class="text-primary">
                            <i class="fas fa-box mr-1"></i>
                            {{ $masterBarang->nama_barang ?? 'Barang #'.$barangId }}
                        </strong>
                        <span class="badge badge-light border ml-1 text-muted" style="font-size:.75rem">
                            {{ $items->count() }} item
                        </span>
                        @if($hasMultipleGroups)
                            <span class="badge badge-warning ml-1" style="font-size:.7rem">
                                <i class="fas fa-layer-group mr-1"></i>{{ count($subGroups) }} kluster
                            </span>
                        @endif
                        <i class="fas fa-chevron-down ml-auto text-muted" style="font-size:.78rem; transition:transform .2s;"></i>
                    </div>

                    <div class="collapse" id="{{ $nlCollapseId }}">
                    <div class="card-body p-0">
                        @php
                            $tierOrderMap = ['OEM'=>0,'Original'=>1,'Aftermarket'=>2,'KW'=>3];
                            $canToggle    = in_array($needlist->status, ['draft','submitted','rejected','approved']);
                        @endphp

                        @foreach($subGroups as $sg)
                            @php
                                $isUniversal = ($sg['manufacturer'] === '__universal__');
                                $displayName = $sg['vehicles'];
                                $subItems    = $sg['items'];

                                $activeItems  = array_filter($subItems, fn($d) => !$d->is_reference);
                                $refItems     = array_filter($subItems, fn($d) =>  $d->is_reference);
                                $kebutuhanQty = count($activeItems) > 0
                                    ? max(array_column(array_map(fn($d)=>['qty'=>$d->qty], $activeItems),'qty'))
                                    : null;

                                // Sub-group by tier within this vehicle cluster
                                $byTier = [];
                                foreach ($subItems as $d) {
                                    $t = $d->variasi->tier ?? '__universal__';
                                    $byTier[$t][] = $d;
                                }
                                uksort($byTier, fn($a,$b) =>
                                    ($tierOrderMap[$a] ?? 99) <=> ($tierOrderMap[$b] ?? 99));
                                $hasMultipleTiers = count($byTier) > 1;
                            @endphp

                            {{-- Vehicle cluster header --}}
                            @if($hasMultipleGroups)
                                <div class="px-3 py-2 d-flex align-items-center flex-wrap"
                                     style="background:rgba(0,0,0,.03); border-top:1px solid #dee2e6;">
                                    <i class="fas fa-{{ $isUniversal ? 'globe' : 'car' }} mr-1 text-muted" style="font-size:.85rem"></i>
                                    <span class="font-weight-bold text-dark" style="font-size:.82rem; letter-spacing:.03em; text-transform:uppercase;">
                                        {{ $displayName }}
                                    </span>
                                    <span class="badge badge-secondary ml-2" style="font-size:.7rem;">
                                        {{ count($activeItems) }} item aktif
                                    </span>
                                    @if(count($refItems) > 0)
                                        <span class="badge badge-light border text-muted ml-1" style="font-size:.7rem;">
                                            {{ count($refItems) }} referensi
                                        </span>
                                    @endif
                                    @if($kebutuhanQty !== null)
                                        <span class="badge badge-info ml-auto" style="font-size:.7rem;">
                                            <i class="fas fa-shopping-cart mr-1"></i>Kebutuhan: {{ $kebutuhanQty }} unit
                                        </span>
                                    @endif
                                </div>
                            @else
                                @if($kebutuhanQty !== null && count($refItems) > 0)
                                    <div class="px-3 py-1 d-flex align-items-center"
                                         style="background:rgba(0,0,0,.02); border-top:1px solid #dee2e6;">
                                        <span class="badge badge-info" style="font-size:.7rem;">
                                            <i class="fas fa-shopping-cart mr-1"></i>Kebutuhan: {{ $kebutuhanQty }} unit
                                        </span>
                                        <span class="badge badge-light border text-muted ml-2" style="font-size:.7rem;">
                                            {{ count($refItems) }} referensi
                                        </span>
                                    </div>
                                @endif
                            @endif

                            {{-- Tier sub-groups --}}
                            @foreach($byTier as $tierKey => $tierItems)
                                @php
                                    $tLabel = ($tierKey === '__universal__') ? 'Universal' : $tierKey;
                                    $tColor = $tierColors[$tierKey] ?? 'secondary';
                                    $rowNum = 1;
                                @endphp

                                {{-- Tier divider header --}}
                                @if($hasMultipleTiers)
                                    <div class="px-4 py-1 d-flex align-items-center"
                                         style="background:rgba(0,0,0,.015); border-top:1px dashed #dee2e6;">
                                        <span class="badge badge-{{ $tColor }} mr-2" style="font-size:.68rem;">{{ $tLabel }}</span>
                                        <small class="text-muted" style="font-size:.75rem;">{{ count($tierItems) }} item</small>
                                    </div>
                                @endif

                            <table class="table table-sm table-bordered mb-0"
                                   style="{{ ($hasMultipleGroups || $hasMultipleTiers) && !$loop->last ? 'border-bottom:2px solid #adb5bd !important;' : '' }}">
                                <thead class="sku-thead">
                                    <tr>
                                        <th class="px-3 py-2 text-secondary" style="width:3%">#</th>
                                        <th class="py-2 text-secondary" style="width:14%">Variasi / Merk</th>
                                        <th class="py-2 text-secondary" style="width:19%">
                                            <i class="fas fa-car mr-1"></i>Kendaraan
                                        </th>
                                        <th class="py-2 text-secondary" style="width:7%">No Part</th>
                                        <th class="py-2 text-secondary text-center" style="width:5%">Stok</th>
                                        <th class="py-2 text-secondary text-center" style="width:8%">Qty Order</th>
                                        <th class="py-2 text-secondary text-right" style="width:10%">Harga Beli</th>
                                        <th class="py-2 text-secondary" style="width:14%">Supplier</th>
                                        <th class="py-2 text-secondary text-center" style="width:13%">Status</th>
                                        <th class="py-2 text-secondary text-center" style="width:7%">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tierItems as $detail)
                                        @php
                                            $v         = $detail->variasi;
                                            $isRef     = (bool) ($detail->is_reference ?? false);
                                            $itemBadge = ['pending'=>'secondary','approved'=>'success','rejected'=>'danger'][$detail->status] ?? 'secondary';
                                            $stock     = (int) ($v->stock ?? 0);

                                            // Harga beli terakhir — min dari semua supplier
                                            $svList   = $v->suppliervariasi ?? collect();
                                            $hargaBeli = $svList->isNotEmpty() ? $svList->min('harga_beli') : null;
                                            $hargaMax  = $svList->isNotEmpty() ? $svList->max('harga_beli') : null;

                                            // Semua supplier yang mensupply variasi ini (boleh lebih dari 1).
                                            $supplierNames = $svList->pluck('supplier.nama_supplier')->filter()->unique()->values();
                                            $supplierCount = $svList->count();

                                            $gens = $v->vehicleGenerations ?? collect();
                                            $shortYear = fn($y) => $y ? substr((string) $y, -2) : null;
                                            $kendaraanStr = $gens->map(fn($g) =>
                                                ($g->vehicle->name ?? '') . ' ' . $g->code .
                                                ($g->start_year ? ' ('.$shortYear($g->start_year).'-'.($shortYear($g->end_year) ?? 'skrg').')' : '')
                                            )->implode(', ');

                                            // Data untuk modal detail item
                                            $itemDetailData = [
                                                'nama_variasi'    => $v->nama_variasi ?? '-',
                                                'barcode'         => $v->barcode ?? '-',
                                                'part_number'     => $v->part_number ?? '-',
                                                'tier'            => $v->tier,
                                                'stock'           => (int) ($v->stock ?? 0),
                                                'harga_jual'      => (float) ($v->harga_jual ?? 0),
                                                'unit'            => $v->unit->nama_unit ?? 'pcs',
                                                'status_variasi'  => ($v->is_active ?? true) ? 'Aktif' : 'Non-aktif',
                                                'qty'             => $isRef ? null : $detail->qty,
                                                'is_reference'    => $isRef,
                                                'status_item'     => $detail->status,
                                                'master_nama'     => $v->m_barang->nama_barang ?? '-',
                                                'master_kode'     => $v->m_barang->kode_barang ?? '-',
                                                'master_kategori' => optional($v->m_barang->kategori)->nama_kategori ?? '-',
                                                'master_desc'     => $v->m_barang->description ?? '-',
                                                'kendaraan'       => $gens->map(fn($g) => [
                                                    'nama'        => $g->vehicle->name ?? '-',
                                                    'manufacturer'=> $g->vehicle->manufacturer ?? '-',
                                                    'generasi'    => $g->code ?? '',
                                                    'tahun_mulai' => $g->start_year,
                                                    'tahun_akhir' => $g->end_year,
                                                    'catatan'     => $g->pivot->compatibility_notes ?? null,
                                                ])->values()->toArray(),
                                                'supplier_count'  => $supplierCount,
                                                'suppliers'       => $svList->map(fn($sv) => [
                                                    'nama'        => $sv->supplier->nama_supplier ?? '-',
                                                    'kode'        => $sv->supplier->kode_supplier ?? '-',
                                                    'harga_beli'  => $sv->harga_beli,
                                                ])->values()->toArray(),
                                                'inquiry'         => ($inquiryItemsByVariasi->get($v->id_variasi) ?? collect())->toArray(),
                                            ];
                                        @endphp
                                        <tr id="nlrow-{{ $detail->id }}" style="{{ $isRef ? 'opacity:.5;' : '' }}">
                                            <td class="text-center px-3">{{ $rowNum++ }}</td>
                                            <td>
                                                <strong>{{ $v->nama_variasi ?? '-' }}</strong>
                                                @if($isRef)
                                                    <br><span class="badge badge-light border text-muted" style="font-size:.68rem;">
                                                        <i class="fas fa-eye mr-1"></i>Referensi
                                                    </span>
                                                @endif
                                                @if(!empty($v->barcode))
                                                    <br><small class="text-muted" style="font-size:.68rem;">{{ $v->barcode }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($kendaraanStr)
                                                    <small>{{ $kendaraanStr }}</small>
                                                @else
                                                    <span class="text-muted small">Semua / Tidak spesifik</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $v->part_number ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($stock <= 0)
                                                    <span class="badge badge-danger">Habis</span>
                                                @elseif($stock <= 5)
                                                    <span class="badge badge-warning">{{ $stock }}</span>
                                                @else
                                                    <span class="badge badge-light border">{{ $stock }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center {{ $isRef ? 'text-muted' : '' }}">
                                                {{ $isRef ? '—' : $detail->qty }}
                                            </td>
                                            <td class="text-right">
                                                @if($hargaBeli !== null)
                                                    <small>Rp {{ number_format($hargaBeli, 0, ',', '.') }}</small>
                                                    @if($hargaBeli !== $hargaMax && $hargaMax > $hargaBeli)
                                                        <br><small class="text-muted">s/d {{ number_format($hargaMax, 0, ',', '.') }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse($supplierNames as $supplierName)
                                                    <small class="d-block">{{ $supplierName }}</small>
                                                @empty
                                                    <small class="text-muted">-</small>
                                                @endforelse
                                                @if($supplierCount > 1)
                                                    <span class="badge badge-info mt-1" style="font-size:.62rem;">
                                                        <i class="fas fa-users mr-1"></i>Multi Supplier
                                                    </span>
                                                @elseif($supplierCount === 1)
                                                    <span class="badge badge-secondary mt-1" style="font-size:.62rem;">
                                                        <i class="fas fa-user mr-1"></i>Supplier Tunggal
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($isRef)
                                                    <span class="badge badge-light border text-muted">Referensi</span>
                                                    @if($canToggle)
                                                        <br>
                                                        <button type="button"
                                                                class="btn btn-xs btn-outline-primary mt-1 btn-toggle-ref"
                                                                data-item-id="{{ $detail->id }}"
                                                                data-is-ref="1"
                                                                style="font-size:.7rem; padding:.15rem .45rem;">
                                                            <i class="fas fa-plus mr-1"></i>Aktifkan
                                                        </button>
                                                    @endif
                                                @else
                                                    <span class="badge badge-{{ $itemBadge }}">
                                                        {{ ucfirst($detail->status) }}
                                                    </span>
                                                    @if($canToggle)
                                                        <br>
                                                        <button type="button"
                                                                class="btn btn-xs btn-outline-secondary mt-1 btn-toggle-ref"
                                                                data-item-id="{{ $detail->id }}"
                                                                data-is-ref="0"
                                                                style="font-size:.7rem; padding:.15rem .45rem;">
                                                            <i class="fas fa-eye mr-1"></i>Ref
                                                        </button>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-xs btn-outline-info btn-detail-item"
                                                        data-detail='@json($itemDetailData)'
                                                        title="Lihat detail lengkap">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @endforeach {{-- /byTier --}}
                        @endforeach {{-- /subGroups --}}
                    </div>
                    </div>{{-- /collapse --}}
                </div>
            @empty
                <div class="text-center text-muted py-3">Tidak ada barang dalam needlist ini.</div>
            @endforelse

        </div>
    </div>

    {{-- ===== MODAL DETAIL ITEM ===== --}}
    <div class="modal fade" id="modalDetailItem" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header py-2 bg-light">
                    <h6 class="modal-title font-weight-bold" id="modalDetailLabel">
                        <i class="fas fa-search mr-1 text-secondary"></i>Detail Item
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalDetailBody" style="font-size:.85rem;">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Memuat...
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var tierColors   = { OEM: 'primary', Original: 'success', Aftermarket: 'warning', KW: 'secondary' };
    var statusColors = { approved: 'success', pending: 'secondary', rejected: 'danger' };

    function fmt(n) {
        if (n === null || n === undefined || n === '') return '-';
        return parseInt(n).toLocaleString('id-ID');
    }
    function fmtDate(s) {
        if (!s) return '-';
        try {
            var d = new Date(s);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        } catch (e) { return s; }
    }
    function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '-'; }
    function badge(cls, txt) { return '<span class="badge badge-' + cls + '">' + txt + '</span>'; }
    function trow(label, val) {
        return '<tr>'
            + '<td class="text-muted align-middle py-1" style="width:42%;white-space:nowrap;font-size:.82rem;">' + label + '</td>'
            + '<td class="align-middle py-1">' + val + '</td>'
            + '</tr>';
    }
    function section(icon, title, extra) {
        extra = extra || '';
        return '<p class="mb-1 mt-3 font-weight-bold text-secondary border-bottom pb-1" style="font-size:.82rem;">'
            + '<i class="' + icon + ' mr-1"></i>' + title + ' ' + extra + '</p>';
    }

    $(document).on('click', '.btn-detail-item', function () {
        var d = $(this).data('detail');
        if (!d) return;

        // ── Badges ──────────────────────────────────────────────────────────────
        var tierBadge = d.tier
            ? '<span class="badge badge-' + (tierColors[d.tier] || 'secondary') + ' ml-1">' + d.tier + '</span>'
            : '';
        var supBadge = d.supplier_count > 1
            ? '<span class="badge badge-info ml-1"><i class="fas fa-users mr-1"></i>Multi Supplier (' + d.supplier_count + ')</span>'
            : (d.supplier_count === 1
                ? '<span class="badge badge-secondary ml-1"><i class="fas fa-user mr-1"></i>Supplier Tunggal</span>'
                : '<span class="badge badge-light border ml-1 text-muted">Belum ada supplier</span>');

        var stockHtml = d.stock <= 0
            ? badge('danger', 'Habis')
            : (d.stock <= 5 ? badge('warning', d.stock + ' unit') : badge('light border', d.stock + ' unit'));

        // ── Modal title ──────────────────────────────────────────────────────────
        $('#modalDetailLabel').html(
            '<i class="fas fa-search mr-1 text-secondary"></i>'
            + '<strong>' + d.nama_variasi + '</strong> '
            + tierBadge + ' ' + supBadge
        );

        // ── Left column ──────────────────────────────────────────────────────────
        var left = '';

        // Informasi Master Barang
        left += section('fas fa-box', 'Informasi Barang');
        left += '<table class="table table-xs table-sm mb-0"><tbody>';
        left += trow('Master Barang', '<strong>' + d.master_nama + '</strong>');
        left += trow('Kode Barang', '<code>' + (d.master_kode || '-') + '</code>');
        left += trow('Kategori', d.master_kategori || '-');
        left += trow('Deskripsi', d.master_desc
            ? '<small>' + d.master_desc + '</small>'
            : '<span class="text-muted">-</span>');
        left += '</tbody></table>';

        // Detail Variasi
        left += section('fas fa-tag', 'Detail Variasi');
        left += '<table class="table table-xs table-sm mb-0"><tbody>';
        left += trow('Nama Variasi', '<strong>' + d.nama_variasi + '</strong>');
        left += trow('Barcode', '<code>' + (d.barcode && d.barcode !== '-' ? d.barcode : '-') + '</code>');
        left += trow('No. Part', '<code>' + (d.part_number && d.part_number !== '-' ? d.part_number : '-') + '</code>');
        left += trow('Grade / Tier', tierBadge || '<span class="text-muted">-</span>');
        left += trow('Satuan', d.unit || 'pcs');
        left += trow('Harga Jual', d.harga_jual > 0
            ? 'Rp <strong>' + fmt(d.harga_jual) + '</strong>'
            : '<span class="text-muted">-</span>');
        left += trow('Stok Saat Ini', stockHtml);
        left += trow('Status Produk', d.status_variasi === 'Aktif'
            ? badge('success', 'Aktif')
            : badge('secondary', 'Non-aktif'));
        left += '</tbody></table>';

        // Data dalam Needlist
        left += section('fas fa-clipboard-list', 'Data dalam Needlist');
        left += '<table class="table table-xs table-sm mb-0"><tbody>';
        left += trow('Qty Order', d.qty !== null && d.qty !== undefined
            ? '<strong>' + d.qty + '</strong> ' + (d.unit || 'pcs')
            : '<span class="text-muted">— (referensi)</span>');
        left += trow('Tipe Item', d.is_reference
            ? badge('light border text-muted', '<i class="fas fa-eye mr-1"></i>Referensi')
            : badge('primary', '<i class="fas fa-check mr-1"></i>Aktif'));
        left += trow('Status Item', badge(statusColors[d.status_item] || 'secondary', ucfirst(d.status_item)));
        left += '</tbody></table>';

        // ── Right column ─────────────────────────────────────────────────────────
        var right = '';

        // Kompatibilitas Kendaraan
        right += section('fas fa-car', 'Kompatibilitas Kendaraan');
        if (d.kendaraan && d.kendaraan.length > 0) {
            right += '<ul class="list-unstyled mb-0" style="font-size:.82rem;">';
            d.kendaraan.forEach(function (k) {
                var tahun = k.tahun_mulai
                    ? ' (' + String(k.tahun_mulai).slice(-2) + '–' + (k.tahun_akhir ? String(k.tahun_akhir).slice(-2) : 'skrg') + ')'
                    : '';
                var catatan = k.catatan ? ' <small class="text-muted">· ' + k.catatan + '</small>' : '';
                right += '<li class="mb-1">'
                    + '<i class="fas fa-car fa-xs text-muted mr-1"></i>'
                    + '<strong>' + k.nama + '</strong> '
                    + '<code style="font-size:.78rem;">' + k.generasi + '</code>'
                    + tahun + catatan
                    + '</li>';
            });
            right += '</ul>';
        } else {
            right += '<small class="text-muted">Semua / Tidak spesifik</small>';
        }

        // Supplier terdaftar
        right += section('fas fa-truck', 'Supplier Terdaftar', supBadge);
        right += '<table class="table table-xs table-sm table-bordered mb-0" style="font-size:.8rem;">'
            + '<thead class="thead-light"><tr>'
            + '<th>Supplier</th><th>Kode</th><th class="text-right">Harga Beli (Ref)</th>'
            + '</tr></thead><tbody>';
        if (d.suppliers && d.suppliers.length > 0) {
            d.suppliers.forEach(function (s) {
                right += '<tr><td>' + s.nama + '</td><td><code>' + (s.kode || '-') + '</code></td>'
                    + '<td class="text-right">Rp ' + fmt(s.harga_beli) + '</td></tr>';
            });
        } else {
            right += '<tr><td colspan="3" class="text-center text-muted">Belum ada supplier terdaftar</td></tr>';
        }
        right += '</tbody></table>';

        // Konfirmasi Harga (Inquiry)
        if (d.inquiry && d.inquiry.length > 0) {
            right += section('fas fa-check-circle text-success', 'Konfirmasi Harga (Needlist ini)');
            right += '<table class="table table-xs table-sm table-bordered mb-0" style="font-size:.8rem;">'
                + '<thead class="thead-light"><tr>'
                + '<th>Supplier</th>'
                + '<th class="text-right">Harga Konfirmasi</th>'
                + '<th class="text-center">Qty</th>'
                + '<th>Estimasi Kirim</th>'
                + '</tr></thead><tbody>';
            d.inquiry.forEach(function (iq) {
                var harga = iq.harga_konfirmasi
                    ? '<strong>Rp ' + fmt(iq.harga_konfirmasi) + '</strong>'
                    : '<span class="text-muted">Belum diisi</span>';
                right += '<tr>'
                    + '<td>' + iq.supplier_nama + '</td>'
                    + '<td class="text-right">' + harga + '</td>'
                    + '<td class="text-center">' + iq.qty + '</td>'
                    + '<td>' + fmtDate(iq.estimasi) + '</td>'
                    + '</tr>';
            });
            right += '</tbody></table>';
        } else {
            right += '<div class="text-muted mt-2" style="font-size:.8rem;">'
                + '<i class="fas fa-info-circle mr-1"></i>'
                + 'Belum ada konfirmasi harga untuk needlist ini.</div>';
        }

        // ── Render body ──────────────────────────────────────────────────────────
        var body = '<div class="row">'
            + '<div class="col-md-6">' + left + '</div>'
            + '<div class="col-md-6">' + right + '</div>'
            + '</div>';

        $('#modalDetailBody').html(body);
        $('#modalDetailItem').modal('show');
    });
})();
</script>
