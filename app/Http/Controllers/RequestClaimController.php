<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\RequestClaim;
use App\Models\RequestClaimDetail;
use App\Models\RequestDetail;
use App\Models\RequestHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestClaimController extends Controller
{
    private function getAvailableStockMap(array $barangIds): array
    {
        $ids = collect($barangIds)->map(fn($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $requestMap = DB::table('request_details')
            ->join('request_headers', 'request_headers.id', '=', 'request_details.request_id')
            ->whereIn('request_details.barang_id', $ids->all())
            // Untuk pengambilan barang, stok fisik hanya perlu dikurangi request legacy yang sedang diproses.
            ->whereIn('request_headers.status', [2])
            ->select('request_details.barang_id', DB::raw('SUM(request_details.qty) as total_qty'))
            ->groupBy('request_details.barang_id')
            ->pluck('total_qty', 'request_details.barang_id')
            ->map(fn($qty) => (int) $qty)
            ->toArray();

        $claimMap = DB::table('request_claim_details')
            ->join('request_claims', 'request_claims.id', '=', 'request_claim_details.claim_id')
            ->whereIn('request_claim_details.barang_id', $ids->all())
            ->whereIn('request_claims.status', [0, 1])
            ->select('request_claim_details.barang_id', DB::raw('SUM(request_claim_details.qty) as total_qty'))
            ->groupBy('request_claim_details.barang_id')
            ->pluck('total_qty', 'request_claim_details.barang_id')
            ->map(fn($qty) => (int) $qty)
            ->toArray();

        $stokMap = Barang::whereIn('id', $ids->all())
            ->pluck('stok', 'id')
            ->map(fn($stok) => (int) $stok)
            ->toArray();

        $result = [];
        foreach ($ids as $barangId) {
            $result[(int) $barangId] = (int) ($stokMap[(int) $barangId] ?? 0)
                - (int) ($requestMap[(int) $barangId] ?? 0)
                - (int) ($claimMap[(int) $barangId] ?? 0);
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

    private function ensurePga(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->role === 'PGA' || $user->userid === 'PGA'), 403);
    }

    private function completedClaimedQtyByDetail(array $detailIds): array
    {
        if (empty($detailIds)) {
            return [];
        }

        return RequestClaimDetail::join('request_claims', 'request_claims.id', '=', 'request_claim_details.claim_id')
            ->whereIn('request_claim_details.request_detail_id', $detailIds)
            ->where('request_claims.status', 2)
            ->select('request_claim_details.request_detail_id', DB::raw('SUM(request_claim_details.qty) as total_qty'))
            ->groupBy('request_claim_details.request_detail_id')
            ->pluck('total_qty', 'request_claim_details.request_detail_id')
            ->map(fn($qty) => (int) $qty)
            ->toArray();
    }

    private function syncRequestHeaderCompletionStatus(int $requestId): void
    {
        $header = RequestHeader::with('details')
            ->where('id', $requestId)
            ->whereIn('status', [1, 3])
            ->first();

        if (!$header || $header->details->isEmpty()) {
            return;
        }

        $detailIds = $header->details->pluck('id')->map(fn($id) => (int) $id)->all();
        $completedMap = $this->completedClaimedQtyByDetail($detailIds);

        $allFulfilled = $header->details->every(function ($detail) use ($completedMap) {
            $completedQty = (int) ($completedMap[$detail->id] ?? 0);
            return $completedQty >= (int) $detail->qty;
        });

        $targetStatus = $allFulfilled ? 3 : 1;
        if ((int) $header->status !== $targetStatus) {
            $header->status = $targetStatus;
            $header->save();
        }
    }

    private function claimedQtyByDetail(array $detailIds): array
    {
        if (empty($detailIds)) {
            return [];
        }

        return RequestClaimDetail::join('request_claims', 'request_claims.id', '=', 'request_claim_details.claim_id')
            ->whereIn('request_claim_details.request_detail_id', $detailIds)
            ->whereIn('request_claims.status', [0, 1, 2])
            ->select('request_claim_details.request_detail_id', DB::raw('SUM(request_claim_details.qty) as total_qty'))
            ->groupBy('request_claim_details.request_detail_id')
            ->pluck('total_qty', 'request_claim_details.request_detail_id')
            ->map(fn($qty) => (int) $qty)
            ->toArray();
    }

    private function quotaRowsForDivision(int $divisionId)
    {
        $headers = RequestHeader::with(['user', 'details.barang'])
            ->whereHas('user', function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId);
            })
            ->where('status', 1)
            ->latest()
            ->get();

        $detailIds = $headers
            ->flatMap(fn($header) => $header->details->pluck('id'))
            ->map(fn($id) => (int) $id)
            ->all();

        $claimedMap = $this->claimedQtyByDetail($detailIds);
        $rows = collect();

        foreach ($headers as $header) {
            foreach ($header->details as $detail) {
                $approvedQty = (int) $detail->qty;
                $claimedQty = (int) ($claimedMap[$detail->id] ?? 0);
                $remainingQty = max(0, $approvedQty - $claimedQty);

                if ($remainingQty <= 0) {
                    continue;
                }

                $rows->push((object) [
                    'request' => $header,
                    'detail' => $detail,
                    'barang' => $detail->barang,
                    'approved_qty' => $approvedQty,
                    'claimed_qty' => $claimedQty,
                    'remaining_qty' => $remainingQty,
                ]);
            }
        }

        return $rows;
    }

    private function buildClaimNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $last = RequestClaim::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('nomor_claim')
            ->orderByDesc('id')
            ->first();

        $number = 1;

        if ($last && $last->nomor_claim && preg_match('/(\d+)$/', $last->nomor_claim, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        return sprintf('PB/GA/%s/%s/%04d', $year, $month, $number);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'PGA' || $user->userid === 'PGA') {
            $dateFrom = (string) $request->query('date_from', '');
            $dateTo = (string) $request->query('date_to', '');
            $mustFillDates = ($dateFrom === '' || $dateTo === '');

            $claimsQuery = RequestClaim::with(['user.division', 'requestHeader', 'details.barang'])
                ->latest();

            if (!$mustFillDates && $dateFrom !== '') {
                $claimsQuery->whereDate('tanggal_claim', '>=', $dateFrom);
            }

            if (!$mustFillDates && $dateTo !== '') {
                $claimsQuery->whereDate('tanggal_claim', '<=', $dateTo);
            }

            if ($mustFillDates) {
                $claimsQuery->whereRaw('1 = 0');
            }

            $claims = $claimsQuery->paginate(10)->withQueryString();

            return view('request_claim.pga_index', compact('claims', 'dateFrom', 'dateTo', 'mustFillDates'));
        }

        $divisionId = (int) ($user->division_id ?? 0);
        $quotaRows = $this->quotaRowsForDivision($divisionId);
        $claims = RequestClaim::with(['requestHeader', 'details.barang', 'user'])
            ->whereHas('user', function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId);
            })
            ->latest()
            ->paginate(10);

        return view('request_claim.index', compact('quotaRows', 'claims'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.request_detail_id' => ['required', 'exists:request_details,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = auth()->user();
        $userDivisionId = (int) ($user->division_id ?? 0);
        $items = collect($validated['items'])
            ->map(fn($item) => [
                'request_detail_id' => (int) $item['request_detail_id'],
                'qty' => (int) ($item['qty'] ?? 0),
            ])
            ->filter(fn($item) => $item['qty'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada barang yang diminta');
        }

        $details = RequestDetail::with(['barang', 'requestHeader.user'])
            ->whereIn('id', $items->pluck('request_detail_id')->all())
            ->get()
            ->keyBy('id');

        $requestIds = collect();

        foreach ($items as $item) {
            $detail = $details->get($item['request_detail_id']);

            if (
                !$detail
                || !$detail->requestHeader
                || (int) $detail->requestHeader->status !== 1
                || (int) ($detail->requestHeader->user->division_id ?? 0) !== $userDivisionId
            ) {
                return back()->with('error', 'Kuota barang tidak valid');
            }

            $requestIds->push((int) $detail->request_id);
        }

        if ($requestIds->unique()->count() !== 1) {
            return back()->with('error', 'Pengambilan barang hanya boleh dari satu nomor request');
        }

        $claimedMap = $this->claimedQtyByDetail($items->pluck('request_detail_id')->all());

        foreach ($items as $item) {
            $detail = $details->get($item['request_detail_id']);
            $remaining = max(0, (int) $detail->qty - (int) ($claimedMap[$detail->id] ?? 0));

            if ($item['qty'] > $remaining) {
                return back()->with('error', "Qty {$detail->barang->nama_barang} melebihi sisa kuota");
            }
        }

        $barangIds = $details->pluck('barang_id')->filter()->map(fn($id) => (int) $id)->unique()->values()->all();
        $availableMap = $this->getAvailableStockMap($barangIds);

        foreach ($items as $item) {
            $detail = $details->get($item['request_detail_id']);
            $barangId = (int) ($detail->barang_id ?? 0);
            if ($barangId <= 0) {
                continue;
            }

            $available = (int) ($availableMap[$barangId] ?? 0);
            if ($available <= 0) {
                return back()->with('error', "Stok {$detail->barang->nama_barang} sedang kosong, tidak bisa melakukan pengambilan.");
            }
        }

        DB::transaction(function () use ($items, $details, $user, $requestIds) {
            $claim = RequestClaim::create([
                'request_id' => $requestIds->first(),
                'user_id' => $user->id,
                'tanggal_claim' => now()->toDateString(),
                'nomor_claim' => $this->buildClaimNumber(),
                'status' => 0,
            ]);

            foreach ($items as $item) {
                $detail = $details->get($item['request_detail_id']);

                RequestClaimDetail::create([
                    'claim_id' => $claim->id,
                    'request_detail_id' => $detail->id,
                    'barang_id' => $detail->barang_id,
                    'qty' => $item['qty'],
                ]);
            }
        });

        return redirect()->route('request-claim.index')
            ->with('success', 'Permintaan barang dari kuota berhasil dikirim ke PGA');
    }

    public function process($id)
    {
        $this->ensurePga();

        $claim = RequestClaim::findOrFail($id);

        if ((int) $claim->status !== 0) {
            return back()->with('error', 'Permintaan barang tidak bisa diproses');
        }

        $claim->update([
            'status' => 1,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Permintaan barang diproses PGA');
    }

    public function complete(Request $request, $id)
    {
        $this->ensurePga();

        $claim = RequestClaim::with(['user.division', 'details.requestDetail.barang'])->findOrFail($id);

        if ((int) $claim->status !== 1) {
            return back()->with('error', 'Permintaan harus diproses dulu sebelum serah terima');
        }

        DB::transaction(function () use ($claim) {
            $detailStockInfo = [];

            foreach ($claim->details as $detail) {
                if (!$detail->barang_id) {
                    continue;
                }

                $barang = Barang::lockForUpdate()->find($detail->barang_id);

                if (!$barang) {
                    continue;
                }

                $stokSebelum = (int) $barang->stok;
                $qty = (int) $detail->qty;
                $kurang = max(0, $qty - $stokSebelum);
                $harga = (int) ($detail->requestDetail->harga_manual ?? ($detail->requestDetail->barang->harga_estimasi ?? 0));

                $detailStockInfo[$detail->id] = [
                    'stok_realtime' => max(0, $stokSebelum),
                    'kurang' => $kurang,
                    'harga_satuan' => $harga,
                    'estimasi' => $kurang * $harga,
                ];

                $barang->stok = max(0, $stokSebelum - $qty);
                $barang->save();
            }

            $claim->update([
                'status' => 2,
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            RequestHeader::where('id', (int) $claim->request_id)->update([
                'nomor_serah' => $claim->nomor_claim,
            ]);

            cache()->put("request_claim_detail_stock_snapshot:{$claim->id}", $detailStockInfo, now()->addDays(2));

            $this->syncRequestHeaderCompletionStatus((int) $claim->request_id);
        });

        $doc = $this->formatDocForUrl($claim->nomor_claim, 'SERAH-TERIMA-KUOTA');
        $pdfUrl = route('request-claim.pdf.serah', ['id' => $claim->id, 'doc' => $doc]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Serah terima selesai dan stok master sudah dipotong',
                'pdf_url' => $pdfUrl,
            ]);
        }

        return back()->with([
            'success' => 'Serah terima selesai dan stok master sudah dipotong',
            'print_claim_id' => $claim->id,
            'print_claim_doc' => $doc,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $this->ensurePga();

        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $claim = RequestClaim::findOrFail($id);

        if (!in_array((int) $claim->status, [0, 1], true)) {
            return back()->with('error', 'Permintaan barang tidak bisa ditolak');
        }

        $claim->update([
            'status' => 3,
            'reject_reason' => $request->reason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'Permintaan barang ditolak');
    }

    public function pdfSerah($id, $doc = null)
    {
        $claim = RequestClaim::with(['user.division', 'details.requestDetail.barang'])->findOrFail($id);

        $user = auth()->user();
        $isPga = $user && ($user->role === 'PGA' || $user->userid === 'PGA');
        $sameDivision = (int) ($user->division_id ?? 0) === (int) ($claim->user->division_id ?? 0);
        abort_unless($isPga || $sameDivision, 403);

        $docBenar = $this->formatDocForUrl($claim->nomor_claim, 'SERAH-TERIMA-KUOTA');
        if ($doc !== $docBenar) {
            return redirect()->route('request-claim.pdf.serah', ['id' => $claim->id, 'doc' => $docBenar]);
        }

        $detailStockInfo = cache()->get("request_claim_detail_stock_snapshot:{$claim->id}", []);

        $pdf = Pdf::loadView('pdf.serah_claim', [
            'claim' => $claim,
            'detailStockInfo' => is_array($detailStockInfo) ? $detailStockInfo : [],
        ]);

        return $pdf->stream($docBenar . '.pdf');
    }
}
