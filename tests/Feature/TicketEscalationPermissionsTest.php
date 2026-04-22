<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketEscalationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function seedDivision(int $id, string $name): void
    {
        DB::table('divisions')->insert([
            'id' => $id,
            'nama_divisi' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_edp_cannot_close_ticket_after_escalated_to_pga(): void
    {
        $this->seedDivision(9, 'EDP');
        $this->seedDivision(10, 'PGA');

        $edp = User::factory()->create([
            'role' => 'EDP',
            'division_id' => 9,
        ]);

        $requester = User::factory()->create([
            'role' => 'USER',
            'division_id' => 9,
        ]);

        $ticket = Ticket::create([
            'user_id' => $requester->id,
            'judul' => 'Printer error',
            'deskripsi' => 'Tidak bisa print',
            'status' => 1,
        ]);

        $ticket->forceFill([
            'level' => 2,
            'current_handler' => 'PGA',
        ])->save();

        $response = $this->actingAs($edp)->post(route('ticket.close', $ticket->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 1,
            'level' => 2,
            'current_handler' => 'PGA',
        ]);
    }

    public function test_pga_can_close_ticket_after_escalated_to_pga(): void
    {
        $this->seedDivision(9, 'EDP');
        $this->seedDivision(10, 'PGA');

        $pga = User::factory()->create([
            'role' => 'PGA',
            'division_id' => 10,
        ]);

        $requester = User::factory()->create([
            'role' => 'USER',
            'division_id' => 9,
        ]);

        $ticket = Ticket::create([
            'user_id' => $requester->id,
            'judul' => 'Network down',
            'deskripsi' => 'Wifi tidak bisa',
            'status' => 1,
        ]);

        $ticket->forceFill([
            'level' => 2,
            'current_handler' => 'PGA',
        ])->save();

        $response = $this->actingAs($pga)->post(route('ticket.close', $ticket->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 2,
        ]);
    }
}

