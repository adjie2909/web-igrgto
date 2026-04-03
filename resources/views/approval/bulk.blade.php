@extends('layouts.app')

@section('content')

    <div class="card">

        <h2 style="margin-bottom:20px;">Bulk Approval</h2>

        <form method="POST" action="{{ route('approval.bulk.process') }}">
            @csrf
            @php
                $totalItem = 0;
                $totalQty = 0;
                $totalEstimasi = 0;
            @endphp
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>NoDoc</th>
                        <th>User</th>
                        <th>Divisi</th>
                        <th>Barang</th>
                        <th>Keterangan</th>
                        <th>Qty</th>
                        <th>Status Stok</th>
                        <th>Estimasi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($requests as $req)
                        @foreach($req->details as $d)

                            @php
                                $stok = $d->barang->stok ?? 0;
                                $kurang = max(0, $d->qty - $stok);
                                $harga = $d->harga_manual 
                                    ?? ($d->barang->harga_estimasi ?? 0);

                                $estimasi = $kurang * $harga;
                            @endphp

                            <tr>
                                <td>
                                    <input type="checkbox" 
                                        class="check-item"
                                        name="ids[]" 
                                        value="{{ $req->id }}"
                                        data-qty="{{ $d->qty }}"
                                        data-estimasi="{{ $estimasi }}">
                                </td>

                                <td>{{ $req->nomor_dokumen }}</td>
                                <td>{{ $req->user->name }}</td>
                                <td>{{ $req->user->division->nama_divisi ?? '-' }}</td>
                                <td>{{ $d->barang->nama_barang ?? '-' }}</td>
                                <td>{{ $d->keterangan ?? '-' }}</td>
                                <td>{{ $d->qty }}</td>

                                <td>
                                    @if($kurang > 0)
                                        <span style="color:red;">Kurang</span>
                                    @else
                                        <span style="color:green;">Ada stok</span>
                                    @endif
                                </td>

                                <td>Rp {{ number_format($estimasi, 0, ',', '.') }}</td>
                            </tr>
                            @php
                                $stok = $d->barang->stok ?? 0;
                                $kurang = max(0, $d->qty - $stok);

                                $harga = $d->harga_manual 
                                    ?? ($d->barang->harga_estimasi ?? 0);

                                $estimasi = $kurang * $harga;

                                $totalItem++;
                                $totalQty += $d->qty;
                                $totalEstimasi += $estimasi;
                            @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="card" style="margin-top:20px; padding:15px; background:#f8fafc;">

                <h3 style="margin-bottom:10px;">Ringkasan (Total yang akan di-approve)</h3>

                <div style="display:flex; gap:40px; font-size:16px;">

                    <div>
                        <b>Total Item:</b><br>
                        <span id="totalItem">0</span>
                    </div>

                    <div>
                        <b>Total Qty:</b><br>
                        <span id="totalQty">0</span>
                    </div>

                    <div>
                        <b>Total Estimasi:</b><br>
                        <span id="totalEstimasi" style="color:green;">
                            Rp 0
                        </span>
                    </div>

                </div>

            </div>
            <button type="submit" 
                class="btn btn-blue" 
                style="margin-top:20px;"
                onclick="return confirm('Yakin approve? Yang tidak dicentang akan di-reject')">
                Approve
            </button>
            <p>*List yang tidak di-approve auto Reject</p>

        </form>

    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline">
        Kembali
    </a>
<script>

document.addEventListener('DOMContentLoaded', function(){

    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.check-item');

    const totalItemEl = document.getElementById('totalItem');
    const totalQtyEl = document.getElementById('totalQty');
    const totalEstimasiEl = document.getElementById('totalEstimasi');

    // =========================
    // FORMAT RUPIAH
    // =========================
    function formatRupiah(angka){
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    // =========================
    // HITUNG TOTAL
    // =========================
    function hitungTotal(){

        let totalItem = 0;
        let totalQty = 0;
        let totalEstimasi = 0;

        checkboxes.forEach(cb => {
            if(cb.checked){
                totalItem++;
                totalQty += parseInt(cb.dataset.qty || 0);
                totalEstimasi += parseInt(cb.dataset.estimasi || 0);
            }
        });

        totalItemEl.innerText = totalItem;
        totalQtyEl.innerText = totalQty;
        totalEstimasiEl.innerText = formatRupiah(totalEstimasi);
    }

    // =========================
    // CHECK ALL
    // =========================
    if(checkAll){
        checkAll.addEventListener('change', function(){
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
            hitungTotal();
        });
    }

    // =========================
    // CHECK PER ITEM
    // =========================
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(){

            // update checkAll status
            checkAll.checked = [...checkboxes].every(c => c.checked);

            hitungTotal();
        });
    });

});

</script>
@endsection