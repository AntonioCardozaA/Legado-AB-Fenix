<x-guest-layout>
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ substr(hash_file('sha256', public_path('css/login.css')), 0, 12) }}">
    </x-slot:head>

    <main class="login-page">
        <section class="login-card login-card-compact login-panel" aria-labelledby="recovery-title">
            <h1 id="recovery-title" class="login-title">Recuperación de contraseña</h1>
            <p class="login-intro">
                Para solicitar el restablecimiento de su contraseña, envíe un mensaje a whatsapp al número <strong>4981239090</strong>.
            </p>

            <div class="login-footer">
                <a class="login-submit" href="{{ route('login') }}">Volver al inicio de sesión</a>
            </div>
        </section>
    </main>
</x-guest-layout>
