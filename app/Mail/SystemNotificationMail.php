<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SystemNotificationMail extends Mailable
{
    public $data;
    public $type;

    public function __construct($data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function build()
    {
        // 🔥 TENTUKAN NAMA PENGIRIM
        $fromName = match($this->type) {
            'request' => 'IGR - Permintaan Barang',
            'ticket'  => 'IGR - Ticketing System',
            default   => config('app.name'),
        };

        // 🔥 SUBJECT DINAMIS
        $subject = match($this->type) {
            'request' => 'Permintaan Barang Baru',
            'ticket'  => 'Ticket Baru Dibuat',
            default   => 'Notifikasi Sistem',
        };

        return $this->from('oracle@gto.indogrosir.co.id', $fromName)
            ->subject($subject)
            ->view('emails.system_notification')
            ->with([
                'data' => $this->data,
                'type' => $this->type
            ]);
    }
}