<?php

namespace App\Http\Controllers;

use App\Models\RequestHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Proses approval per request (single approval)
     *
     * Alur:
     * - Validasi hak akses berdasarkan divisi (kecuali SM)
     * - Validasi level approval sesuai role
     * - Update field approval sesuai level:
     *   Level 1: SJM
     *   Level 2: SAM
     *   Level 3: SM (final approve)
     */
    public function approve($id)
    {
        $request = RequestHeader::findOrFail($id);
        $user = auth()->user();

        // Ambil daftar divisi yang boleh di-approve oleh user
        $allowedDivisions = \App\Models\DivisionApprover::where('user_id', $user->id)
            ->pluck('division_id');

        $requestDivision = $request->user->division_id;

        // Validasi akses divisi (SM boleh semua)
        if ($user->role != 'SM' && !$allowedDivisions->contains($requestDivision)) {
            return back()->with('error', 'Tidak punya akses approve divisi ini');
        }

        // Level 1 → SJM
        if ($request->current_approval_level == 1 && $user->role == 'SJM') {
            $request->approved_by_level1 = $user->id;
            $request->approved_at_level1 = now();
            $request->current_approval_level = 2;
        }

        // Level 2 → SAM
        elseif ($request->current_approval_level == 2 && $user->role == 'SAM') {
            $request->approved_by_level2 = $user->id;
            $request->approved_at_level2 = now();
            $request->current_approval_level = 3;
        }

        // Level 3 → SM (final approval)
        elseif ($request->current_approval_level == 3 && $user->role == 'SM') {
            $request->approved_by_level3 = $user->id;
            $request->approved_at_level3 = now();
            $request->status = 1; // Approved
        } else {
            return back()->with('error', 'Tidak sesuai level approval');
        }

        $request->save();

        return back()->with('success', 'Berhasil approve');
    }

    /**
     * Reject request secara manual (per request)
     *
     * Validasi:
     * - Harus sesuai level approval
     * - Tidak boleh reject jika sudah diproses
     *
     * Data yang disimpan:
     * - status = 4 (rejected)
     * - alasan reject
     * - siapa yang reject
     * - waktu reject
     */
    public function reject(Request $request, $id)
    {
        $req = RequestHeader::findOrFail($id);
        $user = auth()->user();

        // Validasi level approval
        if (
            ($req->current_approval_level == 1 && $user->role != 'SJM') ||
            ($req->current_approval_level == 2 && $user->role != 'SAM') ||
            ($req->current_approval_level == 3 && $user->role != 'SM')
        ) {
            abort(403);
        }

        // Tidak boleh reject jika sudah diproses
        if ($req->status != 0) {
            return back()->with('error', 'Request sudah diproses');
        }

        // Simpan data reject
        $req->status = 4;
        $req->nomor_serah = null;
        $req->reject_reason = $request->reason;
        $req->rejected_by = $user->id;
        $req->rejected_at = now();
        $req->save();

        return back()->with('success', 'Request ditolak');
    }

    /**
     * Halaman bulk approval
     *
     * Menampilkan semua request yang:
     * - status = pending (0)
     * - sesuai level user:
     *   SAM → level 2
     *   SM → level 3
     */
    public function bulkPage()
    {
        $user = auth()->user();

        $level = $user->role == 'SAM' ? 2 : 3;

        $requests = RequestHeader::with(['user.division', 'details.barang'])
            ->where('current_approval_level', $level)
            ->where('status', 0)
            ->get();

        return view('approval.bulk', compact('requests'));
    }

    /**
     * Proses bulk approval
     *
     * Alur:
     * 1. Ambil request yang dipilih user
     * 2. Approve sesuai role:
     *    - SAM: naik ke level 3
     *    - SM: final approve (status = 1)
     * 3. Semua request lain yang tidak dipilih:
     *    - otomatis reject
     *    - isi rejected_by, rejected_at, dan reason
     */
    public function bulkProcess(Request $request)
    {
        $user = auth()->user();

        $ids = $request->ids ?? [];

        // Validasi jika tidak ada yang dipilih
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih');
        }

        $level = $user->role == 'SAM' ? 2 : 3;

        // Ambil data sesuai level dan status
        $headers = RequestHeader::whereIn('id', $ids)
            ->where('current_approval_level', $level)
            ->where('status', 0)
            ->get();

        /** @var \App\Models\RequestHeader $req */
        foreach ($headers as $req) {

            // Jika role SAM → naik ke level 3
            if ($user->role == 'SAM') {
                $req->approved_by_level2 = $user->id;
                $req->approved_at_level2 = now();
                $req->current_approval_level = 3;
            }

            // Jika role SM → final approve
            elseif ($user->role == 'SM') {
                $req->approved_by_level3 = $user->id;
                $req->approved_at_level3 = now();
                $req->status = 1;
            }

            $req->save();
        }

        // Auto reject untuk data yang tidak dipilih
        RequestHeader::where('status', 0)
            ->where('current_approval_level', $level)
            ->whereNotIn('id', $ids)
            ->update([
                'status' => 4,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'reject_reason' => 'Auto reject (bulk approval)'
            ]);

        return back()->with('success', 'Bulk approval berhasil');
    }
}