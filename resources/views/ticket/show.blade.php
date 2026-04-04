@extends('layouts.app')

@section('content')

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

    {{-- CHAT BOX --}}
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

    {{-- CLOSED INFO --}}
    @if($ticket->status == 2)
        <div class="alert-close">
            Ticket sudah ditutup, tidak bisa melakukan diskusi lagi.
        </div>
    @endif

    {{-- INPUT CHAT --}}
    @if($ticket->status != 2)
    <form method="POST" action="{{ route('ticket.reply', $ticket->id) }}" class="chat-form">
        @csrf

        <textarea name="message" class="chat-input" placeholder="Tulis pesan..."></textarea>

        <button class="btn btn-blue">Kirim</button>
    </form>
    @endif

    <br>

    {{-- EDP ESCALATE --}}
    @if($ticket->status != 2 && auth()->user()->division_id == 9 && $ticket->level == 1)
        <form method="POST" action="{{ route('ticket.escalate', $ticket->id) }}">
            @csrf
            <button class="btn btn-danger">
                Eskalasi ke PGA
            </button>
        </form>
    @endif

    {{-- CLOSE --}}
    @if($ticket->status != 2 && in_array(auth()->user()->division_id, [9,10]))
        <form method="POST" action="{{ route('ticket.close', $ticket->id) }}">
            @csrf
            <button class="btn btn-green">
                Selesaikan Ticket
            </button>
        </form>
    @endif

</div>

@endsection