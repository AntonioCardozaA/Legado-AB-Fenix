<x-guest-layout>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Iniciar sesion</h1>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electronico" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contrasena" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    Olvidaste tu contrasena?
                </a>
            @endif

            <div class="flex items-center gap-3">
                <a href="{{ route('welcome') }}" class="text-sm font-medium text-gray-600 underline underline-offset-4 hover:text-gray-900">
                    Volver al inicio
                </a>

                <x-primary-button>
                    Iniciar sesion
                </x-primary-button>
            </div>
        </div>

        @if ($errors->any())
            <p class="mt-4 text-sm text-red-600">
                Verifica tus credenciales e intenta nuevamente.
            </p>
        @endif
    </form>
</x-guest-layout>
