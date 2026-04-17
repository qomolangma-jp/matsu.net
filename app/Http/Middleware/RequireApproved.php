<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->approval_status !== 'approved') {
            return redirect()->route('mypage.index')
                ->with('error', 'このページはアカウント承認後にアクセスできます。');
        }

        return $next($request);
    }
}
