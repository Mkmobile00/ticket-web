<?php

namespace App\Http\Controllers;

use App\Models\PopcornItem;

class PopcornController extends Controller
{
    public function index()
    {
        $items = PopcornItem::orderBy('name')->get();
        return view('popcorn.index', compact('items'));
    }
}
