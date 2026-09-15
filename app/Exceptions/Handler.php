<?php

namespace App\Exceptions;

use App\Services\Imports\ImportException;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Orders\OrderException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /** @var array<int, class-string<Throwable>> */
    protected $dontReport = [];

    /** @var array<int, string> */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        /*
         | The domain exceptions all mean the same thing to a user: the thing
         | you asked for cannot happen, and here is why in plain words. They
         | come back as a message on the page they were already on rather than
         | a stack trace or a bare 500.
         */
        $this->renderable(function (OrderException|ImportException|InsufficientStockException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['tbm' => $e->getMessage()]);
        });
    }
}
