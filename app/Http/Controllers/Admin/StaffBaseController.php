<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\StaffBase;
use App\Models\User;
use Illuminate\Http\Request;

class StaffBaseController extends Controller
{
    /**
     * 職員所属 追加フォーム
     */
    public function create(Request $request)
    {
        $this->ensureAdmin($request);

        $bases = Base::query()->orderBy('id')->get();

        // 職員候補（admin/teacher/staff だけに絞る）
        $users = User::query()
            ->whereIn('role', ['admin', 'teacher', 'staff'])
            ->orderBy('id')
            ->get();

        return view('admin.staff_bases.create', [
            'bases' => $bases,
            'users' => $users,
        ]);
    }

    /**
     * 保存
     */
    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'user_id'    => ['required', 'integer', 'min:1'],
            'base_id'    => ['required', 'integer', 'min:1'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        // 既に同じ所属があるなら重複作成しない
        $exists = StaffBase::query()
            ->where('user_id', (int)$data['user_id'])
            ->where('base_id', (int)$data['base_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'すでに同じ拠点に所属しています。');
        }

        // 主所属にする場合：そのユーザーの他拠点の主所属を外す
        $isPrimary = (bool)($data['is_primary'] ?? false);
        if ($isPrimary) {
            StaffBase::query()
                ->where('user_id', (int)$data['user_id'])
                ->update(['is_primary' => false]);
        }

        StaffBase::create([
            'user_id'     => (int)$data['user_id'],
            'base_id'     => (int)$data['base_id'],
            'is_primary'  => $isPrimary,
        ]);

        return redirect()
            ->route('admin.staff_bases.create')
            ->with('success', '職員の所属を登録しました。');
    }

    // -----------------------------------------------------
    // 共通：adminチェック（既存と合わせる）
    // -----------------------------------------------------
    private function ensureAdmin(Request $request): void
    {
        $u = $request->user();
        if (!$u || ($u->role ?? '') !== 'admin') {
            abort(403);
        }
    }
}
