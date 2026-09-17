@props(['label' => '', 'placeholder' => 'Pilih atau ketik untuk mencari...', 'description' => ''])

@php
    // Komponen pintar: otomatis mendeteksi variabel Livewire apa yang sedang digunakan
    $modelName = $attributes->wire('model')->value();
@endphp

<div class="mb-4">
    @if($label)
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ $label }}</label>
    @endif
    @if($description)
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ $description }}</p>
    @endif
    
    <div wire:ignore>
        <select 
            x-data="{
                tom: null,
                init() {
                    // 1. Inisialisasi TomSelect
                    this.tom = new TomSelect(this.$el, {
                        create: false,
                        placeholder: '{{ $placeholder }}',
                        maxOptions: 50 // Batasi render agar tidak lag jika data ribuan
                    });

                    // 2. Saat user memilih data, beri tahu Livewire
                    this.tom.on('change', (value) => {
                        this.$el.value = value;
                        this.$el.dispatchEvent(new Event('change', { bubbles: true }));
                        this.$el.dispatchEvent(new Event('input', { bubbles: true }));
                    });

                    // 3. JIKA DIGUNAKAN BERSAMA LIVEWIRE (FORM EDIT)
                    @if($modelName)
                        
                        // A. Ambil nilai dari database saat modal/halaman pertama dimuat
                        setTimeout(() => {
                            let initialValue = $wire.{{ $modelName }};
                            if (initialValue) {
                                this.tom.setValue(initialValue, true); // true = ubah tanpa memicu event loop
                            }
                        }, 50);

                        // B. Pantau perubahan secara real-time (Misal Bli klik tombol Edit dari baris tabel lain)
                        $watch('$wire.{{ $modelName }}', (newValue) => {
                            // Samakan tipe data ke String untuk perbandingan yang akurat
                            if (String(this.tom.getValue()) !== String(newValue)) {
                                this.tom.setValue(newValue, true);
                            }
                        });

                    @endif
                }
            }"
            {{ $attributes }}
            class="w-full"
        >
            {{ $slot }}
        </select>
    </div>
</div>