<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Newsletter signup:
     * - DOES NOT write to the DB anymore
     * - Sends an email notification to the company inbox (or an Odoo alias later)
     * - Optionally sends a simple auto-reply to the subscriber (disabled by default)
     *
     * Env vars you can set:
     *  - NEWSLETTER_NOTIFY_TO="newsletter@bunaroots.com"
     *  - NEWSLETTER_NOTIFY_SUBJECT_PREFIX="[Newsletter]"
     *  - NEWSLETTER_SEND_AUTOREPLY=false
     *  - NEWSLETTER_AUTOREPLY_SUBJECT="Thanks — we got your request ☕️"
     */
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

        // Where to send notifications (company inbox now; later can be an Odoo alias)
        $notifyTo = (string) env('NEWSLETTER_NOTIFY_TO', '');
        if ($notifyTo === '') {
            // Fallback: try to notify the default "from" address if no explicit inbox is set
            $notifyTo = (string) config('mail.from.address', env('MAIL_FROM_ADDRESS', ''));
        }

        if ($notifyTo === '') {
            Log::error('Newsletter notify address not configured (set NEWSLETTER_NOTIFY_TO or MAIL_FROM_ADDRESS).', [
                'email' => $email,
                'source' => $source,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Newsletter notification email is not configured on the server.',
            ], 500);
        }

        $subjectPrefix = (string) env('NEWSLETTER_NOTIFY_SUBJECT_PREFIX', '[Newsletter]');
        $subject = trim($subjectPrefix . ' New signup: ' . $email);

        // Helpful metadata (safe + useful for sales follow-up / spam analysis)
        $ip = $request->ip();
        $ua = (string) $request->header('User-Agent', '');
        $referer = (string) $request->header('Referer', '');
        $time = now()->toDateTimeString();

        $textBody =
            "New newsletter signup request\n"
            . "----------------------------\n"
            . "Email: {$email}\n"
            . "Source: " . ($source ?? '-') . "\n"
            . "Time: {$time}\n"
            . "IP: " . ($ip ?? '-') . "\n"
            . "User-Agent: " . ($ua !== '' ? $ua : '-') . "\n"
            . "Referer: " . ($referer !== '' ? $referer : '-') . "\n";

        $htmlBody = "
            <h2>New newsletter signup request</h2>
            <table cellpadding='6' cellspacing='0' style='border-collapse:collapse'>
                <tr><td><strong>Email</strong></td><td>" . e($email) . "</td></tr>
                <tr><td><strong>Source</strong></td><td>" . e($source ?? '-') . "</td></tr>
                <tr><td><strong>Time</strong></td><td>" . e($time) . "</td></tr>
                <tr><td><strong>IP</strong></td><td>" . e($ip ?? '-') . "</td></tr>
                <tr><td><strong>User-Agent</strong></td><td style='max-width:780px; word-break:break-word'>" . e($ua !== '' ? $ua : '-') . "</td></tr>
                <tr><td><strong>Referer</strong></td><td style='max-width:780px; word-break:break-word'>" . e($referer !== '' ? $referer : '-') . "</td></tr>
            </table>
        ";

        $mailResult = ['sent' => true];

        try {
            Mail::send([], [], function ($message) use ($notifyTo, $subject, $htmlBody, $textBody, $email) {
                $message->to($notifyTo)
                    ->subject($subject)
                    ->replyTo($email) // so you can reply directly to the subscriber
                    ->html($htmlBody);

                $message->text($textBody);
            });
        } catch (\Throwable $e) {
            $mailResult = [
                'sent' => false,
                'error' => $e->getMessage(),
            ];

            Log::error('Newsletter notify email failed', [
                'notify_to' => $notifyTo,
                'email' => $email,
                'source' => $source,
                'exception' => $e,
            ]);

            // Return 200 success if you want the UI to not block signups on email failure.
            // Here we return success:true but include mail.sent:false so you can see it.
        }

        // Optional simple auto-reply to subscriber (disabled by default)
        $autoReplyEnabled = filter_var(env('NEWSLETTER_SEND_AUTOREPLY', false), FILTER_VALIDATE_BOOLEAN);

        $autoReply = ['enabled' => $autoReplyEnabled, 'sent' => false];
        if ($autoReplyEnabled) {
            $autoSubject = (string) env('NEWSLETTER_AUTOREPLY_SUBJECT', 'Thanks — we got your request ☕️');

            $autoText =
                "Thanks for signing up for BunaRoots updates!\n"
                . "We received your request and will be in touch.\n";

            $autoHtml = "
                <h2>Thanks for signing up ☕️</h2>
                <p>We received your request for BunaRoots updates.</p>
            ";

            try {
                Mail::send([], [], function ($message) use ($email, $autoSubject, $autoHtml, $autoText) {
                    $message->to($email)
                        ->subject($autoSubject)
                        ->html($autoHtml);

                    $message->text($autoText);
                });

                $autoReply['sent'] = true;
            } catch (\Throwable $e) {
                $autoReply['sent'] = false;
                $autoReply['error'] = $e->getMessage();

                Log::warning('Newsletter auto-reply failed', [
                    'email' => $email,
                    'exception' => $e,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'notified_to' => $notifyTo,
            'mail' => $mailResult,
            'auto_reply' => $autoReply,
        ]);
    }

    /**
     * Unsubscribe:
     * Since we no longer store a subscriber list in this app, there's nothing to update here.
     * Keep this endpoint to avoid breaking old links/routes; return success to avoid leaking anything.
     *
     * When Odoo is live, handle unsubscribe inside Odoo (mailing lists / opt-out).
     */
    public function unsubscribe(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribe is handled by our email platform.',
        ]);
    }
}
