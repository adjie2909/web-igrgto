<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StockAvailabilityNotifier
{
    private const EDP_NOTIFICATION_EMAIL = 'edp@gto.indogrosir.co.id';

    public function notifyRestockedBarang(int $barangId, int $stokSebelum, int $stokSesudah): void
    {
        if ($stokSebelum > 0 || $stokSesudah <= 0) {
            return;
        }

        $barang = Barang::find($barangId);
        if (!$barang) {
            return;
        }

        $this->sendStockAvailableNotification($barang, $stokSesudah);
    }

    public function getAvailableStockMap(array $barangIds): array
    {
        $ids = collect($barangIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $requestMap = DB::table('request_details')
            ->join('request_headers', 'request_headers.id', '=', 'request_details.request_id')
            ->whereIn('request_details.barang_id', $ids->all())
            ->whereIn('request_headers.status', [0, 1, 2])
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

    public function notifyRecoveredItems(array $beforeMap, array $afterMap): void
    {
        $barangIds = collect(array_keys($beforeMap))
            ->merge(array_keys($afterMap))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($barangIds->isEmpty()) {
            return;
        }

        $barangs = Barang::whereIn('id', $barangIds->all())
            ->get()
            ->keyBy(fn($barang) => (int) $barang->id);

        foreach ($barangIds as $barangId) {
            $before = (int) ($beforeMap[$barangId] ?? 0);
            $after = (int) ($afterMap[$barangId] ?? 0);

            if ($before <= 0 && $after > 0) {
                $barang = $barangs->get($barangId);
                if ($barang) {
                    $this->sendStockAvailableNotification($barang, $after);
                }
            }
        }
    }

    private function sendStockAvailableNotification(Barang $barang, int $stokTersedia): void
    {
        $mailData = [
            'subject' => "Update Stok Tersedia - {$barang->nama_barang}",
            'from_name' => 'IGR - Update Stok Barang',
            'stock_item_name' => $barang->nama_barang,
            'stock_available' => $stokTersedia,
            'stock_unit' => $barang->unit,
        ];

        try {
            Mail::to([self::EDP_NOTIFICATION_EMAIL])->send(new SystemNotificationMail($mailData, 'stock'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim notifikasi update stok tersedia', [
                'barang_id' => $barang->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
