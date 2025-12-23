<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SmokeTestController extends Controller
{
    public function email(Request $request)
    {
        // 1) Auth: secret header
        $token = $request->header('X-Smoke-Test-Token');
        $expected = config('services.smoke.token');

        if (!$expected || !$token || !hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        // 2) Hard daily limit for this endpoint (extra safety)
        $key = 'smoke_email_count_' . now()->format('Y-m-d');
        $count = Cache::get($key, 0);

        if ($count >= 5) {
            return response()->json(['ok' => false, 'error' => 'Daily smoke limit reached'], 429);
        }

        // 3) Send email
        $to = config('services.smoke.email_to', 'info@bunaroots.com');
        $subject = 'BunaRoots Smoke Test ' . now()->toIso8601String();
        $body = "Smoke test OK.\n\nServer time: " . now()->toDateTimeString();

        try {
            Mail::raw($body, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject);
            });

            Cache::put($key, $count + 1, now()->endOfDay());

            return response()->json(['ok' => true, 'to' => $to], 200);
        } catch (\Throwable $e) {
            Log::error('Smoke email failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Mail send failed'], 500);
        }
    }
}
