<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
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

        $request->validate([
            'kode_barang' => 'required|unique:barangs,kode_barang,' . $id,
            'nama_barang' => 'required',
            'fraction' => 'required|numeric',
            'unit' => 'required',
            'stok' => 'required|numeric',
            'harga_estimasi' => 'required|numeric'
        ]);

        $barang->update($request->all());

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        Barang::findOrFail($id)->delete();

        return back()->with('success', 'Barang berhasil dihapus');
    }
}