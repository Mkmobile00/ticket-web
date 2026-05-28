<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
        ]);
        ContactMessage::create($data);
        return back()->with('status', 'Thanks — your message was sent.');
    }
}
