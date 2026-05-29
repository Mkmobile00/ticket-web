<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Account profile management (auth). */
class ProfileApiController extends Controller
{
    /** GET /api/v1/profile */
    public function show(Request $request)
    {
        $u = $request->user();
        return response()->json(['data' => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'phone' => $u->phone, 'role' => $u->role,
        ]]);
    }

    /** PUT /api/v1/profile  { name, email, phone? } */
    public function update(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:160', Rule::unique('users')->ignore($u->id)],
            'phone' => 'nullable|string|max:20',
        ]);
        $u->update($data);
        return response()->json(['message' => 'Profile updated.', 'data' => $data]);
    }

    /** PUT /api/v1/profile/password  { current_password, password, password_confirmation } */
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.', 'errors' => ['current_password' => ['Incorrect password.']]], 422);
        }
        $request->user()->update(['password' => Hash::make($request->password)]);
        return response()->json(['message' => 'Password changed.']);
    }
}
