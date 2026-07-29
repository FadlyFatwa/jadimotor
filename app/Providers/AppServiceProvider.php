<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ReceiptObserver (sinkronisasi otomatis kinerja supplier dari
        // penerimaan barang) sengaja TIDAK didaftarkan — Kelola Kinerja
        // Supplier sekarang full manual. File App\Observers\ReceiptObserver
        // dibiarkan ada, tinggal daftarkan lagi di sini kalau sinkronisasi
        // otomatis suatu saat diaktifkan kembali.
    }
}
