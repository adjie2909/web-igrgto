<?php

namespace App\Http\Controllers;

use App\Mail\SystemNotificationMail;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TicketController extends Controller
{
    const DIV_EDP = 9;
    const DIV_PGA = 10;
    const DIV_ADMIN = 14;
    const TEST_NOTIFICATION_EMAIL = 'edp@gto.indogrosir.co.id';

    private function sendTicketNotification(Ticket $ticket, string $event): void
    {
        $ticket->loadMissing('user.division');

        $eventLabel = match ($event) {
            'created' => 'Ticket Baru',
            'reply' => 'Balasan Ticket',
            'escalated' => 'Ticket Eskalasi ke PGA',
            'closed' => 'Ticket Ditutup',
            default => 'Update Ticket',
        };

        $mailData = [
            'subject' => "{$eventLabel} - #{$ticket->id} {$ticket->judul}",
            'ticket' => $ticket,
            'event' => $event,
            'requester_name' => $ticket->user->name ?? '-',
            'division_name' => $ticket->user->division->nama_divisi ?? '-',
        ];

        try {
            Mail::to([self::TEST_NOTIFICATION_EMAIL])->send(new SystemNotificationMail($mailData, 'ticket'));
        } catch (Throwable $e) {
            Log::warning('Gagal kirim notifikasi ticket', [
                'ticket_id' => $ticket->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function index()
    {
        $user = auth()->user();
        $divisionId = $user->division_id;

        if (in_array($divisionId, [self::DIV_EDP, self::DIV_PGA, self::DIV_ADMIN])) {
            $tickets = Ticket::with('user.division')
                ->latest()
                ->get();

            $groupedTickets = $tickets->groupBy(function ($t) {
                return $t->user->division->nama_divisi ?? 'LAINNYA';
            });
        } else {
            $tickets = Ticket::with('user.division')
                ->whereHas('user', function ($q) use ($divisionId) {
                    $q->where('division_id', $divisionId);
                })
                ->latest()
                ->get();

            $groupedTickets = collect();
        }

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
            'status' => 0,
            'level' => 1,
            'current_handler' => 'EDP',
        ]);

        $this->sendTicketNotification($ticket, 'created');

        return redirect()->route('ticket.index')
            ->with('success', 'Ticket berhasil dibuat');
    }

    public function show($id)
    {
        $ticket = Ticket::with('replies.user')->findOrFail($id);
        $user = auth()->user();

        if ($user->division_id == self::DIV_EDP && $ticket->status == 0) {
            $ticket->status = 1;
            $ticket->save();
        }

        return view('ticket.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status == 2) {
            return back()->with('error', 'Ticket sudah ditutup, tidak bisa diskusi lagi');
        }

        $request->validate([
            'message' => 'required',
        ]);

        TicketReply::create([
            'ticket_id' => $id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        $this->sendTicketNotification($ticket, 'reply');

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
            'message' => 'Ticket dieskalasi ke PGA',
        ]);

        $this->sendTicketNotification($ticket, 'escalated');

        return back()->with('success', 'Ticket berhasil dieskalasi ke PGA');
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status == 2) {
            return back()->with('error', 'Ticket sudah ditutup');
        }

        $ticket->status = 2;
        $ticket->save();

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => 'Ticket ditutup',
        ]);

        $this->sendTicketNotification($ticket, 'closed');

        return back()->with('success', 'Ticket ditutup');
    }
}
