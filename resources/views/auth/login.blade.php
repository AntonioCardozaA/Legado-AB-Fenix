<x-guest-layout>
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset('css/logo-sequence.css') }}?v={{ substr(hash_file('sha256', public_path('css/logo-sequence.css')), 0, 12) }}">
        <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ substr(hash_file('sha256', public_path('css/login.css')), 0, 12) }}">
    </x-slot:head>

    <main class="login-page">
        <div class="login-card">
            <header class="login-brand">
                <x-logo-sequence class="login-logo" />

                <div class="login-brand-copy">
                    <p class="login-brand-name">Compañía Cervecera de Zacatecas</p>
                    <p class="login-brand-department">Departamento de Envasado</p>
                </div>
            </header>

            <section class="login-panel" aria-labelledby="login-title">
                <div class="login-panel-inner">
                    <h1 id="login-title" class="login-title">Iniciar sesión</h1>
                    <p class="login-intro">Accede con tu correo de la plataforma.</p>

                    <x-auth-session-status class="login-notice" role="status" :status="session('status')" />

                    <form id="loginForm"
                          class="login-form"
                          method="POST"
                          action="{{ route('login', absolute: false) }}"
                          data-csrf-url="{{ route('csrf-token', absolute: false) }}">
                        @csrf

                        <div>
                            <label for="email" class="login-label">Correo electrónico</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input id="email"
                                       class="login-input"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       autocomplete="username"
                                       autocapitalize="none"
                                       spellcheck="false"
                                       placeholder="usuario@legadoabfenix.com"
                                       @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror
                                       required>
                            </div>
                            <x-input-error id="login-email-error" class="login-error" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <label for="password" class="login-label">Contraseña</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input id="password"
                                       class="login-input login-password"
                                       type="password"
                                       name="password"
                                       autocomplete="current-password"
                                       @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror
                                       required>
                                <button id="loginPasswordToggle"
                                        class="login-password-toggle"
                                        type="button"
                                        aria-controls="password"
                                        hidden>Mostrar</button>
                            </div>
                            <x-input-error id="login-password-error" class="login-error" :messages="$errors->get('password')" />
                        </div>

                        <div class="login-options">
                            <label class="login-remember">
                                <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))>
                                Recordarme
                            </label>

                            <a class="login-link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                        </div>

                        <button id="loginSubmitButton" class="login-submit" type="submit">
                            <span id="loginSubmitLabel">Ingresar</span>
                            <span class="login-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                            <span class="login-spinner" aria-hidden="true"></span>
                        </button>
                    </form>
                    <span id="loginProgress" class="login-visually-hidden" role="status"></span>

                    <div class="login-footer">
                        <a class="login-back" href="{{ route('welcome') }}">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            Volver al inicio
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const form = document.getElementById('loginForm');
            const password = document.getElementById('password');
            const toggle = document.getElementById('loginPasswordToggle');
            const submit = document.getElementById('loginSubmitButton');
            const submitLabel = document.getElementById('loginSubmitLabel');
            const progress = document.getElementById('loginProgress');

            toggle.hidden = false;
            toggle.addEventListener('click', () => {
                const reveal = password.type === 'password';
                password.type = reveal ? 'text' : 'password';
                toggle.textContent = reveal ? 'Ocultar' : 'Mostrar';
                toggle.setAttribute('aria-label', reveal ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
            toggle.setAttribute('aria-label', 'Mostrar contraseña');

            function setSubmitting(submitting) {
                submit.disabled = submitting;
                form.setAttribute('aria-busy', String(submitting));
                submitLabel.textContent = submitting ? 'Ingresando…' : 'Ingresar';
                progress.textContent = submitting ? 'Ingresando…' : '';
            }

            window.addEventListener('pageshow', () => setSubmitting(false));

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (submit.disabled) {
                    return;
                }

                setSubmitting(true);

                const abortController = new AbortController();
                const timeout = setTimeout(() => abortController.abort(), 3000);

                try {
                    const response = await fetch(form.dataset.csrfUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: abortController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.ok) {
                        const { token } = await response.json();

                        if (token) {
                            form.elements.namedItem('_token').value = token;
                            document.querySelector('meta[name="csrf-token"]').content = token;
                        }
                    }
                } catch {
                    // The existing form token can still be valid if the refresh fails.
                } finally {
                    clearTimeout(timeout);
                }

                HTMLFormElement.prototype.submit.call(form);
            });
        })();
    </script>
</x-guest-layout>
