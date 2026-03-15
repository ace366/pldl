<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // ✅ 管理者登録用：学校一覧は不要
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 電話は「数字だけ」DB保存（フォームはハイフンありでもOK）
        $rawPhone = (string)($request->input('phone', ''));
        $phoneDigits = preg_replace('/\D+/', '', $rawPhone);

        // ふりがな（最低限の整形）
        $lastKana  = trim((string)$request->input('last_name_kana', ''));
        $firstKana = trim((string)$request->input('first_name_kana', ''));

        $request->merge([
            'phone'           => $phoneDigits,
            'last_name_kana'  => $lastKana,
            'first_name_kana' => $firstKana,
        ]);

        $validated = $request->validate([
            // 氏名
            'last_name'       => ['required', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:50'],
            'last_name_kana'  => ['required', 'string', 'max:50'],
            'first_name_kana' => ['required', 'string', 'max:50'],

            // 電話（数字のみ保存）
            'phone'           => ['required', 'string', 'regex:/^\d{10,11}$/'],

            // 備考
            'note'            => ['nullable', 'string', 'max:2000'],

            // Breeze標準
            'email'           => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],

            // パスワード：最小4文字
            'password'        => ['required', 'confirmed', Rules\Password::min(4)],
        ], [
            'phone.required' => '電話番号を入力してください。',
            'phone.regex'    => '電話番号は10桁または11桁の数字で入力してください（ハイフンなし）。',
        ]);

        // name（表示名）は「姓 名」で自動生成
        $fullName = trim($validated['last_name'] . ' ' . $validated['first_name']);

        $user = User::create([
            'name'            => $fullName,
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),

            // 追加項目
            'last_name'       => $validated['last_name'],
            'first_name'      => $validated['first_name'],
            'last_name_kana'  => $validated['last_name_kana'],
            'first_name_kana' => $validated['first_name_kana'],
            'phone'           => $validated['phone'],
            'note'            => $validated['note'] ?? null,

            // ✅ 管理者として登録（要望：管理者ID追加用）
            'role'            => 'user',
        ]);

        event(new Registered($user));

        $request->session()->forget('register_unlocked_until');
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
