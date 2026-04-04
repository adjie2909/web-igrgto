<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketReply;

class TicketController extends Controller
{
    // 🔥 CONSTANT DIVISI
    const DIV_EDP = 9;
    const DIV_PGA = 10;
    const DIV_ADMIN = 14;

    public function index()
    {
        $user = auth()->user();
        $divisionId = $user->division_id;

        // =========================
        // 🔥 DIVISI KHUSUS (LIHAT SEMUA + GROUP)
        // =========================
        if (in_array($divisionId, [9, 10, 14])) {

            $tickets = Ticket::with('user.division')
                ->latest()
                ->get();

            $groupedTickets = $tickets->groupBy(function ($t) {
                return $t->user->division->nama_divisi ?? 'LAINNYA';
            });

        } else {

            // =========================
            // 🔥 DIVISI BIASA (HANYA SENDIRI)
            // =========================
            $tickets = Ticket::with('user.division')
                ->whereHas('user', function ($q) use ($divisionId) {
                    $q->where('division_id', $divisionId);
                })
                ->latest()
                ->get();

            $groupedTickets = collect(); // biar aman
        }

        // =========================
        // SUMMARY
        // =========================
        $total = $tickets->count();
        $open = $tickets->where('status', 0)->count();
        $proses = $tickets->where('status', 1)->count();
        $selesai = $tickets->where('status', 2)->count();

        return view('ticket.index', compact(
            'tickets',
            'groupedTickets',
            'total',
            'open',
            'proses',
            'selesai',
            'divisionId'
        ));
    }

    public function create()
    {
        return view('ticket.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
        ]);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'status' => 0, // open
            'level' => 1, // 🔥 masuk ke EDP dulu
            'current_handler' => 'EDP'
        ]);

        return redirect()->route('ticket.index')
            ->with('success', 'Ticket berhasil dibuat');
    }

    public function show($id)
    {
        $ticket = Ticket::with('replies.user')->findOrFail($id);
        $user = auth()->user();

        // =========================
        // 🔥 AUTO UPDATE STATUS
        // =========================
        if (
            $user->division_id == 9 &&   // EDP
            $ticket->status == 0         // masih OPEN
        ) {
            $ticket->status = 1; // jadi DIPROSES
            $ticket->save();
        }

        return view('ticket.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // 🔥 CEGAH REPLY JIKA SUDAH CLOSED
        if ($ticket->status == 2) {
            return back()->with('error', 'Ticket sudah ditutup, tidak bisa diskusi lagi');
        }

        $request->validate([
            'message' => 'required'
        ]);

        TicketReply::create([
            'ticket_id' => $id,
            'user_id' => auth()->id(),
            'message' => $request->message
        ]);

        return back()->with('success', 'Balasan dikirim');
    }

    public function escalate($id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status == 2) {
            return back()->with('error', 'Ticket sudah ditutup');
        }

        $divisionId = auth()->user()->division_id;

        if ($divisionId != self::DIV_EDP) {
            abort(403);
        }

        $ticket->level = 2;
        $ticket->current_handler = 'PGA';
        $ticket->status = 1;

        $ticket->save();

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => 'Ticket dieskalasi ke PGA'
        ]);

        return back()->with('success', 'Ticket berhasil dieskalasi ke PGA');
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);

        // 🔥 kalau sudah closed, skip
        if ($ticket->status == 2) {
            return back()->with('error', 'Ticket sudah ditutup');
        }

        $ticket->status = 2;
        $ticket->save();

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => 'Ticket ditutup'
        ]);

        return back()->with('success', 'Ticket ditutup');
    }
}