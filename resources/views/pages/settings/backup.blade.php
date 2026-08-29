<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System Backup')] class extends Component {
    public function runBackup()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            Flux::toast(variant: 'success', text: 'Backup database berhasil dijalankan!');
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: 'Gagal: ' . $e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('System Backup') }}</flux:heading>

    <x-pages::settings.layout :heading="__('System Backup')" :subheading="__('Cadangkan database secara manual.')">
        <div class="my-6 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-5 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="font-semibold text-zinc-900 dark:text-white">Database Backup</div>
                <div class="text-sm text-zinc-500">Amankan seluruh data sistem dalam format SQL.</div>
            </div>
            <flux:button wire:click="runBackup" variant="primary" icon="circle-stack">
                Run Backup Now
            </flux:button>
        </div>
    </x-pages::settings.layout>
</section>