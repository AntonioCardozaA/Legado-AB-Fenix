 <x-guest-layout>
    <div class="min-h-screen relative flex items-center justify-center px-4 sm:px-6 lg:px-8 py-8 overflow-hidden">

        {{-- Fondo Cervecera --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
             style="background-image: url('{{ asset('images/fondo.png') }}');">
        </div>

        {{-- Overlay Azul Industrial --}}
        <div class="absolute inset-0 bg-gradient-to-br from-[#041c3c]/95 via-[#0b2957]/90 to-[#1f2937]/90"></div>

        {{-- Líneas industriales --}}
        <div class="absolute inset-0 opacity-10"
             style="background-image:
             linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
             linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
             background-size: 45px 45px;">
        </div>

        {{-- Brillos --}}
        <div class="absolute top-0 right-0 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>

        {{-- Contenedor --}}
        <div class="relative w-full max-w-md mx-auto flex items-center justify-center">

            {{-- Tarjeta --}}
            <div class="w-full overflow-hidden rounded-3xl border border-white/10 bg-white/10 backdrop-blur-2xl shadow-[0_0_50px_rgba(0,0,0,0.7)]">

                {{-- Header --}}
                <div class="relative px-6 sm:px-8 py-7 text-center bg-gradient-to-r from-[#0a1f44] via-[#12356d] to-[#d4a017]">

                    {{-- Decoración --}}
                    <div class="absolute inset-0 bg-black/10"></div>

                  {{-- Logo --}}
                <div class="relative mx-auto mb-6
                            w-28 h-28
                            sm:w-32 sm:h-32
                            md:w-36 md:h-36
                            rounded-full
                            bg-white/95
                            p-4
                            shadow-[0_0_35px_rgba(255,196,0,0.45)]
                            border-[5px] border-yellow-400
                            flex items-center justify-center
                            overflow-hidden
                            backdrop-blur-md
                            transition duration-300 hover:scale-105">

                    {{-- Efecto brillo --}}
                    <div class="absolute inset-0 rounded-full bg-gradient-to-br from-white/40 to-transparent opacity-70"></div>

                    <img src="{{ asset('images/logo.png') }}"
                         alt="Logo"
                         class="relative z-10
                                w-full h-full
                                object-contain
                                scale-125
                                transition duration-300 hover:scale-135">
                </div>
                    <h1 class="relative text-2xl sm:text-3xl font-black text-white uppercase tracking-[3px]">
                        Compañia Cervecera de Zacatecas
                    </h1>

                    <p class="relative mt-2 text-xs sm:text-sm font-semibold uppercase tracking-[2px] text-yellow-200">
                        Departamento de Envasado
                    </p>

                    <div class="relative mt-2 w-20 h-1 bg-yellow-400 rounded-full mx-auto"></div>
                </div>

                {{-- Body --}}
                <div class="px-6 sm:px-8 py-7 bg-white/5 backdrop-blur-xl">

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Correo --}}
                        <div class="mb-5">
                            <label for="email"
                                   class="block text-sm font-bold uppercase tracking-wide text-white mb-2">
                                Correo electrónico
                            </label>

                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-yellow-400">
                                    <i class="fa-solid fa-user"></i>
                                </span>

                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       autofocus
                                       autocomplete="username"
                                       placeholder="usuario@empresa.com"
                                       class="w-full pl-11 pr-4 py-3 rounded-xl 
                                              border border-gray-500/30 
                                              bg-white/10 
                                              text-white 
                                              placeholder-gray-300
                                              shadow-inner
                                              backdrop-blur-md
                                              focus:border-yellow-400
                                              focus:ring-2
                                              focus:ring-yellow-400
                                              focus:outline-none
                                              transition duration-300">
                            </div>

                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-300" />
                        </div>

                        {{-- Password --}}
                        <div class="mb-5">
                            <label for="password"
                                   class="block text-sm font-bold uppercase tracking-wide text-white mb-2">
                                Contraseña
                            </label>

                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-yellow-400">
                                    <i class="fa-solid fa-lock"></i>
                                </span>

                                <input id="password"
                                       type="password"
                                       name="password"
                                       required
                                       autocomplete="current-password"
                                       placeholder="********"
                                       class="w-full pl-11 pr-12 py-3 rounded-xl
                                              border border-gray-500/30
                                              bg-white/10
                                              text-white
                                              placeholder-gray-300
                                              shadow-inner
                                              backdrop-blur-md
                                              focus:border-yellow-400
                                              focus:ring-2
                                              focus:ring-yellow-400
                                              focus:outline-none
                                              transition duration-300">

                                <button type="button"
                                        onclick="togglePassword()"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-300 hover:text-yellow-400 transition">
                                    <i id="passwordIcon" class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-300" />
                        </div>

                        {{-- Opciones --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">

                            <label for="remember_me"
                                   class="inline-flex items-center cursor-pointer">

                                <input id="remember_me"
                                       type="checkbox"
                                       name="remember"
                                       class="rounded border-gray-400 text-yellow-400 focus:ring-yellow-400">

                                <span class="ms-2 text-sm text-gray-200">
                                    Recordarme
                                </span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="text-sm font-semibold text-yellow-300 hover:text-yellow-200 transition">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>

                        {{-- Botón --}}
                        <button type="submit"
                                class="group relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-300 px-4 py-3 font-black uppercase tracking-[2px] text-[#0b2957] shadow-xl transition duration-300 hover:scale-[1.02] hover:shadow-yellow-500/40">

                            <span class="relative z-10 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Ingresar
                            </span>

                            <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition"></div>
                        </button>

                        {{-- Volver --}}
                        <div class="mt-5 text-center">
                            <a href="{{ route('welcome') }}"
                               class="text-sm font-semibold text-gray-200 hover:text-yellow-300 underline underline-offset-4 transition">
                                Volver al inicio
                            </a>
                        </div>

                        {{-- Error --}}
                        @if ($errors->any())
                            <div class="mt-5 rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200 backdrop-blur-md">
                                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                                Verifica tus credenciales e intenta nuevamente.
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</x-guest-layout>
