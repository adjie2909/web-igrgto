<?php

namespace App\Http\Controllers;

use App\Models\RequestHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function approve($id)
    {
        $request = RequestHeader::findOrFail($id);
        $user = auth()->user();

        // 🔥 ambil mapping division user
        $allowedDivisions = \App\Models\DivisionApprover::where('user_id', $user->id)
            ->pluck('division_id');

        $requestDivision = $request->user->division_id;

        // 🔥 VALIDASI DIVISI (ANTI BYPASS URL)
        if ($user->role != 'SM' && !$allowedDivisions->contains($requestDivision)) {
            return back()->with('error', 'Tidak punya akses approve divisi ini');
        }

        // 🔥 LEVEL 1 → SJM
        if ($request->current_approval_level == 1 && $user->role == 'SJM') {
            $request->approved_by_level1 = $user->id;
            $request->approved_at_level1 = now();
            $request->current_approval_level = 2;
        }

        // 🔥 LEVEL 2 → SAM
        elseif ($request->current_approval_level == 2 && $user->role == 'SAM') {
            $request->approved_by_level2 = $user->id;
            $request->approved_at_level2 = now();
            $request->current_approval_level = 3;
        }

        // 🔥 LEVEL 3 → SM (FINAL)
        elseif ($request->current_approval_level == 3 && $user->role == 'SM') {
            $request->approved_by_level3 = $user->id;
            $request->approved_at_level3 = now();
            $request->status = 1; // ✅ APPROVED
        } else {
            return back()->with('error', 'Tidak sesuai level approval');
        }

        $request->save();

        return back()->with('success', 'Berhasil approve');
    }

    public function reject(Request $request, $id)
    {
        $req = RequestHeader::findOrFail($id);
        $user = auth()->user();

        // 🔥 VALIDASI LEVEL (INI PENTING)
        if (
            ($req->current_approval_level == 1 && $user->role != 'SJM') ||
            ($req->current_approval_level == 2 && $user->role != 'SAM') ||
            ($req->current_approval_level == 3 && $user->role != 'SM')
        ) {
            abort(403);
        }

        // 🔥 JANGAN BISA REJECT YANG SUDAH DIPROSES
        if ($req->status != 0) {
            return back()->with('error', 'Request sudah diproses');
        }

        // 🔥 SIMPAN REJECT
        $req->status = 4;
        $req->nomor_serah = null;
        $req->reject_reason = $request->reason;
        $req->rejected_by = $user->id;
        $req->rejected_at = now();
        $req->save();

        return back()->with('success', 'Request ditolak');
    }
}
