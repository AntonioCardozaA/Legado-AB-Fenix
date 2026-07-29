<section class="space-y-5">
    <header>
        <h2 class="text-lg font-extrabold text-slate-900">
            {{ __('Informacion del perfil') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('Actualiza tu nombre visible y correo electronico.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-sm font-bold text-slate-700">
                    {{ __('Nombre') }}
                </label>
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    :value="old('name', $user->name)"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-700">
                    {{ __('Correo electronico') }}
                </label>
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    :value="old('email', $user->email)"
                    required
                    autocomplete="username"
                />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <p class="font-semibold">
                    {{ __('Tu direccion de correo electronico aun no esta verificada.') }}
                </p>

                <button
                    form="send-verification"
                    class="mt-2 inline-flex items-center gap-2 rounded-lg font-bold text-amber-800 underline underline-offset-4 transition hover:text-amber-950 focus:outline-none focus:ring-4 focus:ring-amber-200"
                >
                    {{ __('Reenviar correo de verificacion') }}
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-3 font-semibold text-emerald-700">
                        {{ __('Se envio un nuevo enlace de verificacion a tu correo electronico.') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <button type="submit" class="responsive-action">
                <i class="fas fa-floppy-disk"></i>
                {{ __('Guardar informacion') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700"
                >
                    <i class="fas fa-check-circle"></i>
                    {{ __('Guardado') }}
                </p>
            @endif
        </div>
    </form>
</section>
