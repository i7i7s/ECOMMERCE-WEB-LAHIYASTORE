<?php

namespace App\Livewire\Account;

use App\Models\Address;
use App\Support\JapanPrefectures;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Address book with Japanese addresses and one default (AUTH-05).
 */
#[Title('Alamat')]
class Addresses extends Component
{
    public const MAX_ADDRESSES = 10;

    /** Null = form closed, 0 = new address, otherwise the address being edited. */
    #[Locked]
    public ?int $editingId = null;

    public string $label = '';

    public string $recipient_name = '';

    public string $phone = '';

    public string $postal_code = '';

    public string $prefecture = '';

    public string $city = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public bool $is_default = false;

    public function create(): void
    {
        $this->resetForm();
        $this->recipient_name = auth()->user()->name;
        $this->phone = (string) auth()->user()->phone;
        $this->is_default = ! auth()->user()->addresses()->exists();
        $this->editingId = 0;
    }

    public function edit(int $id): void
    {
        $address = $this->find($id);

        $this->resetForm();
        $this->fill($address->only(['label', 'recipient_name', 'phone', 'postal_code', 'prefecture', 'city', 'address_line1', 'is_default']));
        $this->label = (string) $address->label;
        $this->address_line2 = (string) $address->address_line2;
        $this->editingId = $address->id;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[0-9+\-\s()]{9,20}$/'],
            'postal_code' => ['required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture' => ['required', Rule::in(JapanPrefectures::all())],
            'city' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:150'],
            'address_line2' => ['nullable', 'string', 'max:150'],
            'is_default' => ['boolean'],
        ], [
            'phone.regex' => 'Nomor HP hanya boleh angka, spasi, + atau -.',
            'postal_code.regex' => 'Kode pos Jepang 7 digit, contoh 530-0001.',
        ], [
            'recipient_name' => 'nama penerima',
            'phone' => 'no. HP',
            'postal_code' => 'kode pos',
            'prefecture' => 'prefektur',
            'city' => 'kota/ward',
            'address_line1' => 'alamat',
        ]);

        if ($this->editingId === 0 && $user->addresses()->count() >= self::MAX_ADDRESSES) {
            $this->addError('label', 'Maksimal '.self::MAX_ADDRESSES.' alamat.');

            return;
        }

        $data = [...$validated, 'phone' => PhoneNumber::normalize($validated['phone']), 'address_line2' => $validated['address_line2'] ?: null, 'label' => $validated['label'] ?: null];
        $isFirst = ! $user->addresses()->exists();

        DB::transaction(function () use ($user, $data, $isFirst): void {
            $address = $this->editingId ? $this->find($this->editingId) : $user->addresses()->make();
            $address->fill([...$data, 'is_default' => $data['is_default'] || $isFirst])->save();

            if ($address->is_default) {
                $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }
        });

        $this->resetForm();
    }

    public function makeDefault(int $id): void
    {
        $address = $this->find($id);

        DB::transaction(function () use ($address): void {
            auth()->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }

    public function delete(int $id): void
    {
        $address = $this->find($id);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            auth()->user()->addresses()->oldest('id')->first()?->update(['is_default' => true]);
        }
    }

    /**
     * Only the signed-in customer's own addresses can be touched.
     */
    private function find(int $id): Address
    {
        return auth()->user()->addresses()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'label', 'recipient_name', 'phone', 'postal_code', 'prefecture', 'city', 'address_line1', 'address_line2', 'is_default']);
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.account.addresses', [
            'addresses' => auth()->user()->addresses()->orderByDesc('is_default')->oldest('id')->get(),
            'prefectures' => JapanPrefectures::all(),
            'maxAddresses' => self::MAX_ADDRESSES,
        ]);
    }
}
