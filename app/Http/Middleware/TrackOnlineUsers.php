<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\OnlineSession;
use Illuminate\Support\Facades\Log;

class TrackOnlineUsers
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            try {
                $user = Auth::user();

                // الطريقة الأولى: استخدام update
                User::where('id', $user->id)->update(['last_seen' => now()]);

                // أو الطريقة الثانية: إذا كنت تفضل save()
                // $user->last_seen = now();
                // $user->save();

                // تسجيل الجلسة النشطة
                OnlineSession::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'session_id' => session()->getId(),
                        'last_activity' => now()
                    ]
                );

            } catch (\Exception $e) {
                Log::error('Online tracking error: '.$e->getMessage());
            }
        }

        return $next($request);
    }
}
