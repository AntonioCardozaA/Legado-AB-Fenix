<section class="space-y-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
    <header>
        <h2 class="text-lg font-extrabold text-slate-900">
            {{ __('Seguridad') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('Actualiza tu contrasena de acceso.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div class="grid gap-4 lg:grid-cols-3">
            <div>
                <label for="update_password_current_password" class="mb-2 block text-sm font-bold text-slate-700">
                    {{ __('Contrasena actual') }}
                </label>
                <div class="relative">
                    <x-text-input
                        id="update_password_current_password"
                        name="current_password"
                        type="password"
                        x-ref="currentPassword"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 pr-12 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        @click="showCurrent = !showCurrent; $refs.currentPassword.type = showCurrent ? 'text' : 'password'"
                        class="absolute inset-y-0 right-3 inline-flex items-center text-slate-400 transition hover:text-blue-700 focus:outline-none"
                        x-bind:aria-label="showCurrent ? 'Ocultar contrasena actual' : 'Mostrar contrasena actual'"
                    >
                        <i class="fas" x-bind:class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <label for="update_password_password" class="mb-2 block text-sm font-bold text-slate-700">
                    {{ __('Nueva contrasena') }}
                </label>
                <div class="relative">
                    <x-text-input
                        id="update_password_password"
                        name="password"
                        type="password"
                        x-ref="newPassword"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 pr-12 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showNew = !showNew; $refs.newPassword.type = showNew ? 'text' : 'password'"
                        class="absolute inset-y-0 right-3 inline-flex items-center text-slate-400 transition hover:text-blue-700 focus:outline-none"
                        x-bind:aria-label="showNew ? 'Ocultar nueva contrasena' : 'Mostrar nueva contrasena'"
                    >
                        <i class="fas" x-bind:class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">
                    {{ __('Confirmar contrasena') }}
                </label>
                <div class="relative">
                    <x-text-input
                        id="update_password_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        x-ref="confirmPassword"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 pr-12 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showConfirm = !showConfirm; $refs.confirmPassword.type = showConfirm ? 'text' : 'password'"
                        class="absolute inset-y-0 right-3 inline-flex items-center text-slate-400 transition hover:text-blue-700 focus:outline-none"
                        x-bind:aria-label="showConfirm ? 'Ocultar confirmacion' : 'Mostrar confirmacion'"
                    >
                        <i class="fas" x-bind:class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <button type="submit" class="responsive-action">
                <i class="fas fa-floppy-disk"></i>
                {{ __('Guardar contrasena') }}
            </button>

            @if (session('status') === 'password-updated')
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
