{{-- Confirmation dialog for the store; behaviour in resources/js/confirm-dialog.js. --}}
<div
    x-data
    x-cloak
    x-show="$store.confirm.open"
    x-on:keydown.escape.window="$store.confirm.close()"
    class="fixed inset-0 z-[60] flex items-end justify-center bg-black/40 p-4 sm:items-center"
    x-transition.opacity
>
    <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
        aria-describedby="confirm-message"
        x-on:click.outside="$store.confirm.close()"
        x-trap.noscroll="$store.confirm.open"
        class="w-full max-w-sm rounded-[22px] bg-white p-6 shadow-xl"
    >
        <div class="flex items-center gap-2.5">
            <x-store.logo-mark class="size-7 shrink-0" />
            <h2 id="confirm-title" class="text-[19px] font-semibold" x-text="$store.confirm.title"></h2>
        </div>
        <p id="confirm-message" class="mt-2.5 text-[15px] leading-relaxed text-ink-2" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$store.confirm.close()" x-text="$store.confirm.cancelLabel" class="min-h-11 cursor-pointer rounded-full bg-mist px-5 text-[15px] text-ink hover:bg-fog"></button>
            <button
                type="button"
                x-on:click="$store.confirm.accept()"
                x-text="$store.confirm.confirmLabel"
                x-bind:class="$store.confirm.danger ? 'bg-bad hover:opacity-90' : 'bg-action hover:bg-[#0077ed]'"
                class="min-h-11 cursor-pointer rounded-full px-5 text-[15px] font-medium text-white"
            ></button>
        </div>
    </div>
</div>
