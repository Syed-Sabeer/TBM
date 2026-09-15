<?php

use Illuminate\Support\Facades\Route;

/*
| No public API yet. The storefront's own JSON endpoints (the price quote on
| the item page) live in routes/web.php, because they need the session.
*/

Route::middleware('auth:sanctum')->get('/user', fn (\Illuminate\Http\Request $request) => $request->user());
