<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestHeader;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'total' => RequestHeader::count(),
            'pending' => RequestHeader::where('status', 0)->count(),
            'approved' => RequestHeader::where('status', 1)->count(),
            'diproses' => RequestHeader::where('status', 2)->count(),
            'selesai' => RequestHeader::where('status', 3)->count(),
            'recentRequests' => RequestHeader::latest()->take(5)->get(),
        ]);
    }
}
