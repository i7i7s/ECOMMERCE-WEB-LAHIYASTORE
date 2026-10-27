<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductSaleMode;
use App\Filament\Support\SafeDeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount('variants'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('variants_count')
                    ->label('Varian'),
                TextColumn::make('sale_mode')
                    ->label('Cara jual')
                    ->badge()
                    ->color(fn (ProductSaleMode $state): string => $state === ProductSaleMode::Kredit ? 'info' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Tampil')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                SelectFilter::make('sale_mode')
                    ->label('Cara jual')
                    ->options(ProductSaleMode::class),
                TernaryFilter::make('is_active')
                    ->label('Tampil di toko'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }
}
