<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $key = 'maileroo_daily_count_' . now()->format('Y-m-d');
        $count = Cache::get($key, 0);

        if ($count >= 300) {
            return response()->json([
                'message' => 'Daily email limit reached. Please try again tomorrow.'
            ], 429);
        }

        $v = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email',
            'mobile'   => 'nullable|string|max:160',
            'subject'  => 'nullable|string|max:160',
            'message'  => 'required|string|max:2000',
            'category' => 'nullable|string|max:80',
        ]);

        if (preg_match('/http[s]?:\/\//i', $v['message'])) {
            return response()->json(['message' => 'Links not allowed'], 422);
        }

        $body = "From: {$v['name']} <{$v['email']}>\n"
              . "Category: " . ($v['category'] ?? 'N/A') . "\n"
              . "Mobile Phone: " . ($v['mobile'] ?? 'N/A') . "\n\n"
              . $v['message'];

        try {
            $to   = config('mail.contact.to');

            Mail::raw($body, function ($m) use ($v, $to) {
                $m->to($to)
                  ->replyTo($v['email'], $v['name'])
                  ->subject($v['subject'] ?? 'New Contact Message');
            });

            Cache::put($key, $count + 1, now()->endOfDay());

            return response()->json(['ok' => true], 200);
        } catch (\Throwable $e) {
            Log::error('Contact mail failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => 'Mail failed'], 500);
        }
    }
}