<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
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
    }
}
