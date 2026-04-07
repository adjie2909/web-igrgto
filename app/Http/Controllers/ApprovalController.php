<?php

namespace App\Http\Controllers;

use App\Models\RequestHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    private function getAvailableStockMap(array $barangIds): array
    {
        $ids = collect($barangIds)->map(fn($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = DB::table('barangs')
            ->leftJoin('request_details', 'barangs.id', '=', 'request_details.barang_id')
            ->leftJoin('request_headers', 'request_details.request_id', '=', 'request_headers.id')
            ->whereIn('barangs.id', $ids->all())
            ->select(
                'barangs.id',
                'barangs.stok',
                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN request_headers.status IN (0,1,2) OR request_headers.status IS NULL
                            THEN request_details.qty
                            ELSE 0
                        END
                    ),0) as total_request
                ")
            )
            ->groupBy('barangs.id', 'barangs.stok')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            // Jangan clamp ke 0 di sini; dipakai untuk rekonstruksi stok draft centang SM.
            $result[(int) $row->id] = (int) $row->stok - (int) $row->total_request;
        }

        return $result;
    }

    private function formatDocForUrl(?string $nomor, string $fallback): string
    {
        $base = trim((string) $nomor);
        if ($base === '') {
            $base = $fallback;
        }

        return strtoupper(str_replace('/', '-', $base));
    }

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
    public function approve(Request $httpRequest, $id)
    {
        $request = RequestHeader::findOrFail($id);
        $user = auth()->user();
        $shouldPrintPdf = false;

        // Ambil daftar divisi yang boleh di-approve oleh user
        $allowedDivisions = \App\Models\DivisionApprover::where('user_id', $user->id)
            ->pluck('division_id');

        $requestDivision = $request->user->division_id;

        // Validasi akses divisi (SM boleh semua)
        if ($user->role != 'SM' && !$allowedDivisions->contains($requestDivision)) {
            if ($httpRequest->expectsJson()) {
                return response()->json([
                    'message' => 'Tidak punya akses approve divisi ini',
                ], 403);
            }

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
            $shouldPrintPdf = true;
        } else {
            if ($httpRequest->expectsJson()) {
                return response()->json([
                    'message' => 'Tidak sesuai level approval',
                ], 422);
            }

            return back()->with('error', 'Tidak sesuai level approval');
        }

        $request->save();

        if ($shouldPrintPdf) {
            $doc = $this->formatDocForUrl($request->nomor_dokumen, 'APPROVAL-SM');
            $pdfUrl = route('approval.pdf.sm', [
                'ids' => (string) $request->id,
                'doc' => $doc,
            ]);

            if ($httpRequest->expectsJson()) {
                return response()->json([
                    'message' => 'Permintaan barang berhasil diapprove',
                    'pdf_url' => $pdfUrl,
                ]);
            }

            return back()->with([
                'success' => 'Permintaan barang berhasil diapprove',
                'print_approval_ids' => (string) $request->id,
                'print_approval_doc' => $doc,
            ]);
        }

        if ($httpRequest->expectsJson()) {
            return response()->json([
                'message' => 'Permintaan barang berhasil diapprove',
            ]);
        }

        return back()->with('success', 'Permintaan barang berhasil diapprove');
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

        return back()->with('success', 'Permintaan barang ditolak');
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

        $ids = collect($request->ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        // Validasi jika tidak ada yang dipilih
        // if (empty($ids)) {
        //     return back()->with('error', 'Tidak ada data yang dipilih');
        // }

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
        $role = $user->role;

        if ($role === 'SM' && !empty($ids)) {
            $doc = 'APPROVAL-SM-' . implode('-', $ids);
            $pdfUrl = route('approval.pdf.sm', [
                'ids' => implode(',', $ids),
                'doc' => $doc,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Permintaan berhasil diapprove oleh $role",
                    'pdf_url' => $pdfUrl,
                ]);
            }

            return back()->with([
                'success' => "Permintaan berhasil diapprove oleh $role",
                'print_approval_ids' => implode(',', $ids),
                'print_approval_doc' => $doc,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Permintaan berhasil diapprove oleh $role",
            ]);
        }

        return back()->with('success', "Permintaan berhasil diapprove oleh $role");
    }

    public function pdfSmApproval($ids, $doc = null)
    {
        $idList = collect(explode(',', (string) $ids))
            ->map(fn($id) => (int) trim($id))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        abort_if($idList->isEmpty(), 404);

        $requests = RequestHeader::with([
            'user.division',
            'details.barang',
            'approverLevel2',
            'approverLevel3',
        ])
            ->whereIn('id', $idList->all())
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        abort_if($requests->isEmpty(), 404);

        $fallbackDoc = $requests->count() === 1
            ? $this->formatDocForUrl($requests->first()->nomor_dokumen, 'APPROVAL-SM')
            : 'APPROVAL-SM-' . implode('-', $idList->all());

        if ($doc !== $fallbackDoc) {
            return redirect()->route('approval.pdf.sm', [
                'ids' => implode(',', $idList->all()),
                'doc' => $fallbackDoc,
            ]);
        }

        $samName = $requests
            ->pluck('approverLevel2.name')
            ->filter()
            ->unique()
            ->implode(', ');

        $smName = $requests
            ->pluck('approverLevel3.name')
            ->filter()
            ->unique()
            ->implode(', ');

        $barangIds = $requests
            ->flatMap(fn($req) => $req->details->pluck('barang_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableMap = $this->getAvailableStockMap($barangIds);
        $detailStockInfo = [];

        $qtyByBarangInSelection = [];
        foreach ($requests as $req) {
            foreach ($req->details as $detail) {
                $barangId = (int) $detail->barang_id;
                if ($barangId <= 0) {
                    continue;
                }
                $qtyByBarangInSelection[$barangId] = (int) ($qtyByBarangInSelection[$barangId] ?? 0) + (int) $detail->qty;
            }
        }

        foreach ($qtyByBarangInSelection as $barangId => $qtySelected) {
            $availableMap[$barangId] = (int) ($availableMap[$barangId] ?? 0) + (int) $qtySelected;
        }

        foreach ($requests as $req) {
            foreach ($req->details as $detail) {
                $barangId = (int) $detail->barang_id;
                $stokSebelum = max(0, (int) ($availableMap[$barangId] ?? 0));
                $qty = (int) $detail->qty;
                $kurang = max(0, $qty - $stokSebelum);
                $harga = (int) ($detail->harga_manual ?? ($detail->barang->harga_estimasi ?? 0));
                $estimasi = $kurang * $harga;

                $detailStockInfo[$detail->id] = [
                    'stok_realtime' => $stokSebelum,
                    'kurang' => $kurang,
                    'harga_satuan' => $harga,
                    'estimasi' => $estimasi,
                ];

                $availableMap[$barangId] = $stokSebelum - $qty;
            }
        }

        $pdf = Pdf::loadView('pdf.approval_sm', [
            'requests' => $requests,
            'samName' => $samName ?: 'SAM',
            'smName' => $smName ?: 'SM',
            'printedAt' => now(),
            'detailStockInfo' => $detailStockInfo,
        ]);

        $filename = $fallbackDoc . '.pdf';

        return $pdf->stream($filename);
    }
}
