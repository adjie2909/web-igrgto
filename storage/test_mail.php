<?php
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'MAIL_HOST=' . config('mail.mailers.smtp.host') . PHP_EOL;
echo 'MAIL_PORT=' . config('mail.mailers.smtp.port') . PHP_EOL;
echo 'MAIL_USERNAME=' . config('mail.mailers.smtp.username') . PHP_EOL;
echo 'MAIL_PASSWORD_SET=' . (filled(config('mail.mailers.smtp.password')) ? 'yes' : 'no') . PHP_EOL;

try {
    Illuminate\Support\Facades\Mail::raw('Tes notifikasi approval dari web-igrgto', function ($message) {
        $message->to('edp@gto.indogrosir.co.id')->subject('Tes SMTP Gmail - IGR');
    });
    echo 'MAIL_SENT';
} catch (Throwable $e) {
    echo 'MAIL_ERROR: ' . $e->getMessage();
}
