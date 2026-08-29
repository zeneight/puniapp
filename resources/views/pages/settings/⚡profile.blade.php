<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Pengaturan Profil')] class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo; 
    public ?string $current_photo = null; 

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->current_photo = Auth::user()->photo; 
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));
        
        $this->validate([
            'photo' => 'nullable|image|max:2048', 
        ]);

        $user->fill($validated);

        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $user->photo = $path;
            $this->current_photo = $path;
            $this->photo = null; 
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: 'Profil berhasil diperbarui.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Pengaturan Profil</flux:heading>

    <x-pages::settings.layout heading="Profil" subheading="Perbarui nama, alamat email, dan foto profil Anda.">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            
            <div class="flex items-center gap-5">
                <div class="relative shrink-0 w-20 h-20 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                    @elseif ($current_photo)
                        <img src="{{ asset('storage/' . $current_photo) }}" class="w-full h-full object-cover">
                    @else
                        <flux:icon.user class="w-10 h-10 m-5 text-zinc-400" />
                    @endif
                </div>
                <div>
                    <flux:label>Foto Profil</flux:label>
                    <input type="file" wire:model="photo" accept="image/*" class="mt-1 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <div wire:loading wire:target="photo" class="text-xs text-indigo-500 mt-1">Mengunggah foto...</div>
                    @error('photo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <flux:input wire:model="name" label="Nama Lengkap" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" label="Alamat Email" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            Alamat email Anda belum diverifikasi.
                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                Klik di sini untuk mengirim ulang email verifikasi.
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                Tautan verifikasi baru telah dikirim ke alamat email Anda.
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        Simpan Perubahan
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <div class="pt-8 mt-8 border-t border-zinc-200 dark:border-zinc-700">
                <flux:heading size="md" class="text-red-600">Hapus Akun</flux:heading>
                <flux:subheading class="mb-4">Hapus akun Anda beserta seluruh datanya secara permanen.</flux:subheading>
                <livewire:pages::settings.delete-user-form />
            </div>
        @endif
    </x-pages::settings.layout>
</section>