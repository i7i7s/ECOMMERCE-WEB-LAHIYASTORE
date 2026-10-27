<?php

namespace App\Filament\Support;

use App\Exceptions\BusinessRuleException;
use Closure;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

/**
 * Runs a business action from an admin panel action: shows a success notification,
 * or turns a broken business rule into an error notification and keeps the modal open.
 */
class BusinessAction
{
    public static function run(Closure $callback, string $successTitle): mixed
    {
        try {
            $result = $callback();
        } catch (BusinessRuleException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            throw new Halt;
        }

        Notification::make()->title($successTitle)->success()->send();

        return $result;
    }
}
