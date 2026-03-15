<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ChildContactLog;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChildTelController extends Controller
{
    /**
     * TEL票ページ
     * - 上部：児童（左）＋保護者（右）を固定表示（Blade側でsticky）
     * - 下部：履歴（最新→過去）10件ページネーション
     * - 履歴には「種別」「入力者」を表示
     */
    public function index(Request $request, Child $child)
    {
        $child->load([
            'baseMaster',
            'school',
            'guardians',
        ]);
        $siblings = $this->resolveSiblings($child);

        $logs = ChildContactLog::query()
            ->where('child_id', $child->id)
            ->with(['creator']) // ✅ 誰が入力したか
            ->latest('id')
            ->paginate(10)
            ->appends($request->query());

        // 種別の表示ラベル
        $channelLabels = $this->channelLabels();

        return view('admin.children.tel', [
            'child'         => $child,
            'siblings'      => $siblings,
            'logs'          => $logs,
            'channelLabels' => $channelLabels,
        ]);
    }

    /**
     * TEL票追加
     * - タイトル必須
     * - 内容必須
     * - 種別必須（tel/meeting/mail/other）
     * - created_by にログインユーザIDを保存
     */
    public function store(Request $request, Child $child)
    {
        $allowedChannels = array_keys($this->channelLabels());

        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:120'],
            'body'    => ['required', 'string', 'max:5000'],
            'channel' => ['required', 'in:' . implode(',', $allowedChannels)],
        ], [
            'title.required'   => 'タイトルは必須です。',
            'body.required'    => '内容は必須です。',
            'channel.required' => '種別は必須です。',
        ]);

        DB::transaction(function () use ($validated, $child, $request) {
            ChildContactLog::create([
                'child_id'    => $child->id,
                'guardian_id' => null, // 今回は「のみ追加」なので未使用（将来拡張可）
                'created_by'  => $request->user()->id ?? null,

                'title'   => $validated['title'],
                'body'    => $validated['body'],
                'channel' => $validated['channel'],
            ]);
        });

        return redirect()
            ->route('admin.children.tel.index', $child)
            ->with('success', 'TEL票を登録しました。');
    }

    /**
     * 種別ラベル
     */
    private function channelLabels(): array
    {
        return [
            'tel'     => '電話',
            'meeting' => '面談',
            'mail'    => 'メール',
            'other'   => 'その他',
        ];
    }

    private function resolveSiblings(Child $child): Collection
    {
        $hasFamilyLoginCode = Schema::hasColumn('children', 'family_login_code');

        if ($hasFamilyLoginCode) {
            $code = trim((string)($child->family_login_code ?: $child->child_code));
            if ($code !== '') {
                return Child::query()
                    ->select(['id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana', 'grade', 'family_login_code', 'child_code'])
                    ->where(function ($q) use ($code) {
                        $q->where('family_login_code', $code)
                            ->orWhere('child_code', $code);
                    })
                    ->where('id', '!=', $child->id)
                    ->orderBy('grade')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();
            }
        }

        $guardianIds = DB::table('child_guardian')
            ->where('child_id', $child->id)
            ->pluck('guardian_id')
            ->map(fn ($id) => (int)$id)
            ->filter()
            ->values()
            ->all();

        if (empty($guardianIds)) {
            return collect();
        }

        $siblingIds = DB::table('child_guardian')
            ->whereIn('guardian_id', $guardianIds)
            ->where('child_id', '!=', $child->id)
            ->pluck('child_id')
            ->map(fn ($id) => (int)$id)
            ->unique()
            ->values()
            ->all();

        if (empty($siblingIds)) {
            return collect();
        }

        return Child::query()
            ->select(['id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana', 'grade'])
            ->whereIn('id', $siblingIds)
            ->orderBy('grade')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
