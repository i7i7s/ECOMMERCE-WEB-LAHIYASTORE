<?php

namespace App\Filament\Resources\OrderComplaints\Pages;

use App\Actions\Orders\RecordComplaint;
use App\Filament\Resources\OrderComplaints\OrderComplaintResource;
use App\Filament\Support\BusinessAction;
use App\Models\Order;
use App\Models\OrderComplaint;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrderComplaints extends ManageRecords
{
    protected static string $resource = OrderComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Catat komplain')->using(fn (array $data): OrderComplaint => BusinessAction::run(
                fn () => app(RecordComplaint::class)->handle(Order::findOrFail($data['order_id']), auth()->user(), $data),
                'Komplain dicatat',
            )),
        ];
    }
}
