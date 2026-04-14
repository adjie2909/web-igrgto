@php
    $payload = is_array($data) ? $data : [];
    $requestData = $payload['request'] ?? null;
    $ticketData = $payload['ticket'] ?? null;
    $ticketEvent = $payload['event'] ?? null;

    $requesterName = $payload['requester_name'] ?? ($data->user->name ?? '-');
    $divisionName = $payload['division_name'] ?? ($requestData->user->division->nama_divisi ?? ($ticketData->user->division->nama_divisi ?? '-'));
    $targetRole = $payload['target_role'] ?? null;

    $ticketStatus = match ((int) ($ticketData->status ?? 0)) {
        1 => 'Diproses',
        2 => 'Selesai',
        default => 'Open',
    };

    $ticketEventLabel = match ($ticketEvent) {
        'created' => 'Ticket baru dibuat',
        'reply' => 'Ada balasan baru',
        'escalated' => 'Ticket dieskalasi ke PGA',
        'closed' => 'Ticket ditutup',
        default => 'Update ticket',
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifikasi Sistem</title>
</head>
<body style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                <tr>
                    <td style="background:#0f172a; padding:18px 24px;">
                        <div style="font-size:18px; font-weight:700; color:#ffffff;">IGR - Notifikasi Sistem</div>
                        <div style="margin-top:4px; font-size:12px; color:#cbd5e1;">Informasi otomatis dari program internal IGR-GTO</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        @if($type === 'request')
                            <h2 style="margin:0 0 10px; font-size:20px; color:#111827;">Permintaan Approval Barang</h2>
                            <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#374151;">
                                Terdapat pengajuan permintaan barang yang memerlukan tindak lanjut approval.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-size:14px;">
                                <tr>
                                    <td style="padding:10px 0; width:180px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Nomor Dokumen</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb; font-weight:600;">{{ $requestData->nomor_dokumen ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Tanggal Request</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $requestData->tanggal_request ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Pemohon</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $requesterName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Divisi</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $divisionName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280;">Level Approval</td>
                                    <td style="padding:10px 0; font-weight:600;">{{ $targetRole ?? '-' }}</td>
                                </tr>
                            </table>
                        @elseif($type === 'ticket')
                            <h2 style="margin:0 0 10px; font-size:20px; color:#111827;">Notifikasi Ticketing</h2>
                            <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#374151;">
                                Terdapat pembaruan ticket pada sistem helpdesk.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-size:14px;">
                                <tr>
                                    <td style="padding:10px 0; width:180px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Nomor Ticket</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb; font-weight:600;">#{{ $ticketData->id ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Judul</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $ticketData->judul ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Status</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $ticketStatus }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Penanggung Jawab</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $ticketData->current_handler ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280; border-bottom:1px solid #e5e7eb;">Divisi</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb;">{{ $divisionName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280;">Event</td>
                                    <td style="padding:10px 0; font-weight:600;">{{ $ticketEventLabel }}</td>
                                </tr>
                            </table>
                        @elseif($type === 'stock')
                            <h2 style="margin:0 0 10px; font-size:20px; color:#111827;">Update Stok Barang</h2>
                            <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#374151;">
                                Stok barang yang sebelumnya kosong sudah tersedia kembali dan bisa diajukan untuk pengambilan.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-size:14px;">
                                <tr>
                                    <td style="padding:10px 0; width:180px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Nama Barang</td>
                                    <td style="padding:10px 0; border-bottom:1px solid #e5e7eb; font-weight:600;">{{ $payload['stock_item_name'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; color:#6b7280;">Stok Tersedia</td>
                                    <td style="padding:10px 0; font-weight:600;">
                                        {{ $payload['stock_available'] ?? 0 }} {{ $payload['stock_unit'] ?? '' }}
                                    </td>
                                </tr>
                            </table>
                        @else
                            <h2 style="margin:0 0 10px; font-size:20px; color:#111827;">Notifikasi Sistem</h2>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#374151;">
                                Terdapat pembaruan pada sistem. Silakan cek aplikasi untuk detail lebih lanjut.
                            </p>
                        @endif

                        <p style="margin:20px 0 0; font-size:13px; color:#6b7280; line-height:1.6;">
                            Mohon tidak membalas email ini. Notifikasi ini dikirim otomatis oleh sistem.
                        </p>
                        <p style="margin:20px 0 0; font-size:13px; color:#6b7280; line-height:1.6;">
                            (MASIH TESTING/SIMULASI)
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e5e7eb; font-size:12px; color:#64748b;">
                        &copy; {{ date('Y') }} INDGROSIR-GORONTALO
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
