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
        $customFromName = is_array($this->data) ? ($this->data['from_name'] ?? null) : null;
        $customSubject = is_array($this->data) ? ($this->data['subject'] ?? null) : null;
        $attachmentData = is_array($this->data) ? ($this->data['attachment_data'] ?? null) : null;
        $attachmentName = is_array($this->data) ? ($this->data['attachment_name'] ?? null) : null;
        $attachmentMime = is_array($this->data) ? ($this->data['attachment_mime'] ?? 'application/pdf') : 'application/pdf';

        $fromName = match ($this->type) {
            'request' => 'IGR - Permintaan Barang',
            'ticket' => 'IGR - Ticketing System',
            'pga' => 'IGR - PGA',
            default => config('app.name'),
        };

        if (is_string($customFromName) && $customFromName !== '') {
            $fromName = $customFromName;
        }

        $subject = match ($this->type) {
            'request' => 'Permintaan Barang Baru',
            'ticket' => 'Ticket Baru Dibuat',
            'pga' => 'Permintaan Barang Siap Diproses (PGA)',
            default => 'Notifikasi Sistem',
        };

        if (is_string($customSubject) && $customSubject !== '') {
            $subject = $customSubject;
        }

        $mail = $this->from((string) config('mail.from.address'), $fromName)
            ->subject($subject)
            ->view('emails.system_notification')
            ->with([
                'data' => $this->data,
                'type' => $this->type,
            ]);

        if (is_string($attachmentData) && is_string($attachmentName) && $attachmentName !== '') {
            $mail->attachData($attachmentData, $attachmentName, ['mime' => $attachmentMime]);
        }

        return $mail;
    }
}
