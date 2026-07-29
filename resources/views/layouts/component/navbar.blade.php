@php
    $role = auth()->user()->role;
    $roleBadge = [
        'owner'       => 'badge-danger',
        'admin'       => 'badge-primary',
        'supervisor'  => 'badge-warning',
        'procurement' => 'badge-info',
        'gudang'      => 'badge-success',
        'kasir'       => 'badge-secondary',
    ];
    // Label bisnis untuk peran yang namanya di database tidak sama dengan
    // sebutan aktor di alur procurement (supervisor = Manager Toko,
    // procurement = Bagian Pembelian) — supaya tidak rancu saat demo.
    $roleLabel = [
        'supervisor'  => 'Manager Toko',
        'procurement' => 'Bagian Pembelian',
    ];
    $photoUrl = auth()->user()->photo
        ? asset('storage/users/'.auth()->user()->photo)
        : asset('templates/dist/img/profile.jpg');
    // Dirender langsung dari cookie supaya skin navbar & ikon sudah benar
    // sejak HTML pertama dikirim server — tidak menunggu JS toggle jalan.
    $darkMode = request()->cookie('theme') === 'dark';
@endphp

<!-- Navbar -->
<nav class="main-header navbar navbar-expand {{ $darkMode ? 'navbar-dark' : 'navbar-white navbar-light' }}">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto align-items-center">

        {{-- Dark mode toggle --}}
        <li class="nav-item">
            <a class="nav-link" href="#" id="toggle-darkmode" role="button" title="Toggle Dark Mode">
                <i id="theme-icon" class="fas {{ $darkMode ? 'fa-sun' : 'fa-moon' }}"></i>
            </a>
        </li>

        {{-- User dropdown --}}
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <img src="{{ $photoUrl }}" class="user-image" alt="User Image">
                <span class="d-none d-md-inline ml-1">{{ auth()->user()->name }}</span>
            </a>

            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <li class="user-header">
                    <img src="{{ $photoUrl }}" class="img-circle elevation-2" alt="User Image">
                    <p>
                        {{ auth()->user()->name }}
                        <small>
                            <span class="badge {{ $roleBadge[$role] ?? 'badge-secondary' }}"
                                  style="text-transform:uppercase; letter-spacing:.4px;">
                                {{ $roleLabel[$role] ?? $role }}
                            </span>
                        </small>
                    </p>
                </li>
                <li class="user-footer">
                    <a href="{{ route('logout') }}" class="btn btn-default btn-flat float-right"
                       onclick="event.preventDefault(); document.getElementById('logout-form').requestSubmit();">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </li>
            </ul>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;" data-confirm="Yakin ingin logout dari sistem?">
                @csrf
            </form>
        </li>
    </ul>
</nav>
<!-- /.navbar -->
