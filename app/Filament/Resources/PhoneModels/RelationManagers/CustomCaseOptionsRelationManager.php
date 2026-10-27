<?php

namespace App\Filament\Resources\PhoneModels\RelationManagers;

use App\Enums\Currency;
use App\Filament\Support\PriceFields;
use App\Filament\Support\SafeDeleteAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class CustomCaseOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'customCaseOptions';

    protected static ?string $title = 'Harga per jenis case';

    protected static ?string $modelLabel = 'opsi harga';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('case_type_id')
                    ->label('Jenis case')
                    ->relationship('caseType', 'name')
                    ->required()
                    ->unique(modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('phone_model_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages(['unique' => 'Jenis case ini sudah punya harga untuk tipe HP ini.']),
                Toggle::make('is_available')
                    ->label('Blank case tersedia di vendor')
                    ->default(true)
                    ->inline(false),
                ...PriceFields::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caseType.name')
            ->modifyQueryUsing(fn ($query) => $query->with('caseType'))
            ->columns([
                TextColumn::make('caseType.name')
                    ->label('Jenis case'),
                PriceFields::column('price_jpy', Currency::Jpy, 'Harga JPY'),
                PriceFields::column('price_idr', Currency::Idr, 'Harga IDR'),
                PriceFields::column('cost_price_idr', Currency::Idr, 'Modal'),
                IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }
}
