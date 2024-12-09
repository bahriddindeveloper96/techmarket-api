<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'seller') {
            return response()->json([
                'message' => 'Unauthorized. Seller access required.'
            ], 403);
        }

        return $next($request);
    }
}
