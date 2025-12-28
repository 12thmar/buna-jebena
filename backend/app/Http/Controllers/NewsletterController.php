<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email'  => ['required', 'email:rfc,dns', 'max:255'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $email  = strtolower(trim((string) $request->input('email')));
        $source = $request->input('source');

        $subscriber = Subscriber::firstOrCreate(
            ['email' => $email],
            ['status' => 'active', 'source' => $source]
        );

        // If they previously unsubscribed, re-activate them on explicit signup
        if ($subscriber->status === 'unsubscribed') {
            $subscriber->status = 'active';
            $subscriber->unsubscribed_at = null;
            $subscriber->source = $source ?? $subscriber->source;
            $subscriber->save();
        }

        // Build unsubscribe URL (same behavior you had before)
        $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL')), '/');
        $unsubscribeUrl = $frontendUrl . '/unsubscribe?email=' . urlencode($email);

        $subject = 'Welcome to BunaRoots ☕️';

        $html = "
            <h2>Welcome to BunaRoots!</h2>
            <p>Thanks for subscribing. Soon you'll get stories about Ethiopian coffee growth, harvest, and how beans travel from farm to cup.</p>
            <p style='margin-top:18px; font-size: 12px; opacity: 0.8;'>
                <a href='{$unsubscribeUrl}'>Unsubscribe</a>
            </p>
        ";

        $text = "Welcome to BunaRoots! Thanks for subscribing. You'll receive coffee stories soon.\n"
              . "Unsubscribe: {$unsubscribeUrl}";

        // Send via Laravel mailer (SMTP), controlled entirely by .env (Mailpit locally, Maileroo SMTP in prod)
        $mail = ['success' => true];

        try {
            // If you want tags/metadata, you can add headers here (depends on SMTP provider support).
            Mail::send([], [], function ($message) use ($email, $subject, $html, $text) {
                $message->to($email)
                    ->subject($subject)
                    ->html($html);

                // Some clients/providers like having a text alternative:
                $message->text($text);
            });
        } catch (\Throwable $e) {
            $mail = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            Log::error('Newsletter welcome email failed', [
                'email' => $email,
                'source' => $source,
                'exception' => $e,
            ]);
            // NOTE: we still return success:true for the subscription itself
        }

        return response()->json([
            'success' => true,
            'subscriber' => [
                'email' => $subscriber->email,
                'status' => $subscriber->status,
            ],
            'mail' => $mail,
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));

        $subscriber = Subscriber::where('email', $email)->first();
        if (!$subscriber) {
            // Return success to avoid leaking list membership
            return response()->json(['success' => true]);
        }

        $subscriber->status = 'unsubscribed';
        $subscriber->unsubscribed_at = now();
        $subscriber->save();

        return response()->json(['success' => true]);
    }
}
