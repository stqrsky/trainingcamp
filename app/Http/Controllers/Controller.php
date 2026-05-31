<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function userFacingError(Throwable $exception, string $fallback = 'Something went wrong. Please try again.'): string
    {
        report($exception);

        if (config('app.debug')) {
            return $exception->getMessage();
        }

        if ($exception instanceof QueryException) {
            return 'Database is unavailable. Check .env and run: php artisan migrate --seed';
        }

        return $fallback;
    }
}
