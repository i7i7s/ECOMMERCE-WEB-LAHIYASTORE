<form wire:submit="track" class="grid items-start gap-3 text-left sm:grid-cols-[1fr_1fr_auto]">
    <label class="flex flex-col gap-1.5 text-[13px] text-muted">Kode order
        <input wire:model="code" required placeholder="LS-20261001-001" autocomplete="off" @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink uppercase placeholder:normal-case', 'border-bad' => $errors->has('code'), 'border-line' => ! $errors->has('code')])>
    </label>
    <label class="flex flex-col gap-1.5 text-[13px] text-muted">4 digit terakhir no. HP
        <input wire:model="phoneSuffix" required inputmode="numeric" maxlength="4" placeholder="1234" autocomplete="off" @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink', 'border-bad' => $errors->has('phoneSuffix'), 'border-line' => ! $errors->has('phoneSuffix')])>
    </label>
    <button type="submit" class="h-12.5 cursor-pointer self-end rounded-full bg-action px-8 text-[17px] text-white hover:bg-[#0077ed]">
        <span wire:loading.remove wire:target="track">Lacak</span>
        <span wire:loading wire:target="track">Mencari…</span>
    </button>
    @if ($errors->any())
        <p role="alert" class="text-sm text-bad sm:col-span-3">{{ $errors->first() }}</p>
    @endif
</form>
