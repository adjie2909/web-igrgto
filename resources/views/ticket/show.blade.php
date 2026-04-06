@extends('layouts.app')

@section('content')

<style>
    .ticket-layout {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 18px;
        align-items: start;
    }

    .ticket-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
    }

    .ticket-panel h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
    }

    .draft-input {
        width: 100%;
        min-height: 150px;
        resize: vertical;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px;
        box-sizing: border-box;
        font-size: 14px;
    }

    .chat-box {
        max-height: 520px;
    }

    .ticket-actions {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    @media (max-width: 992px) {
        .ticket-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">

    {{-- HEADER --}}
    <h2 style="margin-bottom:5px;">
        {{ $ticket->judul }}

        @if($ticket->status == 2)
            <span class="badge badge-red">CLOSED</span>
        @elseif($ticket->status == 1)
            <span class="badge badge-orange">DIPROSES</span>
        @else
            <span class="badge badge-blue">OPEN</span>
        @endif
    </h2>

    <p style="color:#64748b;">{{ $ticket->deskripsi }}</p>

    <hr>

    <div class="ticket-layout">
        {{-- KIRI: DRAFT --}}
        <div class="ticket-panel">
            <h4>Draft Pesan</h4>

            @if($ticket->status == 2)
                <div class="alert-close">
                    Ticket sudah ditutup, tidak bisa melakukan diskusi lagi.
                </div>
            @endif

            @if($ticket->status != 2)
                <form method="POST" action="{{ route('ticket.reply', $ticket->id) }}">
                    @csrf
                    <textarea name="message" class="draft-input" placeholder="Tulis pesan..."></textarea>

                    <div class="ticket-actions">
                        <button class="btn btn-blue" type="submit">Kirim</button>
                    </div>
                </form>
            @endif

            <div class="ticket-actions">
                @if($ticket->status != 2 && auth()->user()->division_id == 9 && $ticket->level == 1)
                    <form method="POST" action="{{ route('ticket.escalate', $ticket->id) }}">
                        @csrf
                        <button class="btn btn-danger" type="submit">
                            Eskalasi ke PGA
                        </button>
                    </form>
                @endif

                @if($ticket->status != 2 && in_array(auth()->user()->division_id, [9,10]))
                    <form method="POST" action="{{ route('ticket.close', $ticket->id) }}">
                        @csrf
                        <button class="btn btn-green" type="submit">
                            Selesaikan Ticket
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- KANAN: CONVERSATION --}}
        <div class="ticket-panel">
            <h4>Conversation</h4>

            <div class="chat-box">
                @foreach($ticket->replies as $r)
                    @php
                        $isMe = $r->user_id == auth()->id();
                    @endphp

                    <div class="chat-row {{ $isMe ? 'me' : 'other' }}">
                        <div class="chat-bubble">
                            <div class="chat-name">
                                {{ $r->user->name }}
                            </div>

                            <div class="chat-message">
                                {{ $r->message }}
                            </div>

                            <div class="chat-time">
                                {{ $r->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@endsection
