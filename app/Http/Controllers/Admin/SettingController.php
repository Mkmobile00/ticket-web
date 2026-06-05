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
        // Constrain text values and — critically — restrict uploads to images of a
        // bounded size, so the public storage path can't receive arbitrary file
        // types (.svg/.html/.php) or oversized files.
        $request->validate([
            'settings' => 'array',
            'settings.*' => 'nullable|string|max:5000',
            'files' => 'array',
            'files.*' => 'file|mimetypes:image/jpeg,image/png,image/webp,image/gif|max:4096', // 4 MB, images only
        ]);

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
