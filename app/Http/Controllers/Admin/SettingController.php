<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends AdminController
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->input('settings', []) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Image uploads (e.g. hero_bg, search_bg, newsletter_bg) — store under
        // public storage and save the path; this overrides the text value above.
        foreach ((array) $request->file('files', []) as $key => $file) {
            if ($file && $file->isValid()) {
                $path = $file->store('settings', 'public'); // e.g. settings/abc.jpg
                Setting::updateOrCreate(['key' => $key], ['value' => $path]);
            }
        }

        return back()->with('status', 'Settings saved.');
    }
}
