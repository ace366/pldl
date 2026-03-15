<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $hasAnyBankField = !empty($validated['bank_code'])
            || !empty($validated['bank_name'])
            || !empty($validated['bank_branch_code'])
            || !empty($validated['bank_branch_name'])
            || !empty($validated['bank_account_type'])
            || !empty($validated['bank_account_number']);

        if ($hasAnyBankField) {
            $kana = trim((string)($request->user()->last_name_kana ?? '') . (string)($request->user()->first_name_kana ?? ''));
            if ($kana === '') {
                $kana = (string)($request->user()->name ?? '');
            }
            $kana = preg_replace('/\\s+/u', '', $kana);
            $validated['bank_account_holder_kana'] = $kana !== ''
                ? mb_convert_kana($kana, 'KV', 'UTF-8')
                : null;
        } else {
            $validated['bank_account_holder_kana'] = null;
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
