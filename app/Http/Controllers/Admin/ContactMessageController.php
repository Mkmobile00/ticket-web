<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;

class ContactMessageController extends AdminController
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);
        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        return view('admin.contact-messages.show', compact('message'));
    }

    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();
        return back()->with('status', 'Message deleted.');
    }
}
