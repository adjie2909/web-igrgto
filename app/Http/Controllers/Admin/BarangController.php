<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Services\StockAvailabilityNotifier;
use Illuminate\Http\Request;

class BarangController extends Controller
{
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
        $stockNotifier = app(StockAvailabilityNotifier::class);
        $barang = Barang::findOrFail($id);
        $stokSebelum = (int) $barang->stok;

        $request->validate([
            'kode_barang' => 'required|unique:barangs,kode_barang,' . $id,
            'nama_barang' => 'required',
            'fraction' => 'required|numeric',
            'unit' => 'required',
            'stok' => 'required|numeric',
            'harga_estimasi' => 'required|numeric'
        ]);

        $barang->update($request->all());

        $stockNotifier->notifyRestockedBarang((int) $barang->id, $stokSebelum, (int) $barang->fresh()->stok);

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        Barang::findOrFail($id)->delete();

        return back()->with('success', 'Barang berhasil dihapus');
    }
}
