@extends('layouts.main')

@section('title', 'Detail Daftar Kebutuhan')

@section('content')
<div class="container-fluid">


    {{-- HEADER + PROGRESS (digabung satu card) --}}
    @php
        $statusBadge = [
            'draft'                => ['secondary', 'Draf'],
            'submitted'            => ['warning',   'Menunggu Persetujuan'],
            'approved'             => ['success',   'Disetujui'],
            'rejected'             => ['danger',    'Ditolak'],
            'inquiry_created'      => ['primary',   'Konfirmasi Harga Dibuat'],
            'selection_in_progress'=> ['info',      'Pemilihan Supplier'],
            'po_issued'            => ['dark',      'Surat Pesanan Diterbitkan'],
            'completed'            => ['success',   'Selesai'],
        ][$needlist->status] ?? ['secondary', $needlist->status];

        $steps = [
            ['label' => 'Kebutuhan',  'icon' => 'fa-clipboard-list'],
            ['label' => 'Persetujuan','icon' => 'fa-check-circle'],
            ['label' => 'Konfirmasi Harga',  'icon' => 'fa-envelope-open-text'],
            ['label' => 'Pemilihan',  'icon' => 'fa-check-square'],
            ['label' => 'Surat Pesanan','icon' => 'fa-file-invoice'],
            ['label' => 'Penerimaan', 'icon' => 'fa-truck-loading'],
        ];
        $stepMap = [
            'draft'                 => 0,
            'submitted'             => 0,
            'rejected'              => 0,
            'approved'              => 1,
            'inquiry_created'       => 2,
            'selection_in_progress' => 3,
            'po_issued'             => 4,
            'completed'             => 5,
        ];
        $activeStep = $stepMap[$needlist->status] ?? 0;
    @endphp
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2 px-3">
            {{-- Baris 1: judul + kembali --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="font-weight-bold" style="font-size:1rem;">
                        <i class="fas fa-clipboard-list mr-1 text-primary"></i>
                        Detail Daftar Kebutuhan
                    </span>
                    <span class="badge badge-{{ $statusBadge[0] }} ml-2"
                          style="font-size:0.7rem; vertical-align:middle; padding:0.3em 0.6em;">
                        {{ $statusBadge[1] }}
                    </span>
                    <div class="text-muted" style="font-size:0.75rem; margin-top:1px;">
                        {{ $needlist->kode_needlist }} &mdash;
                        {{ $needlist->created_at->format('d M Y') }}
                        &mdash; Oleh: {{ $needlist->user->name ?? '-' }}
                    </div>
                </div>
                <div class="flex-shrink-0 ml-3 text-nowrap">
                    <a href="{{ route('pemilihan-supplier.show', $needlist->id) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-handshake mr-1"></i> Pemilihan Supplier
                    </a>
                    <a href="{{ route('needlist.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>

            {{-- Baris 2: progress stepper --}}
            @php $filledPct = round(($activeStep / (count($steps) - 1)) * 100); @endphp
            <div class="d-flex justify-content-between align-items-center position-relative"
                 style="padding:0 .5rem;">
                <div style="position:absolute;top:13px;left:1rem;right:1rem;height:2px;
                            background:linear-gradient(to right,#28a745 {{ $filledPct }}%,#dee2e6 {{ $filledPct }}%);
                            z-index:0;"></div>
                @foreach($steps as $i => $step)
                    @php
                        if ($i < $activeStep) {
                            $circleClass = 'bg-success text-white';
                            $textClass   = 'text-success';
                        } elseif ($i === $activeStep) {
                            $circleClass = 'bg-primary text-white';
                            $textClass   = 'text-primary font-weight-bold';
                        } else {
                            $circleClass = 'bg-light text-muted border';
                            $textClass   = 'text-muted';
                        }
                    @endphp
                    <div class="d-flex flex-column align-items-center" style="z-index:1; min-width:60px;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center {{ $circleClass }}"
                             style="width:28px;height:28px;font-size:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.15);">
                            <i class="fas {{ $step['icon'] }}"></i>
                        </div>
                        <small class="mt-1 text-center {{ $textClass }}"
                               style="font-size:0.65rem;line-height:1.2;">
                            {{ $step['label'] }}
                        </small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TAB NAVIGASI --}}
    @php
        $tabInquiryLocked    = in_array($needlist->status, ['draft','submitted','rejected','approved']);
        $tabPoLocked         = in_array($needlist->status, ['draft','submitted','rejected','approved','inquiry_created']);
    @endphp
    <div class="card mb-3 shadow-sm">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" id="procurementTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-needlist" data-toggle="tab" href="#pane-needlist" role="tab">
                        <i class="fas fa-list mr-1"></i> Daftar Kebutuhan
                    </a>
                </li>
                <li class="nav-item" @if($tabInquiryLocked) title="Selesaikan persetujuan terlebih dahulu" @endif>
                    <a class="nav-link {{ $tabInquiryLocked ? 'disabled text-muted' : '' }}"
                       id="tab-inquiry" data-toggle="tab" href="#pane-inquiry" role="tab"
                       @if($tabInquiryLocked) style="pointer-events:none; opacity:.5;" @endif>
                        <i class="fas fa-envelope-open-text mr-1"></i> Permintaan Konfirmasi Harga
                        @if($tabInquiryLocked) <i class="fas fa-lock ml-1" style="font-size:.7rem;"></i> @endif
                    </a>
                </li>
                <li class="nav-item" title="Fitur ini sudah dipindahkan ke modul Pemilihan Supplier">
                    <a class="nav-link text-muted" id="tab-selection"
                       href="{{ route('pemilihan-supplier.show', $needlist->id) }}">
                        <i class="fas fa-check-square mr-1"></i> Pemilihan Supplier
                        <span class="badge badge-info ml-1" style="font-size:.6rem;">Dipindahkan</span>
                    </a>
                </li>
                <li class="nav-item" @if($tabPoLocked) title="Selesaikan pemilihan supplier dan simpan pilihan terlebih dahulu" @endif>
                    <a class="nav-link {{ $tabPoLocked ? 'disabled text-muted' : '' }}"
                       id="tab-po" data-toggle="tab" href="#pane-po" role="tab"
                       @if($tabPoLocked) style="pointer-events:none; opacity:.5;" @endif>
                        <i class="fas fa-file-invoice mr-1"></i> Surat Pesanan
                        @if($tabPoLocked) <i class="fas fa-lock ml-1" style="font-size:.7rem;"></i> @endif
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- TAB CONTENT --}}
    <div class="tab-content" id="procurementTabContent">
        @include('pages.procurement.needlist.partials._tab_needlist')
        @include('pages.procurement.needlist.partials._tab_inquiry')
        @include('pages.procurement.needlist.partials._tab_po')
    </div>

</div>{{-- /container --}}

@include('pages.procurement.needlist.partials._modals')

@endsection

@include('pages.procurement.needlist.partials._scripts')
