<?php

namespace App\Http\Controllers\Admin;

use App\Models\NewsletterSubscriber;

class NewsletterController extends AdminController
{
    public function index()
    {
        $subscribers = NewsletterSubscriber::latest()->paginate(30);
        return view('admin.newsletter.index', compact('subscribers'));
    }

    public function destroy($id)
    {
        NewsletterSubscriber::findOrFail($id)->delete();
        return back()->with('status', 'Subscriber removed.');
    }
}
