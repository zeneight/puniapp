<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    public $search = '';
    
    // Variabel Form
    public ?int $user_id = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'petugas';
    public bool $is_active = true;

    // Reset halaman saat mencari
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset(['user_id', 'name', 'email', 'password', 'role', 'is_active']);
        $this->resetValidation();
    }

    // --- FUNGSI CRUD ---
    public function simpan()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,inputer',
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'is_active' => $this->is_active,
        ]);

        $this->batal();
        $this->js('$flux.modal("tambah-user").close()');
        \Flux::toast('User baru berhasil ditambahkan.', variant: 'success');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->user_id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->is_active = $user->is_active;
        // Password sengaja dikosongkan, hanya diisi jika ingin diganti
        $this->password = ''; 
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-user").show()');
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user_id)],
            'password' => 'nullable|min:6', // Boleh kosong saat edit
            'role' => 'required|in:admin,inputer',
        ]);

        $user = User::findOrFail($this->user_id);
        
        $user->name = $this->name;
        $user->email = $this->email;
        $user->role = $this->role;
        $user->is_active = $this->is_active;
        
        // Cek jika kolom password diisi (artinya minta ganti password)
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }
        
        $user->save();

        $this->batal();
        $this->js('$flux.modal("edit-user").close()');
        \Flux::toast('Data User berhasil diperbarui.', variant: 'success');
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        $this->js('$flux.modal("hapus-user").close()');
        \Flux::toast('User berhasil dihapus.', variant: 'success');
    }

    public function konfirmasiHapus($id)
    {
        $this->user_id = $id;
        $this->js('$flux.modal("hapus-user").show()');
    }

    // public function render()
    // {
    //     $users = User::where('name', 'like', '%' . $this->search . '%')
    //         ->orWhere('email', 'like', '%' . $this->search . '%')
    //         ->orderBy('name', 'asc')
    //         ->paginate(10);

    //     return view('livewire.manajemen-user-index', [
    //         'users' => $users
    //     ]);
    // }

    public function with()
    {
        $users = User::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orderBy('name', 'asc')
            ->paginate(10);

        return [
            'users' => $users
        ];
    }
}

?>

<div>
    <div class="flex justify-between items-end gap-4 mb-6">
        <div>
            <flux:heading size="xl">Manajemen User</flux:heading>
            <flux:subheading>Kelola hak akses dan akun staf aplikasi.</flux:subheading>
        </div>
        
        <div class="flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama / email..." class="w-64" />
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-user').show()">
                Tambah User
            </flux:button>
        </div>
    </div>

    <flux:card class="relative">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nama & Email</flux:table.column>
                <flux:table.column>Hak Akses</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($user->role === 'admin')
                                <flux:badge color="purple" size="sm" inset="top bottom">Admin</flux:badge>
                            @else
                                <flux:badge color="blue" size="sm" inset="top bottom">Petugas</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($user->is_active)
                                <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" inset="top bottom">Nonaktif</flux:badge>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $user->id }})" variant="ghost" size="sm" icon="pencil-square" class="text-indigo-600 hover:text-indigo-700" />
                            <flux:button wire:click="konfirmasiHapus({{ $user->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-600" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500">
                            Tidak ada data user yang ditemukan.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </flux:card>

    <flux:modal name="tambah-user" class="md:w-96" @close=" $wire.batal() ">
        <form wire:submit.prevent="simpan" class="space-y-4">
            <flux:heading size="lg">Tambah User Baru</flux:heading>
            
            <flux:input wire:model="name" label="Nama Lengkap" placeholder="Masukkan nama..." />
            <flux:input wire:model="email" type="email" label="Alamat Email" placeholder="email@contoh.com" />
            
            <flux:input wire:model="password" type="password" label="Password (Minimal 6 karakter)" placeholder="••••••••" viewable />
            
            <flux:select wire:model="role" label="Hak Akses">
                <flux:select.option value="inputer">Petugas Lapangan</flux:select.option>
                <flux:select.option value="admin">Administrator</flux:select.option>
            </flux:select>
            
            <flux:switch wire:model="is_active" label="Status Akun Aktif" />

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="batal" x-on:click="$flux.modal('tambah-user').close()" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-user" class="md:w-96" @close=" $wire.batal() ">
        <form wire:submit.prevent="update" class="space-y-4">
            <flux:heading size="lg">Edit Data User</flux:heading>
            
            <flux:input wire:model="name" label="Nama Lengkap" />
            <flux:input wire:model="email" type="email" label="Alamat Email" />
            
            <flux:input wire:model="password" type="password" label="Password Baru" description="Kosongkan jika tidak ingin mengubah password." placeholder="••••••••" viewable />
            
            <flux:select wire:model="role" label="Hak Akses">
                <flux:select.option value="inputer">Petugas Lapangan</flux:select.option>
                <flux:select.option value="admin">Administrator</flux:select.option>
            </flux:select>
            
            <flux:switch wire:model="is_active" label="Status Akun Aktif" />

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="batal" x-on:click="$flux.modal('edit-user').close()" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-user" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus User?</flux:heading>
            <p class="text-sm text-zinc-600">Tindakan ini tidak dapat dibatalkan. User yang dihapus tidak akan bisa lagi mengakses aplikasi.</p>
            <div class="flex justify-end gap-2 pt-4">
                <flux:button x-on:click="$flux.modal('hapus-user').close()" variant="ghost">Batal</flux:button>
                <flux:button wire:click="destroy({{ $user_id }})" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>