<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Division;
use App\Models\RequestClaim;
use App\Models\RequestClaimDetail;
use App\Models\RequestDetail;
use App\Models\RequestHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasColumn('request_headers', 'nomor_dokumen')) {
            Schema::table('request_headers', function (Blueprint $table) {
                $table->string('nomor_dokumen')->nullable();
            });
        }

        if (!Schema::hasColumn('request_headers', 'nomor_serah')) {
            Schema::table('request_headers', function (Blueprint $table) {
                $table->string('nomor_serah')->nullable();
            });
        }

        if (!Schema::hasColumn('barangs', 'fraction')) {
            Schema::table('barangs', function (Blueprint $table) {
                $table->integer('fraction')->default(1);
            });
        }

        if (!Schema::hasColumn('barangs', 'unit')) {
            Schema::table('barangs', function (Blueprint $table) {
                $table->string('unit')->nullable();
            });
        }

        if (!Schema::hasColumn('barangs', 'harga_estimasi')) {
            Schema::table('barangs', function (Blueprint $table) {
                $table->bigInteger('harga_estimasi')->default(0);
            });
        }

        if (!Schema::hasColumn('request_details', 'harga_manual')) {
            Schema::table('request_details', function (Blueprint $table) {
                $table->bigInteger('harga_manual')->nullable();
            });
        }
    }

    public function test_claim_store_rejects_when_requested_qty_exceeds_available_stock(): void
    {
        $division = Division::create([
            'nama_divisi' => 'IT',
        ]);

        $user = User::factory()->create([
            'role' => 'USER',
            'division_id' => $division->id,
        ]);

        $barang = Barang::create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Mouse',
            'fraction' => 1,
            'unit' => 'pcs',
            'stok' => 6,
            'harga_estimasi' => 100000,
        ]);

        $header = RequestHeader::create([
            'user_id' => $user->id,
            'tanggal_request' => now()->toDateString(),
            'status' => 1,
            'current_approval_level' => 3,
            'nomor_dokumen' => 'REQ/GA/2026/04/0001',
        ]);

        $detail = RequestDetail::create([
            'request_id' => $header->id,
            'barang_id' => $barang->id,
            'qty' => 8,
            'harga_manual' => 100000,
        ]);

        $response = $this->actingAs($user)->post(route('request-claim.store'), [
            'items' => [
                [
                    'request_detail_id' => $detail->id,
                    'qty' => 8,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'melebihi stok tersedia',
            (string) session('error')
        );

        $this->assertDatabaseCount('request_claims', 0);
        $this->assertDatabaseCount('request_claim_details', 0);
    }

    public function test_claim_complete_rejects_when_stock_is_insufficient(): void
    {
        $division = Division::create([
            'nama_divisi' => 'IT',
        ]);

        $requester = User::factory()->create([
            'role' => 'USER',
            'division_id' => $division->id,
        ]);

        $pga = User::factory()->create([
            'userid' => 'PGA001',
            'role' => 'PGA',
            'division_id' => $division->id,
        ]);

        $barang = Barang::create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Keyboard',
            'fraction' => 1,
            'unit' => 'pcs',
            'stok' => 6,
            'harga_estimasi' => 150000,
        ]);

        $header = RequestHeader::create([
            'user_id' => $requester->id,
            'tanggal_request' => now()->toDateString(),
            'status' => 1,
            'current_approval_level' => 3,
            'nomor_dokumen' => 'REQ/GA/2026/04/0002',
        ]);

        $detail = RequestDetail::create([
            'request_id' => $header->id,
            'barang_id' => $barang->id,
            'qty' => 8,
            'harga_manual' => 150000,
        ]);

        $claim = RequestClaim::create([
            'request_id' => $header->id,
            'user_id' => $requester->id,
            'tanggal_claim' => now()->toDateString(),
            'nomor_claim' => 'PB/GA/2026/04/0001',
            'status' => 1,
            'processed_by' => $pga->id,
            'processed_at' => now(),
        ]);

        RequestClaimDetail::create([
            'claim_id' => $claim->id,
            'request_detail_id' => $detail->id,
            'barang_id' => $barang->id,
            'qty' => 8,
        ]);

        $response = $this->actingAs($pga)->postJson(route('request-claim.complete', $claim->id));

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Stok Keyboard tidak cukup. Tersedia 6 pcs, diminta 8 pcs.',
            ]);

        $this->assertDatabaseHas('request_claims', [
            'id' => $claim->id,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('barangs', [
            'id' => $barang->id,
            'stok' => 6,
        ]);
    }
}
