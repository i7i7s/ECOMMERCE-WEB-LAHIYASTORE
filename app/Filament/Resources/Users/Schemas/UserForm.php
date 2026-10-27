<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email (untuk login)')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(),
                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel()
                    ->maxLength(20)
                    ->unique(),
                Select::make('role')
                    ->label('Peran')
                    ->options([UserRole::Admin->value => UserRole::Admin->getLabel(), UserRole::Staff->value => UserRole::Staff->getLabel()])
                    ->default(UserRole::Staff->value)
                    ->required()
                    ->disabled($isSelf)
                    ->helperText('Staff tidak bisa melihat keuangan dan harga modal.'),
                TextInput::make('password')
                    ->label(fn (Page $livewire): string => $livewire instanceof CreateRecord ? 'Password' : 'Password baru (kosongkan jika tidak diubah)')
                    ->password()
                    ->revealable()
                    ->rule('min:8')
                    ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Akun nonaktif tidak bisa login.')
                    ->default(true)
                    ->disabled($isSelf)
                    ->inline(false),
            ]);
    }
}
