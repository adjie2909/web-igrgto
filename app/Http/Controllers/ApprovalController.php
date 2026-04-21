<?php

namespace App\Http\Controllers;

use App\Mail\SystemNotificationMail;
use App\Models\RequestDetail;
use App\Models\RequestHeader;
use App\Services\StockAvailabilityNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ApprovalController extends Controller
{
    private const EDP_NOTIFICATION_EMAIL = 'edp@gto.indogrosir.co.id';

    private function getActiveClaimQtyMap(array $barangIds): array
    {
        $ids = collect($barangIds)->map(fn($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('request_claim_details')
            ->join('request_claims', 'request_claims.id', '=', 'request_claim_details.claim_id')
            ->whereIn('request_claim_details.barang_id', $ids->all())
            ->whereIn('request_claims.status', [0, 1])
            ->select('request_claim_details.barang_id', DB::raw('SUM(request_claim_details.qty) as total_qty'))
            ->groupBy('request_claim_details.barang_id')
            ->pluck('total_qty', 'request_claim_details.barang_id')
            ->map(fn($qty) => (int) $qty)
            ->toArray();
    }

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

        $activeClaimMap = $this->getActiveClaimQtyMap($ids->all());
        $result = [];
        foreach ($rows as $row) {
            // Jangan clamp ke 0 di sini; dipakai untuk rekonstruksi stok draft centang SM.
            $result[(int) $row->id] = (int) $row->stok - (int) $row->total_request - (int) ($activeClaimMap[(int) $row->id] ?? 0);
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

    private function getApprovalPageBaselineStockMap($requestRows): array
    {
        $barangIds = collect($requestRows)
            ->pluck('barang_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableMap = $this->getAvailableStockMap($barangIds);
        $qtyByBarangOnPage = [];

        foreach ($requestRows as $row) {
            $barangId = (int) $row->barang_id;
            if ($barangId <= 0) {
                continue;
            }

            $qtyByBarangOnPage[$barangId] = (int) ($qtyByBarangOnPage[$barangId] ?? 0) + (int) $row->qty;
        }

        foreach ($qtyByBarangOnPage as $barangId => $qty) {
            $availableMap[$barangId] = (int) ($availableMap[$barangId] ?? 0) + (int) $qty;
        }

        return $availableMap;
    }

    private function buildApprovalSummary($requestRows): array
    {
        $rows = collect($requestRows)->values();
        $stockMap = $this->getApprovalPageBaselineStockMap($rows);

        $totalItem = 0;
        $totalQty = 0;
        $totalEstimasi = 0;

        foreach ($rows as $row) {
            $barangId = (int) $row->barang_id;
            $qty = (int) $row->qty;
            $harga = (int) ($row->harga_manual ?? ($row->barang->harga_estimasi ?? 0));

            $stokSaatIni = (int) ($stockMap[$barangId] ?? 0);
            $kurang = max(0, $qty - $stokSaatIni);

            $totalItem++;
            $totalQty += $qty;
            $totalEstimasi += $kurang * $harga;

            $stockMap[$barangId] = max(0, $stokSaatIni - $qty);
        }

        return [
            'total_item' => $totalItem,
            'total_qty' => $totalQty,
            'total_estimasi' => $totalEstimasi,
        ];
    }

    private function sendApprovalNotification(RequestHeader $header): void
    {
        $header->loadMissing('user.division');

        $targetRole = match ((int) $header->current_approval_level) {
            1 => 'SJM',
            2 => 'SAM',
            3 => 'SM',
            default => null,
        };

        if ($targetRole === null || !$header->user) {
            return;
        }

        $emails = collect([self::EDP_NOTIFICATION_EMAIL]);

        $mailData = [
            'subject' => "Permintaan Approval {$targetRole} - {$header->nomor_dokumen}",
            'request' => $header,
            'requester_name' => $header->user->name,
            'division_name' => $header->user->division->nama_divisi ?? '-',
            'target_role' => $targetRole,
        ];

        try {
            Mail::to($emails->all())->send(new SystemNotificationMail($mailData, 'request'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim notifikasi approval berjenjang', [
                'request_id' => $header->id,
                'target_role' => $targetRole,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendFinalApprovalPdfNotification(array $requestIds): void
    {
        $idList = collect($requestIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($idList->isEmpty()) {
            return;
        }

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

        if ($requests->isEmpty()) {
            return;
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

        $doc = $requests->count() === 1
            ? $this->formatDocForUrl($requests->first()->nomor_dokumen, 'APPROVAL-SM')
            : 'APPROVAL-SM-' . implode('-', $requests->pluck('id')->all());

        $pdf = Pdf::loadView('pdf.approval_sm', [
            'requests' => $requests,
            'samName' => $samName ?: 'SAM',
            'smName' => $smName ?: 'SM',
            'printedAt' => now(),
            'detailStockInfo' => $detailStockInfo,
        ]);

        $fileName = $doc . '.pdf';
        $firstRequest = $requests->first();

        $mailData = [
            'subject' => "Permintaan Barang Siap Diproses PGA - {$doc}",
            'request' => $firstRequest,
            'requester_name' => $firstRequest->user->name ?? '-',
            'division_name' => $firstRequest->user->division->nama_divisi ?? '-',
            'target_role' => 'PGA',
            'attachment_data' => $pdf->output(),
            'attachment_name' => $fileName,
            'attachment_mime' => 'application/pdf',
        ];

        try {
            Mail::to([self::EDP_NOTIFICATION_EMAIL])->send(new SystemNotificationMail($mailData, 'pga'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim email PDF approval SM ke PGA', [
                'request_ids' => $requests->pluck('id')->all(),
                'error' => $e->getMessage(),
            ]);
        }
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
        $shouldSendFinalApprovalPdf = false;

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
            $shouldSendFinalApprovalPdf = true;
        } else {
            if ($httpRequest->expectsJson()) {
                return response()->json([
                    'message' => 'Tidak sesuai level approval',
                ], 422);
            }

            return back()->with('error', 'Tidak sesuai level approval');
        }

        $request->save();

        if (!$shouldSendFinalApprovalPdf) {
            $this->sendApprovalNotification($request);
        }

        if ($shouldSendFinalApprovalPdf) {
            $this->sendFinalApprovalPdfNotification([(int) $request->id]);

            if ($httpRequest->expectsJson()) {
                return response()->json([
                    'message' => 'Permintaan barang berhasil diapprove',
                ]);
            }

            return back()->with('success', 'Permintaan barang berhasil diapprove');
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
        $stockNotifier = app(StockAvailabilityNotifier::class);
        $barangIds = $req->details()
            ->pluck('barang_id')
            ->map(fn($barangId) => (int) $barangId)
            ->filter(fn($barangId) => $barangId > 0)
            ->unique()
            ->values()
            ->all();

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

        $beforeMap = $stockNotifier->getAvailableStockMap($barangIds);

        // Simpan data reject
        $req->status = 4;
        $req->nomor_serah = null;
        $req->reject_reason = $request->reason;
        $req->rejected_by = $user->id;
        $req->rejected_at = now();
        $req->save();

        $afterMap = $stockNotifier->getAvailableStockMap($barangIds);
        $stockNotifier->notifyRecoveredItems($beforeMap, $afterMap);

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

        $baseQuery = RequestDetail::with(['barang', 'requestHeader.user.division'])
            ->whereHas('requestHeader', function ($query) use ($level) {
                $query->where('current_approval_level', $level)
                    ->where('status', 0);
            })
            ->orderByDesc(
                RequestHeader::select('created_at')
                    ->whereColumn('request_headers.id', 'request_details.request_id')
                    ->limit(1)
            )
            ->orderBy('request_id')
            ->latest();

        $allPendingRows = (clone $baseQuery)->get();

        $requestRows = (clone $baseQuery)
            ->paginate(10)
            ->withQueryString();

        $availableStockMap = $this->getApprovalPageBaselineStockMap($requestRows->getCollection());
        $globalAvailableStockMap = $this->getApprovalPageBaselineStockMap($allPendingRows);
        $globalApprovalSummary = $this->buildApprovalSummary($allPendingRows);
        $globalPendingRows = $allPendingRows->map(function ($row) use ($globalAvailableStockMap) {
            return [
                'detail_id' => (int) $row->id,
                'request_id' => (int) $row->request_id,
                'barang_id' => (int) $row->barang_id,
                'qty' => (int) $row->qty,
                'harga' => (int) ($row->harga_manual ?? ($row->barang->harga_estimasi ?? 0)),
                'base_stok' => max(0, (int) ($globalAvailableStockMap[$row->barang_id] ?? ($row->barang->stok ?? 0))),
            ];
        })->values();

        return view('approval.bulk', compact('requestRows', 'availableStockMap', 'globalAvailableStockMap', 'globalApprovalSummary', 'globalPendingRows'));
    }

    public function stock(Request $request, $id)
    {
        $barangId = (int) $id;
        abort_if($barangId <= 0, 404);

        $barang = DB::table('barangs')->where('id', $barangId)->first();
        abort_if(!$barang, 404);

        $stockMap = $this->getAvailableStockMap([$barangId]);

        $excludeIds = collect(explode(',', (string) $request->query('exclude_request_ids', '')))
            ->map(fn($value) => (int) trim($value))
            ->filter(fn($value) => $value > 0)
            ->unique()
            ->values();

        if ($excludeIds->isNotEmpty()) {
            $qtyOnPage = DB::table('request_details')
                ->where('barang_id', $barangId)
                ->whereIn('request_id', $excludeIds->all())
                ->sum('qty');

            $stockMap[$barangId] = (int) ($stockMap[$barangId] ?? 0) + (int) $qtyOnPage;
        }

        return response()->json([
            'barang_id' => $barangId,
            'stok_asli' => (int) $barang->stok,
            'stok_tersedia' => max(0, (int) ($stockMap[$barangId] ?? 0)),
            'unit' => $barang->unit,
        ]);
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
        $requestsToNotify = collect();
        $smApprovedIds = [];
        $stockNotifier = app(StockAvailabilityNotifier::class);

        $ids = collect($request->ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $pageIds = collect($request->page_request_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $approveAllPending = $request->boolean('approve_all_pending');

        if (empty($pageIds)) {
            $pageIds = $ids;
        }

        // Validasi jika tidak ada yang dipilih
        // if (empty($ids)) {
        //     return back()->with('error', 'Tidak ada data yang dipilih');
        // }

        $level = $user->role == 'SAM' ? 2 : 3;

        if ($approveAllPending) {
            $ids = RequestHeader::where('current_approval_level', $level)
                ->where('status', 0)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $pageIds = $ids;
        }

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
                $smApprovedIds[] = (int) $req->id;
            }

            $req->save();

            if ($user->role == 'SAM') {
                $requestsToNotify->push($req);
            }
        }

        foreach ($requestsToNotify as $requestToNotify) {
            $this->sendApprovalNotification($requestToNotify);
        }

        if ($user->role == 'SM' && !empty($smApprovedIds)) {
            $this->sendFinalApprovalPdfNotification($smApprovedIds);
        }

        $autoRejectedHeaders = RequestHeader::with('details')
            ->where('status', 0)
            ->where('current_approval_level', $level)
            ->whereIn('id', $pageIds)
            ->whereNotIn('id', $ids)
            ->get();

        $autoRejectedBarangIds = $autoRejectedHeaders
            ->flatMap(fn($header) => $header->details->pluck('barang_id'))
            ->map(fn($barangId) => (int) $barangId)
            ->filter(fn($barangId) => $barangId > 0)
            ->unique()
            ->values()
            ->all();

        $beforeAutoRejectMap = $stockNotifier->getAvailableStockMap($autoRejectedBarangIds);

        // Auto reject untuk data yang tidak dipilih
        RequestHeader::where('status', 0)
            ->where('current_approval_level', $level)
            ->whereIn('id', $pageIds)
            ->whereNotIn('id', $ids)
            ->update([
                'status' => 4,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'reject_reason' => 'Auto reject (bulk approval)'
            ]);

        $afterAutoRejectMap = $stockNotifier->getAvailableStockMap($autoRejectedBarangIds);
        $stockNotifier->notifyRecoveredItems($beforeAutoRejectMap, $afterAutoRejectMap);

        $role = $user->role;

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
