@extends('layouts.main')

@section('title', 'Pemilihan Supplier')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size: 1.5rem">
                <i class="fas fa-handshake mr-2 text-primary" style="font-size: 1.3rem"></i>
                Pemilihan Supplier
            </h2>
            <p class="text-muted mb-0" style="font-size: 0.875rem">
                Daftar kebutuhan yang sudah mendapat konfirmasi harga supplier dan siap/sudah dipilih.
            </p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body px-4 pt-3">
            @php
                $pemilihanBadge = [
                    'belum_konfirmasi' => ['secondary', 'Belum Ada Konfirmasi'],
                    'belum_dipilih'    => ['warning',   'Belum Dipilih'],
                    'sebagian_dipilih' => ['info',      'Sebagian Dipilih'],
                    'sudah_dipilih'    => ['success',   'Sudah Dipilih'],
                ];
            @endphp
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sku-thead">
                        <tr>
                            <th class="px-3 py-3 text-secondary" style="width: 5%">No</th>
                            <th class="py-3 text-secondary">Kode Needlist</th>
                            <th class="py-3 text-secondary">Dibuat Oleh</th>
                            <th class="py-3 text-secondary" style="width: 15%">Tanggal</th>
                            <th class="py-3 text-secondary text-center" style="width: 18%">Status</th>
                            <th class="py-3 text-secondary text-center" style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($needlists as $i => $needlist)
                            @php $badge = $pemilihanBadge[$needlist->pemilihanStatus] ?? ['secondary', $needlist->pemilihanStatus]; @endphp
                            <tr>
                                <td class="px-3">{{ $i + 1 }}</td>
                                <td>{{ $needlist->kode_needlist }}</td>
                                <td>{{ $needlist->user->name ?? '-' }}</td>
                                <td>{{ $needlist->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $badge[0] }}">{{ $badge[1] }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('pemilihan-supplier.ringkasan', $needlist->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-handshake mr-1"></i>Pilih Supplier
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada needlist yang siap untuk pemilihan supplier.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
