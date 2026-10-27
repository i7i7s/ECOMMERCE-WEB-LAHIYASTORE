@php
    $selectedHex = $colorOptions->get($color)?->hex_code ?? '#d9e1e8';
    $selectedType = $caseTypeOptions->get($caseType);
@endphp

<div x-data="caseEditor(@js($editorConfig))" class="mx-auto max-w-7xl px-4 pt-8 pb-20 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-3xl font-bold tracking-tight sm:text-[40px]">Desain casingmu</h1>
        <p class="text-[13px] text-muted">Batch {{ $batch->code }} · estimasi tiba {{ $etaLabel }}</p>
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[320px_minmax(0,1fr)_320px]">
        {{-- Steps --}}
        <div class="flex flex-col gap-3.5">
            <section aria-labelledby="step-model" class="rounded-[18px] bg-white p-4.5 shadow-sm">
                <h2 id="step-model" class="mb-2.5 text-[13px] font-semibold text-muted">1 · Tipe HP</h2>
                <div class="relative">
                    <label class="flex h-11.5 items-center gap-2.5 rounded-xl border border-line px-3.5 focus-within:border-action">
                        <svg class="size-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                        <span class="sr-only">Cari tipe HP</span>
                        <input wire:model.live.debounce.250ms="search" placeholder="{{ $modelName ?: 'Cari tipe HP' }}" autocomplete="off" class="w-full bg-transparent text-base outline-none placeholder:text-ink">
                    </label>

                    @if (trim($search) !== '')
                        <div class="absolute inset-x-0 top-full z-20 mt-1.5 overflow-hidden rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-black/5">
                            @foreach ($this->suggestions as $suggestion)
                                <button type="button" wire:key="model-{{ $suggestion->id }}" wire:click="selectModel({{ $suggestion->id }})" class="flex w-full cursor-pointer items-center justify-between rounded-xl p-3 text-left text-[15px] hover:bg-mist">
                                    {{ $suggestion->name }}
                                    <span @class(['text-xs font-semibold', 'text-link' => $suggestion->has_preview, 'text-muted' => ! $suggestion->has_preview])>{{ $suggestion->has_preview ? 'Preview' : 'Perkiraan' }}</span>
                                </button>
                            @endforeach
                            <button type="button" wire:click="useManualModel" class="flex w-full cursor-pointer items-center justify-between rounded-xl border-t border-fog p-3 text-left text-[15px] text-muted hover:bg-mist">
                                Pakai “{{ \Illuminate\Support\Str::limit($search, 30) }}”
                                <span class="text-xs font-semibold">Ketik manual</span>
                            </button>
                        </div>
                    @endif
                </div>
                @error('search') <p class="mt-2 text-sm text-bad">{{ $message }}</p> @enderror

                <p class="mt-2.5 text-[15px] font-medium">{{ $modelName ?: 'Belum ada tipe HP dipilih' }}</p>
                @if ($modelName === '')
                @elseif ($hasPreview)
                    <p class="mt-1 flex items-center gap-1.5 text-[13px] text-good"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>Preview 2D tersedia</p>
                @else
                    <p class="mt-1 rounded-xl bg-confirm-soft px-3 py-2 text-[13px] leading-relaxed text-confirm">Preview perkiraan: bentuk kamera bisa sedikit berbeda. Tim mengecek ketersediaan blank case ke vendor sebelum kamu bayar.</p>
                @endif
            </section>

            <section aria-labelledby="step-case" class="rounded-[18px] bg-white p-4.5 shadow-sm">
                <h2 id="step-case" class="mb-2.5 text-[13px] font-semibold text-muted">2 · Jenis case</h2>
                @if ($caseTypeOptions->count() === 1)
                    <div class="flex min-h-14 items-center justify-between rounded-xl border border-action px-3 py-2 ring-1 ring-action">
                        <span class="flex flex-col">
                            <span class="text-[15px] font-semibold">{{ $caseTypeOptions->first()['name'] }}</span>
                            <span class="text-[13px] text-muted">Casing lentur, satu-satunya jenis yang kami jual</span>
                        </span>
                        <span class="text-[15px] font-semibold">{{ $caseTypeOptions->first()['price_label'] }}</span>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($caseTypeOptions as $key => $type)
                            <label wire:key="case-{{ $key }}" @class([
                                'flex min-h-14 flex-col justify-center rounded-xl border px-3 py-2',
                                'cursor-pointer' => $type['available'],
                                'cursor-not-allowed opacity-50' => ! $type['available'],
                                'border-action ring-1 ring-action' => $caseType === $key,
                                'border-line hover:border-muted' => $caseType !== $key,
                            ])>
                                <input type="radio" wire:model.live="caseType" value="{{ $key }}" @disabled(! $type['available']) class="sr-only">
                                <span class="text-[15px] font-semibold">{{ $type['name'] }}</span>
                                <span class="text-[13px] text-muted">{{ $type['price_label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            <section aria-labelledby="step-color" class="rounded-[18px] bg-white p-4.5 shadow-sm">
                <h2 id="step-color" class="mb-2.5 text-[13px] font-semibold text-muted">3 · Warna case: {{ $colorOptions->get($color)?->name }}</h2>
                <div class="flex gap-2.5">
                    @foreach ($colorOptions as $key => $option)
                        <label wire:key="color-{{ $key }}" @class(['size-11 cursor-pointer rounded-full border-2 p-0.5', 'border-action' => $color === $key, 'border-transparent' => $color !== $key])>
                            <input type="radio" wire:model.live="color" value="{{ $key }}" class="sr-only">
                            <span class="sr-only">{{ $option->name }}</span>
                            <span class="block size-full rounded-full border border-black/15" style="background: {{ $option->hex_code }}"></span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="step-image" class="rounded-[18px] bg-white p-4.5 shadow-sm">
                <h2 id="step-image" class="mb-2.5 text-[13px] font-semibold text-muted">4 · Gambar</h2>
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-[#b0b0b8] p-3 hover:border-action">
                    <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-[10px] bg-mist">
                        <template x-if="src"><img x-bind:src="src" alt="" class="size-full object-cover"></template>
                        <template x-if="! src"><svg class="size-5 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5" /><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" /></svg></template>
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-[15px] font-medium" x-text="fileName || 'Pilih gambar'"></span>
                        <span class="text-[13px] text-muted" x-text="src ? imageWidth + ' × ' + imageHeight + ' px' : 'JPG atau PNG, maks. {{ (int) \App\Models\Setting::valueOf('max_design_upload_mb', '10') }} MB'"></span>
                    </span>
                    <input type="file" accept="image/jpeg,image/png" class="sr-only" x-on:change="pick($event)">
                </label>
                <p x-cloak x-show="fileError" x-text="fileError" class="mt-2 text-sm text-bad"></p>

                @unless ($hasPreview)
                    <label class="mt-3 flex flex-col gap-1.5 text-[13px] text-muted">Catatan posisi gambar (opsional)
                        <textarea x-model="positionNote" rows="3" maxlength="500" placeholder="Contoh: wajah di tengah, logo di bawah kamera" class="rounded-xl border border-line px-3 py-2 text-[15px] text-ink"></textarea>
                    </label>
                @endunless
            </section>
        </div>

        {{-- Stage --}}
        <section aria-label="Preview 2D" class="flex flex-col items-center gap-5 rounded-tile bg-mist p-5 sm:p-7">
            @if ($mockup)
                @php
                    $rim = 1.8;
                    $outerWidth = $mockup['width'] + 2 * $rim;
                    $outerHeight = $mockup['height'] + 2 * $rim;
                    $outerRadius = round(($mockup['radius'] + $rim) / $outerWidth * 100, 2).'% / '.round(($mockup['radius'] + $rim) / $outerHeight * 100, 2).'%';
                    $innerRadius = round($mockup['radius'] / $mockup['width'] * 100, 2).'% / '.round($mockup['radius'] / $mockup['height'] * 100, 2).'%';
                @endphp
                <div
                    class="w-[min(300px,80vw)] touch-none shadow-[0_24px_50px_rgba(0,0,0,0.16)] select-none"
                    style="background: {{ $selectedHex }}; padding: {{ round($rim / $outerWidth * 100, 2) }}%; border-radius: {{ $outerRadius }}"
                    x-bind:class="drag ? 'cursor-grabbing' : (src ? 'cursor-grab' : '')"
                    x-on:pointerdown="startDrag($event)"
                    x-on:pointermove="moveDrag($event)"
                    x-on:pointerup="endDrag()"
                    x-on:pointercancel="endDrag()"
                >
                    <div x-ref="printArea" class="relative overflow-hidden bg-white" style="aspect-ratio: {{ $mockup['width'] }} / {{ $mockup['height'] }}; border-radius: {{ $innerRadius }}">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <img x-cloak x-show="src" x-bind:src="src" x-bind:style="imageStyle" alt="Desain kamu" class="pointer-events-none w-full max-w-none" draggable="false">
                        </div>
                        <p x-show="! src" class="absolute inset-0 flex items-center justify-center p-8 pt-[40%] text-center text-sm text-muted">Upload gambar untuk melihat preview</p>
                        <div class="pointer-events-none absolute inset-2 border-[1.5px] border-dashed border-action/70" style="border-radius: {{ $innerRadius }}"></div>
                        <x-store.phone-camera :mockup="$mockup" x-ref="camera" />
                    </div>
                </div>
                <p class="text-center text-[13px] text-muted">Tampilan belakang {{ $modelName }}{{ $hasPreview ? '' : ' (perkiraan)' }}. Seret gambar untuk menggeser; garis biru putus-putus = area cetak.</p>

                <div class="flex w-full max-w-lg items-center gap-4 rounded-[18px] bg-white px-4.5 py-3.5 shadow-sm">
                    <label for="zoom" class="text-[13px] text-muted">Zoom</label>
                    <input id="zoom" type="range" min="1" max="4" step="0.05" x-model.number="zoom" x-bind:disabled="! src" class="grow accent-action">
                    <span class="w-10 text-[13px] tabular-nums" x-text="zoom.toFixed(1) + '×'"></span>
                    <button type="button" x-on:click="rotate()" x-bind:disabled="! src" aria-label="Putar 90 derajat" class="flex size-11 cursor-pointer items-center justify-center rounded-full bg-mist disabled:opacity-40">
                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7" /><path d="M21 4v5h-5" /></svg>
                    </button>
                    <button type="button" x-on:click="reset()" x-bind:disabled="! src" class="min-h-11 cursor-pointer rounded-full bg-mist px-3.5 text-sm disabled:opacity-40">Reset</button>
                </div>
            @else
                <div class="flex min-h-[420px] w-full max-w-sm flex-col items-center justify-center gap-4 text-center">
                    <div class="flex aspect-[1/2] w-40 items-center justify-center overflow-hidden rounded-[30px] border-8 bg-white" style="border-color: {{ $selectedHex === '#ffffff' ? '#e8e8ed' : $selectedHex }}">
                        <template x-if="src"><img x-bind:src="src" alt="Gambar yang kamu upload" class="size-full object-cover opacity-80"></template>
                    </div>
                    <p class="text-[15px] font-semibold">Pilih tipe HP dulu</p>
                    <p class="text-sm leading-relaxed text-muted">Cari tipe HP-mu di langkah 1 untuk melihat preview 2D.</p>
                </div>
            @endif
        </section>

        {{-- Summary --}}
        <div class="flex flex-col gap-3.5">
            <section aria-labelledby="summary" class="flex flex-col gap-3.5 rounded-[18px] bg-white p-5 shadow-sm">
                <h2 id="summary" class="text-[13px] font-semibold text-muted">Ringkasan</h2>
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-[15px]">{{ $modelName ?: 'Pilih tipe HP' }} · {{ $selectedType['name'] ?? '-' }} · {{ $colorOptions->get($color)?->name }}</span>
                    <span class="shrink-0 text-[21px] font-semibold">{{ $selectedType['price_label'] ?? '' }}</span>
                </div>
                <p class="rounded-xl bg-mist px-3 py-2.5 text-[13px] leading-relaxed">Preorder · masuk batch {{ $batch['code'] }}<br><strong class="font-semibold">Estimasi tiba {{ $etaLabel }}</strong> · gratis ongkir</p>

                <label class="flex cursor-pointer items-start gap-2.5 text-sm leading-snug"><input type="checkbox" x-model="agreeRights" class="mt-0.5 size-5 shrink-0 accent-action">Saya punya hak atas gambar ini.</label>
                <label class="flex cursor-pointer items-start gap-2.5 text-sm leading-snug"><input type="checkbox" x-model="agreeColor" class="mt-0.5 size-5 shrink-0 accent-action">Saya paham warna cetak bisa sedikit berbeda dari layar.</label>
                @unless ($hasPreview)
                    <label class="flex cursor-pointer items-start gap-2.5 text-sm leading-snug"><input type="checkbox" x-model="agreeNoPreview" class="mt-0.5 size-5 shrink-0 accent-action">Saya paham preview ini perkiraan dan tim mengecek ketersediaan casing untuk tipe HP ini sebelum saya bayar.</label>
                @endunless

                <button type="button" x-on:click="submit()" x-bind:disabled="blockedReason !== '' || saving" class="min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed] disabled:cursor-not-allowed disabled:bg-fog disabled:text-muted">
                    <span x-text="saving ? 'Menambahkan…' : 'Tambah ke keranjang'"></span>
                </button>
                <p class="text-center text-xs text-muted" x-text="blockedReason || 'Siap! Tim mengecek desainmu sebelum produksi.'"></p>

                @if ($errors->any())
                    <ul role="alert" class="flex flex-col gap-1 rounded-xl bg-bad-soft px-3.5 py-3 text-sm text-bad">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($added)
                    <p role="status" class="flex items-center justify-between gap-3 rounded-xl bg-good-soft px-3.5 py-3 text-sm text-good">
                        Ditambahkan ke keranjang.
                        <a href="{{ route('cart') }}" wire:navigate class="font-semibold underline">Lihat keranjang</a>
                    </p>
                @endif
            </section>
        </div>
    </div>
</div>
