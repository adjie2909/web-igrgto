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

class RequestController extends Controller
{

    // ===============================
    // FORM CREATE
    // ===============================
    public function create()
    {
        $barangs = Barang::all();
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

        foreach ($request->items as $item) {
            if (!empty($item['qty'])) {
                RequestDetail::create([
                    'request_id' => $header->id,
                    'barang_id' => $item['barang_id'] ?? null,
                    'qty' => $item['qty'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]);
            }
        }

        return redirect()->route('request.index')
            ->with('success', 'Request berhasil dikirim');
    }


    // ===============================
    // LIST REQUEST
    // ===============================
    public function index()
    {
        $user = auth()->user();

        if ($user->role == 'SM') {

            $requests = RequestHeader::with(['user.division', 'details.barang'])
                ->where('current_approval_level', 3)
                ->where('status', 0)
                ->get();

        } elseif ($user->role == 'SJM' || $user->role == 'SAM') {

            $allowedDivisions = DivisionApprover::where('user_id', $user->id)
                ->pluck('division_id');

            $level = $user->role == 'SJM' ? 1 : 2;

            $requests = RequestHeader::with(['user.division', 'details.barang'])
                ->where('current_approval_level', $level)
                ->where('status', 0)
                ->whereHas('user', function ($q) use ($allowedDivisions) {
                    $q->whereIn('division_id', $allowedDivisions);
                })
                ->get();

        } elseif ($user->role == 'PGA') {

            $requests = RequestHeader::with(['user.division', 'details.barang'])
                ->whereIn('status', [1, 2])
                ->get();

        } else {

            $requests = RequestHeader::with(['user.division', 'details.barang'])
                ->where('user_id', $user->id)
                ->get();
        }

        return view('request.index', compact('requests'));
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
            ->with('success', 'Request sedang diproses');
    }


    // ===============================
    // SELESAI (ST NUMBER)
    // ===============================
    public function selesai($id)
    {
        $req = RequestHeader::findOrFail($id);

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

        return redirect()->route('request.index')
            ->with('success', 'Request selesai');
    }


    // ===============================
    // PDF CHECKLIST
    // ===============================
    public function pdfChecklist($id)
    {
        $req = RequestHeader::with(['user.division', 'details.barang'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.checklist', compact('req'));

        return $pdf->stream('checklist-request.pdf');
    }


    // ===============================
    // PDF SERAH TERIMA
    // ===============================
    public function pdfSerah($id)
    {
        $req = RequestHeader::with(['user.division', 'details.barang'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.serah', compact('req'));

        return $pdf->stream('serah-terima.pdf');
    }
}