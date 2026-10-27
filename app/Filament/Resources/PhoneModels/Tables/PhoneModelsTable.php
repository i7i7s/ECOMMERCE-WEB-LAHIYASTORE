<?php

namespace App\Filament\Resources\PhoneModels\Tables;

use App\Filament\Support\SafeDeleteAction;
use App\Models\PhoneModel;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhoneModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('brand')->withCount('customCaseOptions'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Tipe HP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->sortable(),
                TextColumn::make('has_preview')
                    ->label('Preview')
                    ->state(fn (PhoneModel $record): string => $record->has_preview ? 'Ada' : 'Tanpa preview')
                    ->badge()
                    ->color(fn (PhoneModel $record): string => $record->has_preview ? 'success' : 'gray'),
                TextColumn::make('custom_case_options_count')
                    ->label('Opsi harga'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->label('Merek')
                    ->relationship('brand', 'name'),
                TernaryFilter::make('preview')
                    ->label('Preview')
                    ->trueLabel('Punya preview')
                    ->falseLabel('Tanpa preview')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('mockup_path')->whereNotNull('print_width_mm')->whereNotNull('print_height_mm'),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('mockup_path')->orWhereNull('print_width_mm')->orWhereNull('print_height_mm')),
                    ),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }
}
