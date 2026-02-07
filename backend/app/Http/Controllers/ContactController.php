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

            $subject = sprintf(
                '[Contact – %s] %s',
                $request->input('category'),
                $request->input('subject')
            );

            $html = "
                <h2 style='margin-bottom:8px'>New Contact Request – BunaRoots</h2>

                <table cellpadding='6' cellspacing='0' style='border-collapse:collapse; margin-bottom:16px'>
                    <tr><td><strong>Category</strong></td><td>{$request->category}</td></tr>
                    <tr><td><strong>Name</strong></td><td>{$request->name}</td></tr>
                    <tr><td><strong>Email</strong></td><td>{$request->email}</td></tr>
                    <tr><td><strong>Mobile</strong></td><td>{$request->mobile}</td></tr>
                </table>

                <h3 style='margin-bottom:6px'>Message</h3>
                <div style='white-space:pre-wrap; border-left:3px solid #c56a3a; padding-left:12px; margin-bottom:16px'>
                    {$request->message}
                </div>

                <p style='font-size:12px; color:#666'>
                    Submitted on: " . now()->format('D, M j, Y \a\t g:i A') . "<br>
                    Source: Contact Page
                </p>
            ";

            $text = 
            "New Contact Request – BunaRoots\n\n"
            ."Category: {$request->category}\n"
            ."Name: {$request->name}\n"
            ."Email: {$request->email}\n"
            ."Mobile: {$request->mobile}\n\n"
            ."Message:\n{$request->message}\n\n"
            ."Submitted on: ".now()->toDateTimeString()."\n"
            ."Source: Contact Page\n";

            Mail::send([], [], function ($message) use ($subject, $html, $text, $request) {
                $message->to(config('mail.contact.to'))
                    ->subject($subject)
                    ->replyTo($request->email, $request->name)
                    ->html($html)
                    ->text($text);
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