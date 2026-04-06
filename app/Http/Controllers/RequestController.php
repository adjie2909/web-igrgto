<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RequestHeader;
use App\Models\RequestDetail;
use App\Models\DivisionApprover;
use App\Models\Barang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    private function formatDocForUrl(?string $nomor, string $fallback): string
    {
        $base = trim((string) $nomor);
        if ($base === '') {
            $base = $fallback;
        }

        // pertahankan format nomor dokumen, ganti "/" jadi "-"
        return strtoupper(str_replace('/', '-', $base));
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
    public function proses($id)
    {
        $req = RequestHeader::findOrFail($id);

        $req->status = 2;
        $req->save();

        return redirect()->route('request.index')
            ->with('success', 'PGA segera proses permintaan barang');
    }


    // ===============================
    // SELESAI (ST NUMBER)
    // ===============================
    public function selesai($id)
    {
        $req = RequestHeader::with('details')->findOrFail($id);


        //TAMBAHAN: POTONG STOK
        // 
        if ($req->details) {
            foreach ($req->details as $detail) {

                $barang = Barang::find($detail->barang_id);

                if ($barang) {

                    // ambil stok yang tersedia saja (biar tidak minus)
                    $ambil = min($barang->stok, $detail->qty);

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

        return redirect()->route('request.index')->with([
                'print_serah' => $req->id,
                'print_serah_doc' => strtoupper(str_replace('/', '-', $nomorSerah)),
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

        $pdf = Pdf::loadView('pdf.checklist', compact('req'));

        $filename = $docBenar . '-CHECKLIST.pdf';

        return $pdf->stream($filename);
    }


    // ===============================
    // PDF SERAH TERIMA
    // ===============================
    public function pdfSerah($id, $doc = null)
    {
        $req = RequestHeader::with(['user.division', 'details.barang'])
            ->findOrFail($id);

        $nomorUntukSerah = $req->nomor_serah ?: $req->nomor_dokumen;
        $docBenar = $this->formatDocForUrl($nomorUntukSerah, 'SERAH-TERIMA');
        if ($doc !== $docBenar) {
            return redirect()->route('request.pdf.serah', ['id' => $req->id, 'doc' => $docBenar]);
        }

        $pdf = Pdf::loadView('pdf.serah', compact('req'));

        $filename = $docBenar . '.pdf';

        return $pdf->stream($filename);
    }
}
