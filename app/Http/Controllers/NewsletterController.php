<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:160',
        ]);
        NewsletterSubscriber::firstOrCreate(['email' => $data['email']]);
        return back()->with('newsletter_success', 'Thanks for subscribing!');
    }
}
