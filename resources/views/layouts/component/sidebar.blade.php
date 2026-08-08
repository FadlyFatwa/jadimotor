@php
$role = auth()->user()->role;

$menus = [

    // ── MAIN ──────────────────────────────────────
    (object)[
        "header"  => "MAIN",
        "title"   => "Dashboard",
        "path"    => "/dashboard",
        "icon"    => "nav-icon fas fa-home",
        "roles"   => ['owner','admin','gudang','kasir'],
        "submenu" => []
    ],

    // ── MASTER DATA ───────────────────────────────
    (object)[
        "header"  => "Kelola Master Data",
        "title"   => "Master Data",
        "icon"    => "nav-icon fas fa-database",
        "roles"   => ['owner','admin'],
        "submenu" => [
            (object)["title" => "Kategori",          "path" => "/kategori",  "icon" => "nav-icon fas fa-tags"],
            (object)["title" => "Unit",              "path" => "/unit",      "icon" => "nav-icon fas fa-ruler"],
            (object)["title" => "Master Barang",     "path" => "/m_barang",  "icon" => "nav-icon fas fa-box-open"],
            (object)["title" => "Variasi",           "path" => "/barang",    "icon" => "nav-icon fas fa-boxes"],
            (object)["title" => "SKU / Detail",      "path" => "/variasi",   "icon" => "nav-icon fas fa-barcode"],
            (object)["title" => "SKU Terkategori",   "path" => "/variasi/terkategori", "icon" => "nav-icon fas fa-check-circle"],
            (object)["title" => "Deteksi Duplikat",  "path" => "/duplikat-item", "icon" => "nav-icon fas fa-clone"],
            (object)["title" => "Supplier",          "path" => "/supplier",  "icon" => "nav-icon fas fa-truck"],
            (object)["title" => "Kendaraan",         "path" => "/kendaraan", "icon" => "nav-icon fas fa-car"],
        ]
    ],

    // ── PENGADAAN ─────────────────────────────────
    (object)[
        "header"  => "Kelola Pengadaan",
        "title"   => "Pengadaan",
        "icon"    => "nav-icon fas fa-shopping-bag",
        "roles"   => ['owner','admin','supervisor','procurement'],
        "submenu" => [
            (object)["title" => "Daftar Kebutuhan",   "path" => "/needlist",                               "icon" => "nav-icon fas fa-clipboard-list", "roles" => ['owner','admin']],
            (object)["title" => "Persetujuan",         "path" => "/needlist/supervisor",                    "icon" => "nav-icon fas fa-user-check", "roles" => ['owner','admin']],
            (object)["title" => "Pemilihan Supplier", "path" => "#", "icon" => "nav-icon fas fa-handshake", "submenu" => [
                (object)["title" => "Daftar Pemilihan",    "path" => "/procurement/pemilihan-supplier", "icon" => "nav-icon fas fa-list-ul", "roles" => ['owner','admin','procurement']],
                (object)["title" => "Kriteria & Bobot",  "path" => "/procurement/saw-kriteria",  "icon" => "nav-icon fas fa-sliders-h"],
                (object)["title" => "Kinerja Supplier", "path" => "/procurement/saw-historis",  "icon" => "nav-icon fas fa-history"],
            ]],
        ]
    ],

    (object)[
        "header"  => null,
        "title"   => "Laporan ",
        "path"    => "/procurement/supplier-selection/laporan",
        "icon"    => "nav-icon fas fa-chart-bar",
        "roles"   => ['owner','admin'],
        "submenu" => []
    ],

    // ── OPERASIONAL ───────────────────────────────
    (object)[
        "header"  => "OPERASIONAL",
        "title"   => "Gudang",
        "icon"    => "nav-icon fas fa-warehouse",
        "roles"   => ['admin','gudang'],
        "submenu" => [
            (object)["title" => "Penerimaan Barang", "path" => "/receipts",           "icon" => "nav-icon fas fa-dolly"],
            (object)["title" => "Detail Penerimaan", "path" => "/detail-penerimaan",  "icon" => "nav-icon fas fa-list-alt"],
        ]
    ],

    (object)[
        "header"  => null,
        "title"   => "Penjualan (POS)",
        "path"    => "/penjualan",
        "icon"    => "nav-icon fas fa-cash-register",
        "roles"   => ['owner','admin','kasir'],
        "submenu" => []
    ],

    (object)[
        "header"  => null,
        "title"   => "Barcode",
        "icon"    => "nav-icon fas fa-barcode",
        "roles"   => ['owner','admin','gudang'],
        "submenu" => [
            (object)["title" => "Cetak Multiple",    "path" => "/barcode/print/multiple",        "icon" => "nav-icon fas fa-print"],
            (object)["title" => "Template 107",      "path" => "/barcode/print/template",        "icon" => "nav-icon fas fa-tag"],
            (object)["title" => "Template 101",      "path" => "/barcode/print/template/101",    "icon" => "nav-icon fas fa-tag"],
            (object)["title" => "Template Fanbelt",  "path" => "/barcode/print/template/fanbelt","icon" => "nav-icon fas fa-tag"],
        ]
    ],

    // ── LAPORAN ───────────────────────────────────
    (object)[
        "header"  => "LAPORAN",
        "title"   => "Laporan",
        "icon"    => "nav-icon fas fa-file-alt",
        "roles"   => ['owner','admin'],
        "submenu" => [
            (object)["title" => "Laporan Penerimaan","path" => "/laporan/penerimaan", "icon" => "nav-icon fas fa-file-import"],
            (object)["title" => "Laporan Stok",      "path" => "/laporan/stok",       "icon" => "nav-icon fas fa-chart-pie"],
        ]
    ],

    // ── ADMINISTRASI ───────────────────────────────
    (object)[
        "header"  => "ADMINISTRASI",
        "title"   => "Manajemen User",
        "path"    => "/users",
        "icon"    => "nav-icon fas fa-users-cog",
        "roles"   => ['owner','admin'],
        "submenu" => []
    ],
];

$roleBadge = [
    'owner'       => 'badge-danger',
    'admin'       => 'badge-primary',
    'supervisor'  => 'badge-warning',
    'procurement' => 'badge-info',
    'gudang'      => 'badge-success',
    'kasir'       => 'badge-secondary',
];
// Label bisnis untuk peran yang namanya di database tidak sama dengan sebutan
// aktor di alur procurement (supervisor = Manager Toko, procurement = Bagian
// Pembelian) — supaya tidak rancu saat demo.
$roleLabel = [
    'supervisor'  => 'Manager Toko',
    'procurement' => 'Bagian Pembelian',
];
@endphp

<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('LOGO JDM BW.jpg') }}" alt="JadiMotor"
             class="brand-image img-circle elevation-3" style="opacity:.85">
        <span class="brand-text font-weight-bold">JadiMotor</span>
    </a>

    <div class="sidebar">

        {{-- User panel --}}
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="{{ auth()->user()->photo
                        ? asset('storage/users/'.auth()->user()->photo)
                        : asset('templates/dist/img/profile.jpg') }}"
                     class="img-circle elevation-2"
                     style="width:40px; height:40px; object-fit:cover;" alt="User">
            </div>
            <div class="info">
                <a href="#" class="d-block font-weight-bold">{{ auth()->user()->name }}</a>
                <span class="badge {{ $roleBadge[$role] ?? 'badge-secondary' }}"
                      style="font-size:.62rem; letter-spacing:.4px; text-transform:uppercase; border-radius:4px;">
                    {{ $roleLabel[$role] ?? $role }}
                </span>
            </div>
        </div>

        {{-- Sidebar Menu --}}
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent text-sm"
                data-widget="treeview" role="menu" data-accordion="false">

                @foreach ($menus as $menu)
                    @if(in_array($role, $menu->roles))

                        @if(!empty($menu->header))
                            <li class="nav-header">{{ $menu->header }}</li>
                        @endif

                        @if(empty($menu->submenu))
                            {{-- Single menu item --}}
                            <li class="nav-item">
                                <a href="{{ url(ltrim($menu->path, '/')) }}"
                                   class="nav-link {{ request()->is(trim($menu->path, '/')) ? 'active' : '' }}">
                                    <i class="{{ $menu->icon }}"></i>
                                    <p>{{ $menu->title }}</p>
                                </a>
                            </li>
                        @else
                            {{-- Dropdown menu --}}
                            @php
                                $allPaths = collect($menu->submenu)->flatMap(function($s) {
                                    $paths = collect([$s->path ?? null])->filter();
                                    if (!empty($s->submenu ?? [])) {
                                        $paths = $paths->merge(collect($s->submenu)->pluck('path')->filter());
                                    }
                                    return $paths;
                                });
                                $isOpen = $allPaths->contains(fn($p) => request()->is(ltrim($p, '/').'*'));
                            @endphp
                            <li class="nav-item {{ $isOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $isOpen ? 'active' : '' }}">
                                    <i class="{{ $menu->icon }}"></i>
                                    <p>{{ $menu->title }}<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @foreach($menu->submenu as $sub)
                                        @if(in_array($role, $sub->roles ?? $menu->roles))
                                            @if(!empty($sub->submenu ?? []))
                                                @php
                                                    $subPaths  = collect($sub->submenu)->pluck('path');
                                                    $isSubOpen = $subPaths->contains(fn($p) => request()->is(ltrim($p,'/').'*'));
                                                    if (!$isSubOpen && ($sub->path ?? '#') !== '#') {
                                                        $isSubOpen = request()->is(ltrim($sub->path,'/').'*');
                                                    }
                                                @endphp
                                                <li class="nav-item {{ $isSubOpen ? 'menu-open' : '' }}">
                                                    <a href="{{ ($sub->path ?? '#') !== '#' ? url(ltrim($sub->path, '/')) : '#' }}"
                                                       class="nav-link {{ $isSubOpen ? 'active' : '' }}">
                                                        <i class="{{ $sub->icon }}"></i>
                                                        <p>{{ $sub->title }}<i class="right fas fa-angle-left"></i></p>
                                                    </a>
                                                    <ul class="nav nav-treeview">
                                                        @foreach($sub->submenu as $subsub)
                                                            @if(in_array($role, $subsub->roles ?? $sub->roles ?? $menu->roles))
                                                                <li class="nav-item">
                                                                    <a href="{{ url(ltrim($subsub->path, '/')) }}"
                                                                       class="nav-link {{ request()->is(ltrim($subsub->path,'/').'*') ? 'active' : '' }}">
                                                                        <i class="{{ $subsub->icon }}"></i>
                                                                        <p>{{ $subsub->title }}</p>
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @else
                                                <li class="nav-item">
                                                    <a href="{{ url(ltrim($sub->path, '/')) }}"
                                                       class="nav-link {{ request()->is(trim($sub->path, '/')) ? 'active' : '' }}">
                                                        <i class="{{ $sub->icon }}"></i>
                                                        <p>{{ $sub->title }}</p>
                                                    </a>
                                                </li>
                                            @endif
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @endif

                    @endif
                @endforeach

            </ul>
        </nav>

    </div>
</aside>
