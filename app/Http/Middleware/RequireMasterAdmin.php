<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMasterAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'master_admin') {
            abort(403, 'この操作はマスター管理者のみ実行できます。');
        }

        return $next($request);
    }
}
