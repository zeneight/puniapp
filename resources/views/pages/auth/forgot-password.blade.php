<x-layouts::auth title="Lupa Kata Sandi">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Lupa Kata Sandi" description="Masukkan email Anda untuk menerima tautan reset kata sandi" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Akun Email"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                Kirim Tautan Reset Kata Sandi
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>Atau balik ke halaman </span>
            <flux:link :href="route('login')" wire:navigate>Login</flux:link>
        </div>
    </div>
</x-layouts::auth>
