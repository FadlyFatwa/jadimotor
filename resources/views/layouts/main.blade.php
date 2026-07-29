@php
  // Sumber kebenaran tema adalah cookie (bisa dibaca server), bukan localStorage,
  // supaya HTML yang dikirim server SUDAH benar dari awal — tidak ada lagi
  // kedipan/flash karena nunggu JS jalan dulu setelah halaman tampil.
  $darkMode = request()->cookie('theme') === 'dark';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JadiMotor</title>
  <link rel="icon" type="image/jpeg" href="{{ asset('LOGO JDM BW.jpg') }}">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('templates/plugins/fontawesome-free/css/all.min.css') }}">

  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('templates/dist/css/adminlte.min.css') }}">

  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('templates/plugins/select2/css/select2.css') }}">
  <link rel="stylesheet" href="{{ asset('templates/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('templates/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('templates/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">

  <style>

  /* ======================================================
     LAYOUT — Stabilitas (cegah navbar/sidebar "geser" & seam di bawah)
  ====================================================== */
  /* Lebar viewport berubah-ubah tiap pindah halaman tergantung ada/tidaknya
     scrollbar (halaman pendek vs panjang) — itu yang membuat navbar (left:0;
     right:0) & tepi sidebar kelihatan "geser" tiap navigasi. Paksa ruang
     scrollbar selalu disediakan supaya lebar viewport konstan. */
  html {
    overflow-y: scroll;
    scrollbar-gutter: stable;
  }
  /* AdminLTE menghitung tinggi minimum content-wrapper dengan asumsi ada
     footer (dikurangi 2x tinggi navbar), padahal footer di layout ini
     sengaja dimatikan. Hasilnya: ada celah ~56px di bawah yang keluar warna
     background <body>, bukan warna content-wrapper — kelihatan seperti
     "footer" yang berkedip padahal elemennya tidak ada. */
  .wrapper .content-wrapper {
    min-height: calc(100vh - (3.5rem + 1px));
  }

  /* ======================================================
     NAVBAR — Polish
  ====================================================== */
  .main-header.navbar {
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
  }
  .dark-mode .main-header.navbar {
    box-shadow: 0 1px 4px rgba(0,0,0,.4);
  }
  .main-header .nav-link {
    border-radius: .375rem;
    transition: background-color .15s ease;
  }
  .main-header .nav-link:hover {
    background-color: rgba(0,0,0,.04);
  }
  .dark-mode .main-header .nav-link:hover {
    background-color: rgba(255,255,255,.08);
  }
  .main-header .user-image {
    width: 28px;
    height: 28px;
    object-fit: cover;
    border-radius: 50%;
  }
  .main-header .user-menu .user-header {
    background: linear-gradient(135deg, #007bff, #0056d6) !important;
  }
  .main-header .user-menu .user-header img {
    border: 2px solid rgba(255,255,255,.6);
  }
  .dark-mode .main-header .dropdown-menu {
    background-color: #2d3035;
    border-color: #3d4148;
  }
  .dark-mode .main-header .dropdown-menu .user-footer {
    background-color: #2d3035;
    border-top-color: #3d4148;
  }

  /* ======================================================
     SIDEBAR — Polish
  ====================================================== */
  /* Sembunyikan scrollbar bawaan browser tapi tetap bisa di-scroll */
  .main-sidebar .sidebar {
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .main-sidebar .sidebar::-webkit-scrollbar {
    display: none;
  }
  /* Helper teks global (.dark-mode p/small/label) tidak boleh menimpa warna
     teks menu sidebar — biarkan ikut warna kontras yang sudah diatur AdminLTE
     per state (normal/hover/active), supaya teks menu aktif tidak hilang. */
  .dark-mode .main-sidebar p,
  .dark-mode .main-sidebar small,
  .dark-mode .main-sidebar label {
    color: inherit;
  }

  /* Custom Select2 styles for both light and dark mode */
  .select2-container--bootstrap-5 .select2-selection {
    border: 1px solid #ced4da !important;
    min-height: calc(2.875rem + 2px);
    padding: 0.4375rem 0.75rem;
  }

  /* Dark mode specific styles */
  .dark-mode .select2-container--bootstrap-5 .select2-selection {
    background-color: #343a40;
    border-color: #6c757d !important;
    color: #f8f9fa;
  }

  /* Light mode specific styles */
  .select2-container--bootstrap-5 .select2-selection {
    background-color: #fff;
    color: #495057;
  }

  /* Dropdown styles */
  .select2-container--bootstrap-5 .select2-dropdown {
    border: 1px solid #ced4da;
  }

  .dark-mode .select2-container--bootstrap-5 .select2-dropdown {
    background-color: #343a40;
    border-color: #6c757d;
    color: #f8f9fa;
  }

  /* Search box styles */
  .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
  }

  .dark-mode .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    background-color: #343a40;
    border-color: #6c757d;
    color: #f8f9fa;
  }

  /* Option hover styles */
  .select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #e9ecef;
    color: #495057;
  }

  .dark-mode .select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #495057;
    color: #f8f9fa;
  }

  /* ======================================================
     DARK MODE — Content Area
  ====================================================== */
  .dark-mode .content-wrapper {
    background-color: #1e2228;
  }

  /* Card */
  .dark-mode .card {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #e4e8ec;
  }
  .dark-mode .card-header {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #dde2e7;
  }
  .dark-mode .card-body {
    background-color: #2d3035;
    color: #e4e8ec;
  }
  .dark-mode .card-footer {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #dde2e7;
  }
  .dark-mode .card-outline.card-primary {
    border-top-color: #007bff;
  }

  /* Table */
  .dark-mode .table {
    color: #e4e8ec;
  }
  .dark-mode .table td,
  .dark-mode .table th {
    border-color: #3d4148;
  }
  .dark-mode .table thead th,
  .dark-mode .table thead td {
    background-color: #252830 !important;
    color: #9ea4ab !important;
    border-color: #3d4148 !important;
  }
  .dark-mode .table.table-hover tbody tr:hover {
    background-color: rgba(255,255,255,.06);
    color: #e8eaec;
  }
  .dark-mode .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255,255,255,.03);
  }
  .dark-mode .table-bordered {
    border-color: #3d4148;
  }
  .dark-mode .thead-dark th {
    background-color: #1a1d21 !important;
    border-color: #3d4148 !important;
    color: #9ea4ab !important;
  }
  .dark-mode .dataTables_info,
  .dark-mode .dataTables_paginate {
    color: #9ea4ab;
  }
  /* Selector dibuat lebih spesifik dari punya AdminLTE (.dark-mode .page-item
     .page-link dkk) supaya warna di sini yang menang, bukan ketimpa bawaan
     AdminLTE yang sebelumnya bikin tombol "Berikutnya" & nomor halaman aktif
     warnanya beda sendiri (terlalu ngejreng & tidak senada). */
  .dark-mode .dataTables_paginate .pagination .page-item .page-link {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #9ea4ab;
    box-shadow: none;
  }
  .dark-mode .dataTables_paginate .pagination .page-item .page-link:hover,
  .dark-mode .dataTables_paginate .pagination .page-item .page-link:focus {
    background-color: #363c44;
    border-color: #4a5260;
    color: #c2c9d1;
    box-shadow: none;
  }
  .dark-mode .dataTables_paginate .pagination .page-item.active .page-link {
    background-color: #3f6791;
    border-color: #3f6791;
    color: #fff;
  }
  .dark-mode .dataTables_paginate .pagination .page-item.active .page-link:hover,
  .dark-mode .dataTables_paginate .pagination .page-item.active .page-link:focus {
    background-color: #355a80;
    border-color: #355a80;
    color: #fff;
  }
  .dark-mode .dataTables_paginate .pagination .page-item.disabled .page-link {
    background-color: #252830;
    border-color: #3d4148;
    color: #5a6169;
  }

  /* Form controls */
  .dark-mode .form-control {
    background-color: #252830;
    border-color: #3d4148;
    color: #e4e8ec;
  }
  .dark-mode .form-control:focus {
    background-color: #252830;
    border-color: #5a9ef8;
    color: #f0f2f4;
    box-shadow: 0 0 0 .2rem rgba(0,123,255,.2);
  }
  .dark-mode .form-control::placeholder {
    color: #5a6169;
  }
  .dark-mode .input-group-text {
    background-color: #252830;
    border-color: #3d4148;
    color: #9ea4ab;
  }
  .dark-mode select.form-control option {
    background-color: #2d3035;
    color: #d1d4d8;
  }

  /* Custom controls (checkbox, radio) */
  .dark-mode .custom-control-label::before {
    background-color: #252830;
    border-color: #4b545c;
  }
  .dark-mode .custom-control-label {
    color: #dde2e7;
  }

  /* Alert */
  .dark-mode .alert-success {
    background-color: #1e3a2a;
    border-color: #2a5038;
    color: #80c99a;
  }
  .dark-mode .alert-danger {
    background-color: #3a1e1e;
    border-color: #5a2828;
    color: #e88;
  }

  /* Text helpers */
  .dark-mode .text-muted { color: #a8b2bc !important; }
  .dark-mode h1, .dark-mode h2, .dark-mode h3,
  .dark-mode h4, .dark-mode h5, .dark-mode h6 { color: #f0f2f4; }
  .dark-mode p { color: #cdd3d9; }
  .dark-mode label { color: #dde2e7; }
  .dark-mode .small, .dark-mode small { color: #b0bac4; }
  .dark-mode hr { border-color: #3d4148; }
  .dark-mode .border-bottom { border-color: #3d4148 !important; }

  /* Accordion (card collapse) */
  .dark-mode .card-outline.card-secondary {
    border-color: #3d4148;
  }

  /* DataTables processing */
  .dark-mode .dataTables_processing {
    background: #2d3035;
    color: #d1d4d8;
    border-color: #3d4148;
  }

  /* Table thead — light & dark via class (override inline style) */
  .sku-thead {
    background: #f0f2f4;
    border-bottom: 2px solid #dee2e6;
  }
  .dark-mode .sku-thead {
    background: #363c44 !important;
    border-bottom: 2px solid #4a5260 !important;
  }
  .dark-mode .sku-thead th {
    background: #363c44 !important;
    color: #c2c9d1 !important;
    border-color: #4a5260 !important;
  }
  /* Header label jangan ikut wrap 2 baris (bikin baris header jadi tidak
     rata tinggi dgn kolom lain), dan padding/vertical-align disamakan
     supaya kolom No tidak lebih menjorok dari kolom lain. */
  #skuTable th {
    padding: .75rem 1rem;
    white-space: nowrap;
  }
  #skuTable td {
    padding: .75rem 1rem;
    vertical-align: middle;
  }

  /* Search box dgn icon nempel di dalam input (bukan kotak terpisah),
     supaya senada di tema apapun — tidak ada lagi seam antara icon & input. */
  .search-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: .8rem;
    pointer-events: none;
  }
  .search-input-with-icon {
    border-radius: 20px;
    padding-left: 34px;
    font-size: .875rem;
  }

  /* Tombol aksi icon-only (Detail/Edit/Hapus) — lebar/tinggi disamakan
     jadi kotak persegi tetap, supaya tidak beda-beda ukuran tergantung
     bentuk glyph icon-nya (fa-eye vs fa-edit vs fa-trash). */
  .btn-icon-xs {
    width: 26px;
    height: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  /* Varian untuk tombol icon-only ukuran btn-sm (mis. kolom Aksi User) */
  .btn-icon-sm {
    width: 31px;
    height: 31px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  /* Modal */
  .dark-mode .modal-content {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #d1d4d8;
  }
  .dark-mode .modal-footer {
    border-color: #3d4148;
  }
  .dark-mode .modal-body {
    color: #d1d4d8;
  }

  /* Select2 default theme (non-bootstrap5) */
  .dark-mode .select2-container--default .select2-selection--single {
    background-color: #252830;
    border-color: #3d4148;
    color: #d1d4d8;
  }
  .dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #e4e8ec;
  }
  .dark-mode .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #9ea4ab transparent transparent transparent;
  }
  .dark-mode .select2-dropdown {
    background-color: #2d3035;
    border-color: #3d4148;
    color: #d1d4d8;
  }
  .dark-mode .select2-container--default .select2-results__option {
    background-color: #2d3035;
    color: #e4e8ec;
  }
  .dark-mode .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #007bff;
    color: #fff;
  }
  .dark-mode .select2-search--dropdown .select2-search__field {
    background-color: #252830;
    border-color: #3d4148;
    color: #d1d4d8;
  }

  /* Dark mode: table contextual rows */
  .dark-mode .table-success,
  .dark-mode .table-success > td,
  .dark-mode .table-success > th {
    background-color: rgba(40, 167, 69, 0.25) !important;
    color: inherit !important;
  }
  .dark-mode .table-secondary,
  .dark-mode .table-secondary > td,
  .dark-mode .table-secondary > th {
    background-color: rgba(108, 117, 125, 0.2) !important;
    color: inherit !important;
  }
  .dark-mode thead.bg-light,
  .dark-mode .bg-light {
    background-color: rgba(255, 255, 255, 0.06) !important;
  }
  .dark-mode .table {
    color: inherit;
  }
  .dark-mode .table-bordered td,
  .dark-mode .table-bordered th {
    border-color: rgba(255, 255, 255, 0.1);
  }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed{{ $darkMode ? ' dark-mode' : '' }}">
<div class="wrapper">

  <!-- Navbar -->
  @include('layouts.component.navbar')

  <!-- Main Sidebar Container -->
  @include('layouts.component.sidebar')

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        @yield('header')
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        @yield('content')
      </div>  
    </section>
  </div>

  <!-- Footer -->
  {{-- <footer class="main-footer">
    <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 3.2.0
    </div>
  </footer> --}}

</div>

<!-- jQuery -->
<script src="{{ asset('templates/plugins/jquery/jquery.min.js') }}"></script>

<!-- Bootstrap 4 -->
<script src="{{ asset('templates/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- DataTables & Plugins -->
<script src="{{ asset('templates/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('templates/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- AdminLTE App -->
<script src="{{ asset('templates/dist/js/adminlte.js') }}"></script>


<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JS Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> 

<!-- Custom Scripts -->
@yield('scripts')

<script>
  toastr.options = {
    positionClass   : 'toast-top-right',
    timeOut         : 4000,
    extendedTimeOut : 1500,
    progressBar     : true,
    closeButton     : true,
    newestOnTop     : true,
    preventDuplicates: true,
  };

  @if(session('success'))
    toastr.success(@json(session('success')));
  @endif
  @if(session('error'))
    toastr.error(@json(session('error')));
  @endif
  @if(session('info'))
    toastr.info(@json(session('info')));
  @endif
  @if(session('warning'))
    toastr.warning(@json(session('warning')));
  @endif
  @if($errors->any())
    @foreach($errors->all() as $err)
      toastr.error(@json($err));
    @endforeach
  @endif

  // Konfirmasi generik untuk <form data-confirm="pesan"> via SweetAlert2
  $(document).on('submit', 'form[data-confirm]', function (e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
      title: 'Konfirmasi',
      text: $(form).data('confirm'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d33',
    }).then((result) => {
      if (result.isConfirmed) form.submit();
    });
  });

  // ── Deteksi sesi habis: timer berdasarkan SESSION_LIFETIME + intercept AJAX 401/419 ──
  (function () {
    const SESSION_LIFETIME_MS = {{ config('session.lifetime') * 60000 }};
    let sessionExpiredShown = false;
    let sessionTimer = null;

    function showSessionExpired() {
      if (sessionExpiredShown) return;
      sessionExpiredShown = true;
      clearTimeout(sessionTimer);
      Swal.fire({
        title: 'Sesi Berakhir',
        text: 'Sesi Anda telah habis. Silakan login kembali.',
        icon: 'warning',
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: 'Login Kembali',
        confirmButtonColor: '#b8860b',
      }).then(() => {
        window.location.href = "{{ route('login') }}";
      });
    }

    function resetSessionTimer() {
      if (sessionExpiredShown) return;
      clearTimeout(sessionTimer);
      sessionTimer = setTimeout(showSessionExpired, SESSION_LIFETIME_MS);
    }

    $(document).ajaxComplete(function (event, xhr) {
      if (xhr.status >= 200 && xhr.status < 300) resetSessionTimer();
    });

    $(document).ajaxError(function (event, xhr) {
      if (xhr.status === 401 || xhr.status === 419) showSessionExpired();
    });

    resetSessionTimer();
  })();
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const body = document.body;
      const toggle = document.getElementById('toggle-darkmode');
      const icon = document.getElementById('theme-icon');
      const navbar = document.querySelector('.main-header');

      // Tema awal SUDAH benar — dirender langsung oleh server berdasarkan
      // cookie "theme" (lihat $darkMode di main.blade.php & navbar.blade.php).
      // Fungsi ini cuma dipakai untuk toggle instan tanpa reload + menyimpan
      // pilihan baru ke cookie supaya halaman berikutnya ikut benar dari server.
      function setTheme(isDark) {
          if (isDark) {
              body.classList.add('dark-mode');
              icon.classList.remove('fa-moon');
              icon.classList.add('fa-sun');
              navbar.classList.remove('navbar-light', 'navbar-white');
              navbar.classList.add('navbar-dark');
          } else {
              body.classList.remove('dark-mode');
              icon.classList.remove('fa-sun');
              icon.classList.add('fa-moon');
              navbar.classList.remove('navbar-dark');
              navbar.classList.add('navbar-light', 'navbar-white');
          }
          document.cookie = 'theme=' + (isDark ? 'dark' : 'light') + ';path=/;max-age=31536000;samesite=lax';
      }

      if (toggle) {
          toggle.addEventListener('click', function (e) {
              e.preventDefault();
              setTheme(!body.classList.contains('dark-mode'));
          });
      }
  });
  // Inisialisasi Select2 — selector dibatasi ke tag <select> saja, karena Select2
  // menambahkan class "select2" ke <span> container yang dibuatnya; kalau pakai
  // selector umum ".select2", container itu ikut tertangkap & ke-reinit ulang
  // dengan konfigurasi default ini, menimpa konfigurasi custom (mis. Master Barang).
  $('select.select2').select2({
    theme: 'bootstrap-5',
    placeholder: "-- Pilih --",
    width: '100%',
    allowClear: true,
  });
  // Fokus otomatis ke kotak pencarian saat dropdown dibuka, supaya bisa
  // langsung mengetik tanpa klik dua kali.
  $(document).on('select2:open', () => {
    document.querySelector('.select2-container--open .select2-search__field')?.focus();
  });
  // Select2 menutup dropdown-nya sendiri saat klik di luar, tapi itu membuat
  // klik PERTAMA di tombol lain (mis. tombol hapus baris) "termakan" hanya
  // untuk menutup dropdown — aksi sebenarnya baru jalan di klik kedua.
  // Tutup paksa dropdown yang masih terbuka di fase mousedown (sebelum event
  // click target lain diproses), supaya klik yang sama langsung kena target.
  document.addEventListener('mousedown', function (e) {
    if (e.target.closest('.select2-container')) return;
    $('select.select2-hidden-accessible').each(function () {
      const inst = $(this).data('select2');
      if (inst && inst.isOpen && inst.isOpen()) inst.close();
    });
  }, true);
</script>
</body>
</html>