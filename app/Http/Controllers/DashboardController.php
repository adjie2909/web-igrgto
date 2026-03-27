<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestHeader;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 🔥 ambil nama divisi
        $divisionName = $user->division->nama_divisi ?? '';

        // ===============================
        // BASE QUERY
        // ===============================
        $query = RequestHeader::query();

        // ===============================
        // FILTER DIVISI
        // ===============================
        if (!in_array($divisionName, ['INDOGROSIR', 'PGA'])) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
            });
        }

        // ===============================
        // CLONE QUERY UNTUK STATISTIK
        // ===============================
        return view('dashboard', [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 0)->count(),
            'approved' => (clone $query)->where('status', 1)->count(),
            'diproses' => (clone $query)->where('status', 2)->count(),
            'ditolak' => (clone $query)->where('status', 4)->count(),
            'selesai' => (clone $query)->where('status', 3)->count(),

            'recentRequests' => (clone $query)
                ->with(['user.division'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}