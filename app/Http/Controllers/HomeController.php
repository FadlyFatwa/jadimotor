<?php

namespace App\Http\Controllers;

use App\Models\Needlist;
use App\Models\Penjualan;
use App\Models\PurchaseOrder;
use App\Models\SupplierInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard, tailored to the logged-in user's role.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return match (auth()->user()->role) {
            'procurement' => $this->procurementDashboard(),
            'supervisor'  => $this->managerTokoDashboard(),
            default       => view('dashboard'),
        };
    }

    private function procurementDashboard()
    {
        $needlistCounts = Needlist::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $poCounts = PurchaseOrder::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $inquiryWaiting = SupplierInquiry::where('status', 'waiting_response')->count();

        $needlistTerbaru = Needlist::with('user')->latest()->take(5)->get();

        $poTerbaru = PurchaseOrder::with('supplier')->latest()->take(5)->get();

        return view('dashboard-procurement', [
            'needlistDraft'     => $needlistCounts->get('draft', 0),
            'needlistSubmitted' => $needlistCounts->get('submitted', 0),
            'needlistApproved'  => $needlistCounts->get('approved', 0),
            'inquiryWaiting'    => $inquiryWaiting,
            'poOpen'            => $poCounts->get('open', 0),
            'poPartial'         => $poCounts->get('partial_received', 0),
            'needlistTerbaru'   => $needlistTerbaru,
            'poTerbaru'         => $poTerbaru,
        ]);
    }

    private function managerTokoDashboard()
    {
        $needlistWaitingApproval = Needlist::where('status', 'submitted')->count();

        $needlistApprovedThisMonth = Needlist::where('approval_status', 'approved')
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $penjualanHariIni = Penjualan::where('status', 'completed')
            ->whereDate('tanggal', Carbon::today())
            ->selectRaw('count(*) as jumlah, coalesce(sum(grand_total), 0) as total')
            ->first();

        $penjualanBulanIni = Penjualan::where('status', 'completed')
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->selectRaw('count(*) as jumlah, coalesce(sum(grand_total), 0) as total')
            ->first();

        $poBerjalan = PurchaseOrder::whereIn('status', ['open', 'partial_received'])->count();

        $needlistMenungguPersetujuan = Needlist::with('user')
            ->where('status', 'submitted')
            ->latest()
            ->take(5)
            ->get();

        $penjualanTerbaru = Penjualan::with('pelanggan')
            ->where('status', 'completed')
            ->latest('tanggal')
            ->take(5)
            ->get();

        return view('dashboard-manager-toko', [
            'needlistWaitingApproval'     => $needlistWaitingApproval,
            'needlistApprovedThisMonth'   => $needlistApprovedThisMonth,
            'penjualanHariIniJumlah'      => $penjualanHariIni->jumlah ?? 0,
            'penjualanHariIniTotal'       => $penjualanHariIni->total ?? 0,
            'penjualanBulanIniJumlah'     => $penjualanBulanIni->jumlah ?? 0,
            'penjualanBulanIniTotal'      => $penjualanBulanIni->total ?? 0,
            'poBerjalan'                  => $poBerjalan,
            'needlistMenungguPersetujuan' => $needlistMenungguPersetujuan,
            'penjualanTerbaru'            => $penjualanTerbaru,
        ]);
    }
}
