<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
<<<<<<< HEAD
    public function submit(Request $request)
    {
        $data = $request->validate([
            "name"    => "required|string|max:100",
            "email"   => "required|email",
            "subject" => "required|string|max:150",
            "message" => "required|string|max:5000",
        ]);

        // Try to send via Mailpit (non-fatal on error)
        try {
            Mail::raw("From: {$data["name"]} <{$data["email"]}>\n\n{$data["message"]}", function ($m) use ($data) {
                $m->to("inbox@example.test")->subject($data["subject"]);
            });
        } catch (\Throwable $e) {
            Log::error("Mail send failed: ".$e->getMessage());
        }

        return response()->json(["status" => "ok"], 201);
=======
    public function send(Request $request)
    {
        $v = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'required|email',
            'subject' => 'nullable|string|max:160',
            'message' => 'required|string|max:5000',
            'category'=> 'nullable|string|max:80',
        ]);

        $body = "From: {$v['name']} <{$v['email']}>\n"
              . "Category: " . ($v['category'] ?? 'N/A') . "\n\n"
              . $v['message'];

        try {
            Mail::raw($body, function ($m) use ($v) {
                $m->to(env('MAIL_TO_ADDRESS', 'support@bunaroots.local'))
                  ->subject($v['subject'] ?? 'New Contact Message');
            });

            return response()->json(['ok' => true], 200);
        } catch (\Throwable $e) {
            Log::error('Contact mail failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Mail failed'], 500);
        }
>>>>>>> add-contact
    }
}
