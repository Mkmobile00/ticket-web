<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Partner;

class PageController extends Controller
{
    public function about()
    {
        $faqs = Faq::where('page_type', 'about')->where('is_active', true)->orderBy('order')->get();
        $partners = Partner::where('is_active', true)->orderBy('name')->get();
        return view('pages.about', compact('faqs', 'partners'));
    }

    public function apps()
    {
        return view('pages.apps');
    }
}
