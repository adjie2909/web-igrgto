<!DOCTYPE html>
<html>
@vite(['resources/js/app.js'])
<head>
    <title>IGR GTO</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8fafc;
            margin: 0;
            color: #1f2937;
        }

        .navbar {
            background: #1e293b;
            color: white;
            padding: 14px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-link {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: #cbd5f5;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav-link:hover {
            background: #334155;
            color: white;
        }

        .nav-link.active {
            background: #2563eb;
            color: white;
        }

        .container {
            padding: 25px;
        }

        /* CARD */
        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        /* TITLE */
        h1 {
            margin-bottom: 5px;
        }

        /* STAT CARD */
        .stat-card {
            flex: 1;
            padding: 18px;
            border-radius: 10px;
            background: #f1f5f9;
        }

        .stat-title {
            font-size: 30px;
            color: #64748b;
        }

        .stat-value {
            font-size: 25px;
            font-weight: bold;
        }

        /* WARNA HALUS */
        .bg-total {
            background: #d4c483;
        }

        .bg-pending {
            background: #fef3c7;
        }

        .bg-approved {
            background: #dcfce7;
        }

        .bg-proses {
            background: #e0e7ff;
        }

        .bg-selesai {
            background: #dcfce7;
        }

        .bg-tolak {
            background: #ffaf81;
        }

        /* BUTTON */
        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn-green {
            background: #16a34a;
            color: white;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        #table-barang th {
            text-align: left;
            font-size: 14px;
            color: #64748b;
            padding-bottom: 10px;
        }

        #table-barang td {
            vertical-align: top;
            padding: 10px 5px;
        }

        .info-stok {
            background: #f8fafc;
            border-radius: 8px;
            padding: 8px;
            margin-top: 6px;
            font-size: 12px;
        }

        th {
            text-align: left;
            padding: 10px;
            font-size: 25px;
            color: #64748b;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 20px
        }

        tr:hover {
            background: #f9fafb;
        }

        /* PRIMARY */
        .btn-primary {
            background: #2563eb;
            color: white;
        }

        /* OUTLINE */
        .btn-outline {
            background: #2563eb;
            color: white;
        }

        /* HOVER */
        .btn-primary:hover {
            background: #4785c4;
        }

        .btn-outline:hover {
            background: #4785c4;
        }

        /* STATUS */
        .status-pending {
            color: #d97706;
            font-weight: 500;
        }

        .status-approved {
            color: #2563eb;
            font-weight: 500;
        }

        .status-proses {
            color: #4f46e5;
            font-weight: 500;
        }

        .status-selesai {
            color: #16a34a;
            font-weight: 500;
        }

        .status-reject {
            color: #ef4444;
            font-weight: 600;
        }

        /* GROUP */
        .form-group {
            margin-bottom: 16px;
        }

        /* LABEL */
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #475569;
        }

        /* INPUT */
        .input {
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .input:focus {
            outline: none;
            border-color: #2563eb;
        }

        /* INPUT & SELECT */
        .input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            box-sizing: border-box;
        }

        select.input {
            height: 40px;
        }

        /* FOCUS */
        .input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb;
        }

        body {
            background: #f1f5f9;
        }

        .card {
            transition: 0.2s;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            z-index: 999;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 350px;
            animation: fadeIn 0.2s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        /* SUCCESS KHUSUS */
        .success-content {
            transform: scale(0.85);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .success-content {
            transform: scale(1);
            opacity: 1;
        }

        /* ICON */
        .success-icon {
            font-size: 40px;
            color: #22c55e;
            margin-bottom: 10px;
            /* animation: pop 0.4s ease; */
        }

        @keyframes pop {
            0% {
                transform: scale(0);
            }

            70% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes fadeIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-action {
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 6px;
            display: inline-block;
            text-align: center;
            min-width: 80px;
            background: #1072ce;
        }

        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-reject:hover {
            background: #dc2626;
        }

        .detail-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
        }

        .detail-box small {
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .detail-box div {
            font-weight: 500;
        }

        /* CHAT BOX */
        .chat-box {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        /* ROW */
        .chat-row {
            display: flex;
            margin-bottom: 10px;
        }

        /* POSISI */
        .chat-row.me {
            justify-content: flex-end;
        }

        .chat-row.other {
            justify-content: flex-start;
        }

        /* BUBBLE */
        .chat-bubble {
            max-width: 60%;
            padding: 10px;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* WARNA */
        .chat-row.me .chat-bubble {
            background: #3b82f6;
            color: white;
        }

        .chat-row.other .chat-bubble {
            background: white;
            color: #1e293b;
        }

        /* NAME */
        .chat-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* TIME */
        .chat-time {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.7;
        }

        /* INPUT */
        .chat-form {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
        }

        /* ALERT */
        .alert-close {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .stat-box {
            flex:1;
            padding:15px;
            border-radius:10px;
            text-align:center;
            font-size:14px;
        }

        /* BADGE */
        .badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-left: 10px;
            color: white;
        }

        .badge-blue { background: #3b82f6; }
        .badge-orange { background: orange; }
        .badge-red { background: red; }
        .badge-green {background: green;}
    </style>
</head>

<body>

    @if(!request()->is('login') && !request()->is('register'))
    <div class="navbar" style="display:flex; align-items:center; justify-content:space-between;">

        <!-- KIRI: LOGO + MENU -->
        <div style="display:flex; align-items:center; gap:25px;">

            <!-- LOGO -->
            <div style="display:flex; align-items:center; gap:10px;">
                <img src="{{ asset('assets/logo_indogrosir.png') }}" style="height:40px;">
                <div style="font-weight:600;">
                    INDOGROSIR GORONTALO
                </div>
            </div>

            <!-- MENU -->
            <div style="display:flex; gap:10px; margin-left:20px;">

                <!-- DASHBOARD
                <a href="{{ route('dashboard') }}"
                class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    🏠 Dashboard
                </a> -->

                <!-- DASHBOARD -->
                <a href="{{ route('dashboard') }}"
                class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}">
                    Permintaan Barang
                </a>

                <!-- TICKETING -->
                <a href="{{ route('ticket.index') }}"
                class="nav-link {{ request()->is('ticket*') ? 'active' : '' }}">
                    Ticketing Case
                </a>

            </div>

        </div>

        <!-- KANAN: USER -->
        <div style="display:flex; align-items:center; gap:20px;">
            @auth
                <div style="font-size:14px; color:#cbd5f5;">
                    {{ auth()->user()->userid }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-red">Logout</button>
                </form>
            @endauth
        </div>

    </div>
    @endif

    <div class="container">
        @yield('content')
    </div>

</body>

</html>