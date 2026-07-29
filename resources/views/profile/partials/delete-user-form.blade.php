<section class="mt-6 space-y-4 border-t border-slate-100 pt-5">
    <header>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">
            {{ __('Zona sensible') }}
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ __('Eliminar la cuenta removera sus datos asociados.') }}
        </p>
    </header>

    <button
        type="button"
        class="responsive-action responsive-action--danger w-full"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        <i class="fas fa-user-xmark"></i>
        {{ __('Eliminar cuenta') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-700">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">
                        {{ __('Confirmar eliminacion de cuenta') }}
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ __('Ingresa tu contrasena para confirmar que deseas eliminar permanentemente tu cuenta.') }}
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Contrasena') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-100"
                    placeholder="{{ __('Contrasena') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center rounded-xl border-slate-200 px-5 py-3 text-sm font-bold normal-case tracking-normal">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="justify-center rounded-xl px-5 py-3 text-sm font-bold normal-case tracking-normal">
                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
