<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SystemNotificationMail;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Throwable;

class BarangController extends Controller
{
    private const EDP_NOTIFICATION_EMAIL = 'edp@gto.indogrosir.co.id';

    private function getReservedQty(int $barangId): int
    {
        $requestQty = (int) DB::table('request_details')
            ->join('request_headers', 'request_headers.id', '=', 'request_details.request_id')
            ->where('request_details.barang_id', $barangId)
            ->whereIn('request_headers.status', [0, 1, 2])
            ->sum('request_details.qty');

        $claimQty = (int) DB::table('request_claim_details')
            ->join('request_claims', 'request_claims.id', '=', 'request_claim_details.claim_id')
            ->where('request_claim_details.barang_id', $barangId)
            ->whereIn('request_claims.status', [0, 1])
            ->sum('request_claim_details.qty');

        return $requestQty + $claimQty;
    }

    private function notifyStockAvailable(Barang $barang, int $stokTersedia): void
    {
        $emailsFromClaims = DB::table('request_claims')
            ->join('request_claim_details', 'request_claim_details.claim_id', '=', 'request_claims.id')
            ->join('users', 'users.id', '=', 'request_claims.user_id')
            ->where('request_claim_details.barang_id', $barang->id)
            ->whereIn('request_claims.status', [0, 1])
            ->whereNotNull('users.email')
            ->pluck('users.email');

        $emailsFromRequests = DB::table('request_headers')
            ->join('request_details', 'request_details.request_id', '=', 'request_headers.id')
            ->join('users', 'users.id', '=', 'request_headers.user_id')
            ->where('request_details.barang_id', $barang->id)
            ->where('request_headers.status', 1)
            ->whereNotNull('users.email')
            ->pluck('users.email');

        $emails = $emailsFromClaims
            ->merge($emailsFromRequests)
            ->map(fn($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $emails = collect([self::EDP_NOTIFICATION_EMAIL]);
        }

        $mailData = [
            'subject' => "Update Stok Tersedia - {$barang->nama_barang}",
            'from_name' => 'IGR - Update Stok Barang',
            'stock_item_name' => $barang->nama_barang,
            'stock_available' => $stokTersedia,
            'stock_unit' => $barang->unit,
        ];

        try {
            Mail::to($emails->all())->send(new SystemNotificationMail($mailData, 'stock'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim notifikasi update stok tersedia', [
                'barang_id' => $barang->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $q = $request->search;

        $barangs = Barang::when($q, function ($query) use ($q) {
            $query->where('nama_barang', 'like', "%$q%")
                ->orWhere('kode_barang', 'like', "%$q%");
        })
            ->latest()
            ->paginate(10);

        return view('admin.barang.index', compact('barangs'));
    }

    public function create()
    {
        return view('admin.barang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|unique:barangs',
            'nama_barang' => 'required',
            'fraction' => 'required|numeric',
            'unit' => 'required',
            'stok' => 'required|numeric',
            'harga_estimasi' => 'required|numeric'
        ], [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.unique' => 'Kode barang sudah terpakai, silakan gunakan kode lain.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'fraction.required' => 'Fraction wajib diisi.',
            'fraction.numeric' => 'Fraction harus berupa angka.',
            'unit.required' => 'Unit wajib diisi.',
            'stok.required' => 'Stok wajib diisi.',
            'stok.numeric' => 'Stok harus berupa angka.',
            'harga_estimasi.required' => 'Harga estimasi wajib diisi.',
            'harga_estimasi.numeric' => 'Harga estimasi harus berupa angka.'
        ]);

        Barang::create($request->all());

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil ditambahkan');
    }

    public function edit($id)
    {
        $barang = Barang::findOrFail($id);
        return view('admin.barang.edit', compact('barang'));
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $stokSebelum = (int) $barang->stok;
        $reservedQty = $this->getReservedQty((int) $barang->id);
        $availableBefore = $stokSebelum - $reservedQty;

        $request->validate([
            'kode_barang' => 'required|unique:barangs,kode_barang,' . $id,
            'nama_barang' => 'required',
            'fraction' => 'required|numeric',
            'unit' => 'required',
            'stok' => 'required|numeric',
            'harga_estimasi' => 'required|numeric'
        ]);

        $barang->update($request->all());

        $stokSesudah = (int) $barang->fresh()->stok;
        $availableAfter = $stokSesudah - $reservedQty;

        if ($availableBefore <= 0 && $availableAfter > 0) {
            $this->notifyStockAvailable($barang->fresh(), $availableAfter);
        }

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        Barang::findOrFail($id)->delete();

        return back()->with('success', 'Barang berhasil dihapus');
    }
}
