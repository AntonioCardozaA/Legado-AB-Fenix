<?php
// test_email.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Mail;

echo "Enviando correo de prueba...\n";

try {
    Mail::raw('Este es un correo de prueba del sistema', function ($message) {
        $message->to('antoniocardoza695@gmail.com')
                ->subject('Correo de prueba');
    });
    
    echo "✅ Correo enviado exitosamente!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}