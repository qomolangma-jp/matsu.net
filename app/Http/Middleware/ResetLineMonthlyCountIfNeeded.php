<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LineNotificationLog;

class ResetLineMonthlyCountIfNeeded
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 月が変わっているかをチェックして必要に応じてリセット
        LineNotificationLog::resetMonthIfNeeded();

        return $next($request);
    }
}
