<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $v = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'required|email',
            'mobile' => 'nullable|string|max:160',
            'subject' => 'nullable|string|max:160',
            'message' => 'required|string|max:5000',
            'category'=> 'nullable|string|max:80',
        ]);

        $body = "From: {$v['name']} <{$v['email']}>\n"
              . "Category: " . ($v['category'] ?? 'N/A') . "\n\n"
              . "Mobile Phone: " . ($v['mobile'] ?? 'N/A') . "\n\n"
              . $v['message'];

        try {
            Mail::raw($body, function ($m) use ($v) {
                $m->to(env('MAIL_TO_ADDRESS', 'info@bunaroots.com'))
                  ->subject($v['subject'] ?? 'New Contact Message');
            });

            return response()->json(['ok' => true], 200);
        } catch (\Throwable $e) {
            Log::error('Contact mail failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Mail failed'], 500);
        }
    }
}
