<?php

namespace App\Http\Middleware;

use App\Support\LegacyCms;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = LegacyCms::currentUser($request);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'יש להתחבר מחדש כדי לבצע את הפעולה.'], 401);
            }
            return redirect()->route('login', ['next' => $request->getRequestUri()]);
        }
        if (!$user['is_admin']) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'אין הרשאה לבצע את הפעולה הזו.'], 403);
            }
            return redirect()->route('index');
        }

        return $next($request);
    }
}
