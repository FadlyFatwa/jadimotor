@extends('layouts.main')
@section('title', 'Laporan Evaluasi Supplier')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">
                <i class="fas fa-chart-bar mr-2 text-primary" style="font-size:1.3rem"></i>Laporan Evaluasi Supplier
            </h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Rekap hasil penilaian supplier, rekomendasi, dan keputusan user
            </p>
        </div>
    </div>

    {{-- Small-box stats --}}
    <div class="row">
        <div class="col-6 col-md-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ $totalHitung }}</h3><p>Total Perhitungan</p></div>
                <div class="icon"><i class="fas fa-calculator"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ $totalPilihan }}</h3><p>Total Pemilihan</p></div>
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ $totalIkuti }}</h3><p>Ikuti Rekomendasi</p></div>
                <div class="icon"><i class="fas fa-thumbs-up"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ $totalOverride }}</h3><p>Override / Manual</p></div>
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
        </div>
    </div>

    {{-- Ringkasan akurasi --}}
    @php
        $totalSawDipakai = $totalIkuti + $totalOverride;
        $totalManual     = $totalPilihan - $totalSawDipakai;
    @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col">
                    <small class="text-muted d-block">Total Pilihan</small>
                    <strong>{{ $totalPilihan }}</strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Gunakan Evaluasi</small>
                    <strong class="text-info">{{ $totalKonfirmasi }}</strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Ikuti Rekomendasi</small>
                    <strong class="text-success">{{ $totalIkuti }}</strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Override</small>
                    <strong class="text-warning">{{ $totalOverride }}</strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Manual (tanpa SAW)</small>
                    <strong class="text-secondary">{{ max(0, $totalManual) }}</strong>
                </div>
                @if($totalKonfirmasi > 0)
                <div class="col">
                    <small class="text-muted d-block">Akurasi SAW</small>
                    <strong class="{{ ($totalIkuti/$totalKonfirmasi*100) >= 70 ? 'text-success' : 'text-warning' }}">
                        {{ round($totalIkuti/$totalKonfirmasi*100, 1) }}%
                    </strong>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Card utama: filter + tabel laporan --}}
    <div class="card shadow-sm">
        <div class="card-body pb-0 pt-3 px-4">
            <form method="GET" class="d-flex align-items-center" style="gap:.5rem">
                <select name="needlist_id" class="form-control form-control-sm" style="width:220px; border-radius:6px">
                    <option value="">— Semua Needlist —</option>
                    @foreach($needlists as $nl)
                        <option value="{{ $nl->id }}" {{ request('needlist_id') == $nl->id ? 'selected' : '' }}>
                            {{ $nl->kode_needlist }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search mr-1"></i>Filter
                </button>
                @if(request('needlist_id'))
                    <a href="{{ route('saw.laporan') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body px-4 pt-3">

            @php
                $grouped = $perhitungans->getCollection()
                    ->groupBy('needlist_id')
                    ->map(fn($rows) => $rows->groupBy(fn($p) => $p->id_barang ?? ('v-'.($p->id_variasi ?? 'x'))));
            @endphp

            @forelse($grouped as $needlistId => $byBarang)
                @php $firstP = $byBarang->flatten(1)->first(); @endphp

                {{-- Sub-header needlist --}}
                <div class="d-flex align-items-center justify-content-between py-2 mb-2 border-bottom">
                    <span>
                        <i class="fas fa-clipboard-list mr-1 text-primary"></i>
                        <a href="{{ route('needlist.show', $needlistId) }}" target="_blank" class="font-weight-bold">
                            {{ $firstP->needlist->kode_needlist ?? $needlistId }}
                        </a>
                        <span class="badge badge-secondary ml-1">{{ $byBarang->flatten(1)->count() }} perhitungan</span>
                    </span>
                    <small class="text-muted">{{ $firstP->needlist->created_at?->format('d M Y') ?? '' }}</small>
                </div>

                @foreach($byBarang as $barangKey => $pList)
                    @php $barangName = $pList->first()->mBarang?->nama_barang ?? $pList->first()->variasi?->m_barang?->nama_barang ?? '-'; @endphp

                    <div class="mb-3">
                        <div class="px-2 py-1 mb-1 d-flex align-items-center rounded" style="background:rgba(0,0,0,.03)">
                            <i class="fas fa-box mr-2 text-secondary" style="font-size:.82rem;"></i>
                            <strong style="font-size:.87rem;">{{ $barangName }}</strong>
                            <span class="badge badge-light ml-2">{{ $pList->count() }} tier</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="sku-thead text-center">
                                    <tr>
                                        <th class="py-2 text-secondary text-left">Tier / Cluster</th>
                                        <th class="py-2 text-secondary">Tgl Hitung</th>
                                        <th class="py-2 text-secondary">Supplier (Ranking)</th>
                                        <th class="py-2 text-secondary">Rekomendasi Sistem</th>
                                        <th class="py-2 text-secondary">Pilihan User</th>
                                        <th class="py-2 text-secondary">Metode</th>
                                        <th class="py-2 text-secondary">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pList->sortBy('calculated_at') as $p)
                                        @php
                                            $rek       = $p->rekomendasi;
                                            $topDetail = $p->details->sortBy('ranking')->first();
                                            $tierLabel = '-';
                                            if ($p->rekomendasi?->variasi?->tier) {
                                                $tierLabel = $p->rekomendasi->variasi->tier;
                                            } elseif ($p->tier_key) {
                                                $tierLabel = 'Cluster #' . substr($p->tier_key, 0, 6);
                                            }
                                            $kendaraan = $p->rekomendasi?->variasi?->vehicleGenerations
                                                ?->map(fn($g) => $g->vehicle?->name)->filter()->unique()->implode(' / ') ?? '-';
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ ['OEM'=>'primary','Original'=>'success','Aftermarket'=>'warning','KW'=>'secondary'][$tierLabel] ?? 'light text-dark' }}">
                                                    {{ $tierLabel }}
                                                </span>
                                                @if($kendaraan !== '-')
                                                    <br><small class="text-muted">{{ $kendaraan }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center" style="white-space:nowrap; font-size:.82rem;">
                                                {{ $p->calculated_at?->format('d/m/Y H:i') ?? '-' }}
                                            </td>
                                            <td>
                                                @foreach($p->details->sortBy('ranking') as $d)
                                                    <div class="{{ $d->is_recommended ? 'font-weight-bold text-success' : 'text-muted' }}" style="font-size:.82rem;">
                                                        <span class="badge badge-{{ $d->is_recommended ? 'success' : 'secondary' }}" style="font-size:.65rem;">#{{ $d->ranking }}</span>
                                                        {{ $d->supplier?->nama_supplier ?? '-' }}
                                                        <small>({{ number_format($d->nilai_vi,4) }})</small>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($topDetail)
                                                    <strong>{{ $topDetail->supplier?->nama_supplier ?? '-' }}</strong><br>
                                                    <small class="text-muted">Vi = {{ number_format($topDetail->nilai_vi,4) }}</small>
                                                @else <span class="text-muted">-</span> @endif
                                            </td>
                                            <td>
                                                @if($rek?->supplierDipilih)
                                                    {{ $rek->supplierDipilih->nama_supplier }}
                                                @else <span class="text-muted fst-italic">Belum dikonfirmasi</span> @endif
                                            </td>
                                            <td class="text-center">
                                                @if(!$rek)
                                                    <span class="badge badge-light text-secondary"><i class="fas fa-hand-pointer mr-1"></i>Manual</span>
                                                @elseif($rek->mengikuti_rekomendasi)
                                                    <span class="badge badge-success"><i class="fas fa-robot mr-1"></i>Evaluasi ✓</span>
                                                @else
                                                    <span class="badge badge-warning"><i class="fas fa-exchange-alt mr-1"></i>Override</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs btn-outline-info btn-detail-saw" data-id="{{ $p->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="mb-3"></div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Belum ada penilaian tersimpan.
                </div>
            @endforelse

            <div class="mt-2">{{ $perhitungans->links() }}</div>
        </div>
    </div>

</div>

{{-- Modal Detail --}}
<div class="modal fade" id="modalDetailSaw" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-calculator mr-1"></i>Detail Penilaian Supplier</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-detail-saw', function () {
        var id = $(this).data('id');
        $('#modalDetailBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
        $('#modalDetailSaw').modal('show');

        $.get('{{ url('/procurement/supplier-selection/detail-saw') }}/' + id, function (data) {
            if (!data.success) {
                $('#modalDetailBody').html('<div class="alert alert-danger">' + data.message + '</div>');
                return;
            }
            var p = data.perhitungan, details = data.details, bobot = data.bobot_snapshot;
            var html = '<div class="table-responsive"><table class="table table-bordered table-sm text-center">';
            html += '<thead class="bg-light"><tr><th>Supplier</th>';
            bobot.forEach(function(k) {
                html += '<th>' + k.kode + '<br><small class="text-muted">' + k.nama.split(' ').slice(0,2).join(' ') + '</small><br>';
                html += '<span class="badge badge-' + (k.jenis==='cost'?'danger':'success') + '">' + k.jenis + '</span>';
                html += '<br><small>W=' + k.bobot + '</small></th>';
            });
            html += '<th>V<sub>i</sub></th><th>Ranking</th></tr></thead><tbody>';
            details.forEach(function(d) {
                html += '<tr class="' + (d.ranking===1 ? 'table-success font-weight-bold' : '') + '">';
                html += '<td class="text-left">' + d.supplier + (d.ranking===1 ? ' 🏆' : '') + '</td>';
                ['c1','c2','c3','c4','c5','c6'].forEach(function(k) {
                    html += '<td><div>Xij: ' + parseFloat(d['nilai_'+k]).toFixed(2) + '</div>';
                    html += '<div class="text-muted" style="font-size:.8rem">Rij: ' + parseFloat(d['norm_'+k]).toFixed(4) + '</div>';
                    html += '<div class="text-primary" style="font-size:.8rem">W×R: ' + parseFloat(d['weighted_'+k]).toFixed(4) + '</div></td>';
                });
                html += '<td><strong>' + parseFloat(d.nilai_vi).toFixed(4) + '</strong></td>';
                html += '<td><span class="badge badge-' + (d.ranking===1?'success':'secondary') + '">#' + d.ranking + '</span></td></tr>';
            });
            html += '</tbody></table></div>';
            $('#modalDetailBody').html(html);
        }).fail(function() {
            $('#modalDetailBody').html('<div class="alert alert-danger">Gagal memuat detail.</div>');
        });
    });
});
</script>
@endsection
