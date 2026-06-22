<x-layouts::auth title="Masuk ke Sistem">
    <div class="flex flex-col gap-6">
        
        <div class="flex flex-col items-center text-center mb-2">
            
            <div class="text-[11px] font-bold text-zinc-600 dark:text-zinc-400 tracking-widest mb-3">
                v.1.0.2026
            </div>
            
            <flux:heading size="xl" class="uppercase font-bold mb-3 leading-tight">
                Sistem Informasi dan Database Digital<br>
                Desa Wisata Munggu
            </flux:heading>
            
            <flux:subheading class="max-w-sm mx-auto text-xs leading-relaxed">
                Platform Digital pengelolaan Data dan Informasi Desa Wisata Munggu secara transparan dan terpercaya
            </flux:subheading>
        </div>
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- <x-passkey-verify /> -->

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                label="Akun"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="admin@punia.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    label="Kata Sandi"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Lupa Kata Sandi?
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" label="Ingatkan Saya" :checked="old('remember')" />

            <div class="flex items-center justify-end mt-2">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Masuk
                </flux:button>
            </div>
        </form>

        </div>
</x-layouts::auth>