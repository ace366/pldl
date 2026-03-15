<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Base;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function index()
    {
        $bases = Base::orderBy('name')->paginate(20);
        return view('admin.bases.index', compact('bases'));
    }

    public function create()
    {
        return view('admin.bases.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:150', 'unique:bases,name'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => '拠点名を入力してください。',
            'name.unique'   => '同じ拠点名がすでに登録されています。',
        ]);

        Base::create([
            'name'      => $validated['name'],
            'is_active' => (bool)($request->boolean('is_active', true)),
        ]);

        return redirect()->route('admin.bases.index')->with('success', '拠点を登録しました。');
    }

    public function edit(Base $base)
    {
        return view('admin.bases.edit', compact('base'));
    }

    public function update(Request $request, Base $base)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:150', 'unique:bases,name,' . $base->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $base->update([
            'name'      => $validated['name'],
            'is_active' => (bool)($request->boolean('is_active', true)),
        ]);

        return redirect()->route('admin.bases.index')->with('success', '拠点を更新しました。');
    }

    public function destroy(Base $base)
    {
        // 児童が紐づいている拠点は削除させない（安全運用）
        if ($base->children()->exists()) {
            return redirect()->route('admin.bases.index')
                ->with('error', 'この拠点には児童が登録されているため削除できません。');
        }

        $base->delete();

        return redirect()->route('admin.bases.index')->with('success', '拠点を削除しました。');
    }
}
