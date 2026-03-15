<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));

        $guardians = Guardian::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('last_name', 'like', "%{$q}%")
                       ->orWhere('first_name', 'like', "%{$q}%")
                       ->orWhere('last_name_kana', 'like', "%{$q}%")
                       ->orWhere('first_name_kana', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%")
                       ->orWhere('line_user_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.guardians.index', compact('guardians', 'q'));
    }

    public function create()
    {
        return view('admin.guardians.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name'       => ['required', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:50'],
            'last_name_kana'  => ['nullable', 'string', 'max:50'],
            'first_name_kana' => ['nullable', 'string', 'max:50'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'line_user_id'    => ['nullable', 'string', 'max:80'],
            'preferred_contact' => ['nullable', 'in:line,email,phone'],
        ]);

        // ★ 必須カラム name を自動生成
        $validated['name'] = trim($validated['last_name'].' '.$validated['first_name']);

        // (任意) ふりがなフルネームを保存するカラムがある場合だけ
        if (\Illuminate\Support\Facades\Schema::hasColumn('guardians', 'name_kana')) {
            $validated['name_kana'] = trim(($validated['last_name_kana'] ?? '').' '.($validated['first_name_kana'] ?? ''));
        }

        // (任意) phone を数字だけで保存したい場合
        // $validated['phone'] = isset($validated['phone']) ? preg_replace('/\D+/', '', $validated['phone']) : null;

        $guardian = \App\Models\Guardian::create($validated);

        return redirect()
            ->route('admin.guardians.index')
            ->with('success', '保護者を登録しました。');
    }

}
