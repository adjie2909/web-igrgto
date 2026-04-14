<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RequestHeader;
use App\Models\RequestDetail;
use App\Models\DivisionApprover;
use App\Models\Barang;
use App\Models\User;
use App\Mail\SystemNotificationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class RequestController extends Controller
{
    private const EDP_NOTIFICATION_EMAIL = 'edp@gto.indogrosir.co.id';

    private function buildDetailStockInfo(RequestHeader $req, bool $addBackCurrentQty = false): array
    {
        $detailStockInfo = [];
        $availableMap = [];

        $barangIds = $req->details
            ->pluck('barang_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($barangIds)) {
            return $detailStockInfo;
        }

        $reservedBefore = [];
        $reservedRows = RequestDetail::join('request_headers', 'request_headers.id', '=', 'request_details.request_id')
            ->whereIn('request_details.barang_id', $barangIds)
            ->whereIn('request_headers.status', [1, 2])
            ->where('request_headers.id', '<', $req->id)
            ->select(
                'request_details.barang_id',
                DB::raw('SUM(request_details.qty) as total_qty')
            )
            ->groupBy('request_details.barang_id')
            ->get();

        foreach ($reservedRows as $row) {
            $reservedBefore[(int) $row->barang_id] = (int) $row->total_qty;
        }

        $qtyCurrentByBarang = $req->details
            ->groupBy('barang_id')
            ->map(fn($items) => (int) $items->sum('qty'))
            ->toArray();

        foreach ($req->details as $detail) {
            $barangId = (int) $detail->barang_id;
            if ($barangId <= 0) {
                continue;
            }

            if (!array_key_exists($barangId, $availableMap)) {
                $stokMaster = (int) ($detail->barang->stok ?? 0);
                $stokMaster += $addBackCurrentQty ? (int) ($qtyCurrentByBarang[$barangId] ?? 0) : 0;
                $stokSetelahAntrian = $stokMaster - (int) ($reservedBefore[$barangId] ?? 0);
                $availableMap[$barangId] = $stokSetelahAntrian;
            }

            $stokSebelum = max(0, (int) $availableMap[$barangId]);
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

        return $detailStockInfo;
    }

    private function getAvailableStockMap(array $barangIds): array
    {
        $ids = collect($barangIds)->map(fn($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = Barang::leftJoin('request_details', 'barangs.id', '=', 'request_details.barang_id')
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
            // Jangan clamp ke 0 di sini; dipakai untuk rekonstruksi stok saat cetak PDF.
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

        // pertahankan format nomor dokumen, ganti "/" jadi "-"
        return strtoupper(str_replace('/', '-', $base));
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

        $emails = collect();

        if ($targetRole === 'SM') {
            $emails = User::where('role', 'SM')
                ->whereNotNull('email')
                ->pluck('email');
        } else {
            $approverUserIds = DivisionApprover::where('division_id', $header->user->division_id)
                ->where('role', $targetRole)
                ->whereNotNull('user_id')
                ->pluck('user_id');

            $emails = User::whereIn('id', $approverUserIds)
                ->whereNotNull('email')
                ->pluck('email');
        }

        $emails = $emails
            ->map(fn($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            Log::warning('Tidak ada email PIC approval untuk notifikasi request', [
                'request_id' => $header->id,
                'target_role' => $targetRole,
            ]);

            $emails = collect([self::EDP_NOTIFICATION_EMAIL]);
        }

        $ccEmails = collect([self::EDP_NOTIFICATION_EMAIL])
            ->reject(fn($email) => $emails->contains($email))
            ->values();

        $mailData = [
            'subject' => "Permintaan Approval {$targetRole} - {$header->nomor_dokumen}",
            'request' => $header,
            'requester_name' => $header->user->name,
            'division_name' => $header->user->division->nama_divisi ?? '-',
            'target_role' => $targetRole,
        ];

        try {
            $mail = Mail::to($emails->all());

            if ($ccEmails->isNotEmpty()) {
                $mail->cc($ccEmails->all());
            }

            $mail->send(new SystemNotificationMail($mailData, 'request'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim notifikasi request approval', [
                'request_id' => $header->id,
                'target_role' => $targetRole,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ===============================
    // FORM CREATE
    // ===============================
    public function create()
    {
        $barangs = Barang::leftJoin('request_details', 'barangs.id', '=', 'request_details.barang_id')
            ->leftJoin('request_headers', 'request_details.request_id', '=', 'request_headers.id')
            ->select(
                'barangs.*',
                DB::raw("
                    COALESCE(SUM(
                        CASE 
                            WHEN request_headers.status IN (0,1,2) OR request_headers.status IS NULL
                            THEN request_details.qty
                            ELSE 0
                        END
                    ),0) as total_request
                "),
                DB::raw("
                    GREATEST(
                        barangs.stok - COALESCE(SUM(
                            CASE 
                                WHEN request_headers.status IN (0,1,2) OR request_headers.status IS NULL
                                THEN request_details.qty
                                ELSE 0
                            END
                        ),0),
                        0
                    ) as stok_tersedia
                ")
            )
            ->groupBy(
                'barangs.id',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.fraction',
                'barangs.unit',
                'barangs.stok',
                'barangs.harga_estimasi',
                'barangs.created_at',
                'barangs.updated_at'
            )
            ->get();

        return view('request.create', compact('barangs'));
    }

    public function stock($id)
    {
        $barang = Barang::findOrFail($id);

        $totalRequest = RequestDetail::join('request_headers', 'request_headers.id', '=', 'request_details.request_id')
            ->where('request_details.barang_id', $barang->id)
            ->whereIn('request_headers.status', [0, 1, 2])
            ->sum('request_details.qty');

        $stokTersedia = max(0, (int) $barang->stok - (int) $totalRequest);

        return response()->json([
            'barang_id' => (int) $barang->id,
            'stok_asli' => (int) $barang->stok,
            'total_request' => (int) $totalRequest,
            'stok_tersedia' => (int) $stokTersedia,
            'unit' => $barang->unit,
        ]);
    }


    // ===============================
    // STORE REQUEST (REQ NUMBER)
    // ===============================
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_request' => 'required|date',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'items.*.harga_manual' => 'nullable|integer',
        ]);

        $divisionId = auth()->user()->division_id;

        $hasSJM = DivisionApprover::where('division_id', $divisionId)
            ->where('role', 'SJM')
            ->exists();

        $level = $hasSJM ? 1 : 2;

        $year = date('Y', strtotime($request->tanggal_request));
        $month = date('m', strtotime($request->tanggal_request));

        $last = RequestHeader::whereYear('tanggal_request', $year)
            ->whereMonth('tanggal_request', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;

        if ($last && $last->nomor_dokumen) {
            preg_match('/(\d+)$/', $last->nomor_dokumen, $matches);
            $number = intval($matches[1]) + 1;
        }

        $formatted = str_pad($number, 4, '0', STR_PAD_LEFT);
        $nomorDokumen = "REQ/GA/$year/$month/$formatted";

        $header = RequestHeader::create([
            'user_id' => Auth::id(),
            'tanggal_request' => $request->tanggal_request,
            'status' => 0,
            'current_approval_level' => $level,
            'nomor_dokumen' => $nomorDokumen,
        ]);

        foreach ($request->items as $i => $item) {

            $imagePath = null;

            // 🔥 AMBIL FILE DARI REQUEST
            if ($request->hasFile("items.$i.image")) {
                $file = $request->file("items.$i.image");
                $imagePath = $file->store('request_images', 'public');
            }

            RequestDetail::create([
                'request_id' => $header->id,
                'barang_id' => $item['barang_id'] ?? null,
                'qty' => $item['qty'],
                'keterangan' => $item['keterangan'] ?? null,
                'harga_manual' => $item['harga_manual'] ?? null,
                'image' => $imagePath,
            ]);
        }

        $this->sendApprovalNotification($header);

        return redirect()->route('request.index')
            ->with('success', 'Request berhasil dikirim');
    }


    // ===============================
    // LIST REQUEST
    // ===============================
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = RequestHeader::with(['user.division', 'details.barang']);

        // 🔍 SEARCH
        if ($request->search) {
            $search = $request->search;

            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }

        // 🔥 ROLE FILTER (TETAP DIPAKAI)
        if ($user->role == 'ADMIN') {

            // 🔥 ADMIN LIHAT SEMUA
            // tidak perlu filter

        } elseif ($user->role == 'SM') {

            $query->where('current_approval_level', 3)
                ->where('status', 0);

        } elseif ($user->role == 'SJM' || $user->role == 'SAM') {

            $allowedDivisions = DivisionApprover::where('user_id', $user->id)
                ->pluck('division_id');

            $level = $user->role == 'SJM' ? 1 : 2;

            $query->where('current_approval_level', $level)
                ->where('status', 0)
                ->whereHas('user', function ($q) use ($allowedDivisions) {
                    $q->whereIn('division_id', $allowedDivisions);
                });

        } elseif ($user->role == 'PGA') {

            $query->whereIn('status', [1, 2]);

        } else {

            // USER BIASA
            $query->where('user_id', $user->id);
        }

        // 🔥 PAGINATION (INI PENTING)
        $requests = $query->latest()->paginate(10)->withQueryString();

        if ($user->role == 'ADMIN') {
            return view('admin.request.index', compact('requests'));
        } else {
            return view('request.index', compact('requests'));
        }
    }


    // ===============================
    // PROSES
    // ===============================
    public function proses(Request $request, $id)
    {
        $req = RequestHeader::with(['details.barang'])->findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'PGA') {
            abort(403);
        }

        if ((int) $req->status !== 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Request tidak bisa diproses karena status tidak sesuai',
                ], 422);
            }

            return redirect()->route('request.index')
                ->with('error', 'Request tidak bisa diproses karena status tidak sesuai');
        }

        $req->status = 2;
        $req->save();

        // Simpan snapshot perhitungan checklist agar serah-terima bisa memakai angka yang sama.
        $detailStockSnapshot = $this->buildDetailStockInfo($req, false);
        Cache::put("request_detail_stock_snapshot:{$req->id}", $detailStockSnapshot, now()->addDays(2));

        $docChecklist = strtoupper(str_replace('/', '-', $req->nomor_dokumen ?? 'CHECKLIST-REQUEST'));

        $pdfUrl = route('request.pdf', ['id' => $req->id, 'doc' => $docChecklist]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'PGA segera proses permintaan barang',
                'pdf_url' => $pdfUrl,
            ]);
        }

        return redirect()->route('request.index')->with([
            'success' => 'PGA segera proses permintaan barang',
            'print_checklist' => $req->id,
            'print_checklist_doc' => $docChecklist,
        ]);
    }


    // ===============================
    // SELESAI (ST NUMBER)
    // ===============================
    public function selesai(Request $request, $id)
    {
        $req = RequestHeader::with('details')->findOrFail($id);
        $user = auth()->user();
        $detailStockInfoAtSerah = [];

        if ($user->role !== 'PGA') {
            abort(403);
        }

        if ((int) $req->status !== 2) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Request tidak bisa diselesaikan karena status tidak sesuai',
                ], 422);
            }

            return redirect()->route('request.index')
                ->with('error', 'Request tidak bisa diselesaikan karena status tidak sesuai');
        }


        //TAMBAHAN: POTONG STOK
        // 
        if ($req->details) {
            foreach ($req->details as $detail) {

                $barang = Barang::find($detail->barang_id);

                if ($barang) {
                    $stokSebelum = (int) $barang->stok;
                    $kurang = max(0, (int) $detail->qty - $stokSebelum);
                    $harga = (int) ($detail->harga_manual ?? ($barang->harga_estimasi ?? 0));
                    $estimasi = $kurang * $harga;

                    $detailStockInfoAtSerah[$detail->id] = [
                        'stok_realtime' => max(0, $stokSebelum),
                        'kurang' => $kurang,
                        'harga_satuan' => $harga,
                        'estimasi' => $estimasi,
                    ];

                    // ambil stok yang tersedia saja (biar tidak minus)
                    $ambil = min($stokSebelum, (int) $detail->qty);

                    $barang->stok -= $ambil;

                    $barang->save();
                }
            }
        }

        $year = date('Y');
        $month = date('m');

        $last = RequestHeader::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('nomor_serah')
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;

        if ($last && $last->nomor_serah) {
            preg_match('/(\d+)$/', $last->nomor_serah, $matches);
            $number = intval($matches[1]) + 1;
        }

        $formatted = str_pad($number, 4, '0', STR_PAD_LEFT);
        $nomorSerah = "ST/GA/$year/$month/$formatted";

        $req->status = 3;
        $req->nomor_serah = $nomorSerah;
        $req->save();

        $docSerah = strtoupper(str_replace('/', '-', $nomorSerah));
        $existingSnapshot = Cache::get("request_detail_stock_snapshot:{$req->id}");
        if (!is_array($existingSnapshot)) {
            Cache::put("request_detail_stock_snapshot:{$req->id}", $detailStockInfoAtSerah, now()->addDays(2));
        }
        $stockToken = (string) Str::uuid();
        Cache::put("serah_stock_info:$stockToken", $detailStockInfoAtSerah, now()->addMinutes(30));
        $pdfUrl = route('request.pdf.serah', ['id' => $req->id, 'doc' => $docSerah]) . '?token=' . $stockToken;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Permintaan barang selesai diproses',
                'pdf_url' => $pdfUrl,
            ]);
        }

        return redirect()->route('request.index')->with([
                'print_serah' => $req->id,
                'print_serah_doc' => $docSerah,
                'print_serah_token' => $stockToken,
                'success' => 'Permintaan barang selesai diproses'
            ]);
    }


    // ===============================
    // PDF CHECKLIST
    // ===============================
    public function pdfChecklist($id, $doc = null)
    {
        $req = RequestHeader::with(['user.division', 'details.barang'])
            ->findOrFail($id);

        $docBenar = $this->formatDocForUrl($req->nomor_dokumen, 'CHECKLIST-REQUEST');
        if ($doc !== $docBenar) {
            return redirect()->route('request.pdf', ['id' => $req->id, 'doc' => $docBenar]);
        }

        // Untuk checklist PGA, gunakan stok antrian:
        // stok master dikurangi request APPROVED/DIPROSES yang lebih dulu dari request ini.
        $snapshot = Cache::get("request_detail_stock_snapshot:{$req->id}");
        $detailStockInfo = is_array($snapshot)
            ? $snapshot
            : $this->buildDetailStockInfo($req, false);

        $pdf = Pdf::loadView('pdf.checklist', compact('req', 'detailStockInfo'));

        $filename = $docBenar . '-CHECKLIST.pdf';

        return $pdf->stream($filename);
    }


    // ===============================
    // PDF SERAH TERIMA
    // ===============================
    public function pdfSerah(Request $request, $id, $doc = null)
    {
        $req = RequestHeader::with(['user.division', 'details.barang'])
            ->findOrFail($id);

        $nomorUntukSerah = $req->nomor_serah ?: $req->nomor_dokumen;
        $docBenar = $this->formatDocForUrl($nomorUntukSerah, 'SERAH-TERIMA');
        $token = (string) $request->query('token', '');
        if ($doc !== $docBenar) {
            $url = route('request.pdf.serah', ['id' => $req->id, 'doc' => $docBenar]);
            if ($token !== '') {
                $url .= '?token=' . urlencode($token);
            }
            return redirect()->to($url);
        }

        // Serah terima harus konsisten dengan checklist.
        // Karena status selesai sudah memotong stok, qty request ini dikembalikan dulu (add-back)
        // agar hasil estimasinya sama dengan saat checklist dicetak.
        $detailStockInfoFromToken = $token !== '' ? Cache::pull("serah_stock_info:$token") : null;
        $detailStockInfoFromChecklist = Cache::get("request_detail_stock_snapshot:{$req->id}");
        $detailStockInfo = is_array($detailStockInfoFromChecklist)
            ? $detailStockInfoFromChecklist
            : (is_array($detailStockInfoFromToken)
                ? $detailStockInfoFromToken
                : $this->buildDetailStockInfo($req, true));

        $pdf = Pdf::loadView('pdf.serah', compact('req', 'detailStockInfo'));

        $filename = $docBenar . '.pdf';

        return $pdf->stream($filename);
    }
}
