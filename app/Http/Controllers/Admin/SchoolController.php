<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('name')->paginate(20);
        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('admin.schools.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:schools,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool)($request->boolean('is_active'));

        School::create($data);

        return redirect()->route('admin.schools.index')->with('success', '学校を登録しました。');
    }

    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:schools,name,' . $school->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool)($request->boolean('is_active'));

        $school->update($data);

        return redirect()->route('admin.schools.index')->with('success', '学校を更新しました。');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('admin.schools.index')->with('success', '学校を削除しました。');
    }
}
