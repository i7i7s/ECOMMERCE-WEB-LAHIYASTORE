<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Delete button for master data. Rows still referenced by orders or the cash book are
 * protected by foreign keys; instead of an error page the admin gets a notification
 * suggesting to deactivate the record.
 */
class SafeDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->using(function (Model $record): bool {
                try {
                    return (bool) $record->delete();
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }

                    return false;
                }
            })
            ->failureNotificationTitle('Tidak bisa dihapus')
            ->failureNotificationBody('Data ini sudah dipakai di order, kas, atau data lain. Nonaktifkan saja supaya tidak tampil lagi.');
    }
}
