<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo_indogrosir.png') }}">
    <style>
        /* BASE */
        body {
            margin: 0;
            font-family: sans-serif;
            background: #f1f5f9;
        }

        /* LAYOUT UTAMA */
        .container {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background: #1e293b;
            color: white;
            padding: 20px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: #cbd5f5;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 5px;
        }

        .sidebar a:hover {
            background: #334155;
            color: white;
        }

        /* CONTENT (INI YANG DIPERBAIKI TOTAL) */
        .content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* 🔥 WAJIB GANTI */
            padding: 40px;
            overflow-y: auto;        /* 🔥 WAJIB TAMBAH */
        }

        /* CARD */
        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        }

        /* HEADER */
        .header {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* TABLE */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;

        }

        .table-modern thead th {
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            padding: 10px;
        }

        .table-modern tbody tr {
            background: #f8fafc;
            border-radius: 10px;
            transition: 0.2s;
        }

        .table-modern tbody tr:hover {
            background: #f1f5f9;
            transform: scale(1.01);
        }

        .table-modern td {
            padding: 14px;
            vertical-align: middle !important;
        }

        .table-modern td:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .table-modern td:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        /* BUTTON */
        .btn {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
        }

        .btn-blue {
            background: #3b82f6;
            color: white;
        }

        .btn-red {
            background: #ef4444;
            color: white;
        }

        .btn-gray {
            background: #64748b;
            color: white;
        }

        /* BADGE */
        .badge {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
        }

        .badge-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-user {
            background: #e0f2fe;
            color: #075985;
        }

        .badge-sjm {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-sam {
            background: #ede9fe;
            color: #5b21b6;
        }

        .badge-sm {
            background: #dcfce7;
            color: #166534;
        }

        .badge-pga {
            background: #fce7f3;
            color: #9d174d;
        }

        /* FORM GRID */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            /* dari 15 → lebih lega */
        }

        .grid-2 .full {
            grid-column: 1 / -1;
        }

        .grid-2>.form-group {
            width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            background: #f9fafb;
            transition: 0.2s;
            box-sizing: border-box;
            /* 🔥 ini penting */
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        /* BUTTON GROUP */
        .aksi-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .aksi {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            /* biar ga kepanjangan */
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            /* 🔥 overlay gelap */
            z-index: 9999;
            /* 🔥 paling atas */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            /* 🔥 WAJIB putih */
            padding: 25px;
            border-radius: 12px;
            width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content table th {
            font-size: 13px;
            color: #64748b;
        }

        .modal-content table td {
            padding: 6px 0;
        }

        .modal-content hr {
            border: none;
            border-top: 1px solid #eee;
        }

        .modal-content * {
            position: relative;
            z-index: 1;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pagination-custom a,
        .pagination-custom span {
            padding: 8px 12px;
            border-radius: 10px;
            background: #f1f5f9;
        }

        .pagination-custom .active {
            background: #3b82f6;
            color: white;
        }

        .page-link {
            padding: 6px 10px;
            border-radius: 6px;
        }

        .header-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-modern h2 {
            margin: 0;
            font-size: 22px;
        }

        .header-modern p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .search-box {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            width: 220px;
        }

        /* FIX PAGINATION TOTAL */
        nav[role="navigation"] {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 8px;
        }

        /* SEMUA LINK */
        nav[role="navigation"] a,
        nav[role="navigation"] span {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            background: #e2e8f0;
            color: #1e293b;
            font-size: 13px;
            text-decoration: none;
        }

        /* ACTIVE PAGE */
        nav[role="navigation"] span[aria-current="page"] {
            background: #3b82f6;
            color: white;
        }

        /* HOVER */
        nav[role="navigation"] a:hover {
            background: #cbd5f5;
        }

        /* SVG ICON (INI YANG BIKIN ERROR BESAR) */
        nav[role="navigation"] svg {
            width: 14px;
            height: 14px;
        }

        .pagination-custom {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
        }

        .pagination-custom a,
        .pagination-custom span {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            background: #e2e8f0;
            color: #1e293b;
        }

        .pagination-custom a:hover {
            background: #cbd5f5;
        }

        .pagination-custom .active {
            background: #3b82f6;
            color: white;
        }

        .pagination-custom .disabled {
            opacity: 0.5;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 10px;
        }

        .detail-grid label {
            font-size: 12px;
            color: #64748b;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th {
            text-align: left;
            font-size: 12px;
            color: #64748b;
            padding-bottom: 8px;
        }

        .detail-table td {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <h2>Admin Panel</h2>
            @php 
                $user = auth()->user();
            @endphp

            {{-- HANYA ADMIN NON PGA --}}
            @if($user->role == 'ADMIN' && $user->userid != 'PGA')
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('user.index') }}">User</a>
                <a href="{{ route('request.index') }}">Request</a>
            @endif

            @if($user->role == 'PGA' || $user->userid == 'PGA')
                <a href="{{ route('request-claim.index') }}">Permintaan Barang Masuk</a>
            @endif

            {{-- ADMIN + PGA --}}
            @if($user->role == 'ADMIN')
                <a href="{{ route('barang.index') }}">Barang</a>
            @endif
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-red">Logout</button>
            </form>
        </div>

        <!-- CONTENT -->
        <div class="content">
            @yield('content')
        </div>

    </div>

</body>

</html>
