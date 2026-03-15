<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $q            = trim((string)$request->query('q', ''));
        $schoolId     = $request->query('school_id');
        $grade        = $request->query('grade'); // ★追加
        $enrolledOnly = $request->boolean('enrolled_only', false);
        $allergyOnly  = $request->boolean('allergy_only', false); // ★追加

        // ★追加：ソート
        $sort = (string)$request->query('sort', '');
        $dir  = strtolower((string)$request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $adminReadKey = null;
        if (Schema::hasTable('family_message_admin_reads')) {
            if (Schema::hasColumn('family_message_admin_reads', 'family_message_id')) {
                $adminReadKey = 'family_message_id';
            } elseif (Schema::hasColumn('family_message_admin_reads', 'message_id')) {
                $adminReadKey = 'message_id';
            }
        }

        // ✅ fm.deleted_at を使うかどうか（DBに列が無いなら条件を付けない）
        $hasDeletedAt = Schema::hasColumn('family_messages', 'deleted_at');

        // ★ has_allergy / allergy_note があるか（移行途中でも落ちないように）
        $hasAllergyCols =
            Schema::hasColumn('children', 'has_allergy') &&
            Schema::hasColumn('children', 'allergy_note');

        // ★ birth_date があるか（移行途中でも落ちないように）
        $hasBirthDate = Schema::hasColumn('children', 'birth_date');

        // ★ソート許可（ホワイトリスト）
        // Blade側で ondblclick="sortUrl('...')" と渡しているキーに対応させる
        $allowedSortKeys = [
            'grade',
            'child_code',
            'name',       // last_name, first_name
            'kana',       // last_name_kana, first_name_kana
            'base',       // bases.name
            'birth_date', // children.birth_date
            'allergy',    // has_allergy, allergy_note
            'status',
        ];

        if (!in_array($sort, $allowedSortKeys, true)) {
            $sort = ''; // 不正キーは無視
        }

        $childrenQuery = Child::query()
            ->with([
                'baseMaster',
                'school',
                'guardians' => function ($q) {
                    $q->select(
                        'guardians.id',
                        'guardians.last_name',
                        'guardians.first_name',
                        'guardians.email',
                        'guardians.phone'
                    )->withPivot(['relationship', 'relation']);
                },
            ])
            // ✅ 未読件数を付与（paginateの前に！）
            ->select('children.*')
            ->selectSub(function ($sub) use ($adminReadKey, $hasDeletedAt) {
                $sub->from('family_messages as fm')
                    ->whereColumn('fm.child_id', 'children.id');

                if ($hasDeletedAt) {
                    $sub->whereNull('fm.deleted_at');
                }

                if (Schema::hasColumn('family_messages', 'sender_type')) {
                    $sub->where('fm.sender_type', 'family');
                }

                if ($adminReadKey) {
                    $sub->whereNotExists(function ($qq) use ($adminReadKey) {
                        $qq->from('family_message_admin_reads as fmr')
                            ->whereColumn("fmr.{$adminReadKey}", 'fm.id')
                            ->whereColumn('fmr.child_id', 'children.id');
                    });
                }

                $sub->selectRaw('COUNT(*)');
            }, 'unread_message_count');

        // ★ base 名でソートする可能性があるので LEFT JOIN（ソートで必要な時だけでもよいが簡潔に常時でOK）
        // ただしベーステーブル名を変えたくないので alias を使用
        $childrenQuery->leftJoin('bases as b', 'children.base_id', '=', 'b.id');

        // 検索条件
        $childrenQuery
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('children.last_name', 'like', "%{$q}%")
                        ->orWhere('children.first_name', 'like', "%{$q}%")
                        ->orWhere('children.last_name_kana', 'like', "%{$q}%")
                        ->orWhere('children.first_name_kana', 'like', "%{$q}%")
                        ->orWhere('children.name', 'like', "%{$q}%")
                        ->orWhere('children.child_code', 'like', "%{$q}%");
                });
            })
            ->when($schoolId !== null && $schoolId !== '', fn($query) => $query->where('children.school_id', $schoolId))
            ->when($grade !== null && $grade !== '', fn($query) => $query->where('children.grade', (int)$grade))
            ->when($enrolledOnly, fn($query) => $query->where('children.status', 'enrolled'));

        // ★アレルギー有りフィルタ
        if ($allergyOnly && $hasAllergyCols) {
            $childrenQuery->where('children.has_allergy', 1);
        }

        // ★ソート適用（ダブルクリック）
        if ($sort !== '') {
            switch ($sort) {
                case 'grade':
                    $childrenQuery->orderBy('children.grade', $dir)
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;

                case 'child_code':
                    $childrenQuery->orderBy('children.child_code', $dir)
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;

                case 'name':
                    $childrenQuery->orderBy('children.last_name', $dir)
                                  ->orderBy('children.first_name', $dir);
                    break;

                case 'kana':
                    $childrenQuery->orderBy('children.last_name_kana', $dir)
                                  ->orderBy('children.first_name_kana', $dir)
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;

                case 'base':
                    // base名がNULLの子もいるので最後尾に寄せたい場合はCOALESCEを使うが、まずは素直に
                    $childrenQuery->orderBy('b.name', $dir)
                                  ->orderBy('children.grade')
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;

                case 'birth_date':
                    if ($hasBirthDate) {
                        $childrenQuery->orderBy('children.birth_date', $dir)
                                      ->orderBy('children.grade')
                                      ->orderBy('children.last_name')
                                      ->orderBy('children.first_name');
                    } else {
                        // 列がないなら通常順へ
                        $childrenQuery->orderBy('children.grade')
                                      ->orderBy('children.last_name')
                                      ->orderBy('children.first_name');
                    }
                    break;

                case 'allergy':
                    if ($hasAllergyCols) {
                        // 「有り」を上にしたいなら desc 固定にする手もあるが、ここはdirに従う
                        $childrenQuery->orderBy('children.has_allergy', $dir)
                                      ->orderBy('children.allergy_note', $dir)
                                      ->orderBy('children.grade')
                                      ->orderBy('children.last_name')
                                      ->orderBy('children.first_name');
                    } else {
                        $childrenQuery->orderBy('children.grade')
                                      ->orderBy('children.last_name')
                                      ->orderBy('children.first_name');
                    }
                    break;

                case 'status':
                    $childrenQuery->orderBy('children.status', $dir)
                                  ->orderBy('children.grade')
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;

                default:
                    // 念のため
                    $childrenQuery->orderBy('children.grade')
                                  ->orderBy('children.last_name')
                                  ->orderBy('children.first_name');
                    break;
            }
        } else {
            // 既定の並び（今まで通り）
            $childrenQuery->orderBy('children.grade')
                          ->orderBy('children.last_name')
                          ->orderBy('children.first_name');
        }

        // ★ joinしたので distinct を入れて安全に（guardians joinしてないけど、将来拡張でも事故りにくい）
        $children = $childrenQuery
            ->distinct('children.id')
            ->paginate(20)
            ->appends($request->query());

        $siblingSummaryByChildId = [];
        $hasFamilyLoginCode = Schema::hasColumn('children', 'family_login_code');

        if ($children->isNotEmpty()) {
            $pageChildren = $children->getCollection();
            $pageChildIds = $pageChildren->pluck('id')->map(fn ($id) => (int)$id)->values()->all();

            if ($hasFamilyLoginCode) {
                $groupCodes = $pageChildren
                    ->map(fn ($c) => trim((string)($c->family_login_code ?: $c->child_code)))
                    ->filter(fn ($v) => $v !== '')
                    ->unique()
                    ->values();

                $membersByCode = [];
                if ($groupCodes->isNotEmpty()) {
                    $groupMembers = Child::query()
                        ->select(['id', 'last_name', 'first_name', 'child_code', 'family_login_code', 'grade'])
                        ->where(function ($q) use ($groupCodes) {
                            $q->whereIn('family_login_code', $groupCodes->all())
                                ->orWhereIn('child_code', $groupCodes->all());
                        })
                        ->orderBy('grade')
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get();

                    foreach ($groupMembers as $m) {
                        $code = trim((string)($m->family_login_code ?: $m->child_code));
                        if ($code === '') {
                            continue;
                        }
                        $membersByCode[$code][] = $m;
                    }
                }

                foreach ($pageChildren as $c) {
                    $code = trim((string)($c->family_login_code ?: $c->child_code));
                    $siblings = collect($membersByCode[$code] ?? [])
                        ->reject(fn ($m) => (int)$m->id === (int)$c->id)
                        ->map(fn ($m) => trim((string)$m->last_name . ' ' . (string)$m->first_name))
                        ->filter(fn ($name) => $name !== '')
                        ->values()
                        ->all();

                    $siblingSummaryByChildId[(int)$c->id] = [
                        'count' => count($siblings),
                        'names' => $siblings,
                        'code' => $code !== '' ? $code : null,
                    ];
                }
            } else {
                $pageLinks = DB::table('child_guardian')
                    ->whereIn('child_id', $pageChildIds)
                    ->get(['child_id', 'guardian_id']);

                $guardianIds = $pageLinks->pluck('guardian_id')->map(fn ($id) => (int)$id)->unique()->values()->all();
                $childIdsByGuardian = [];
                $guardianIdsByChild = [];

                if (!empty($guardianIds)) {
                    $allLinks = DB::table('child_guardian')
                        ->whereIn('guardian_id', $guardianIds)
                        ->get(['child_id', 'guardian_id']);

                    foreach ($allLinks as $link) {
                        $childId = (int)$link->child_id;
                        $guardianId = (int)$link->guardian_id;

                        $childIdsByGuardian[$guardianId][$childId] = true;
                        $guardianIdsByChild[$childId][$guardianId] = true;
                    }
                }

                $relatedChildIds = [];
                foreach ($childIdsByGuardian as $childIdSet) {
                    foreach (array_keys($childIdSet) as $cid) {
                        $relatedChildIds[(int)$cid] = true;
                    }
                }

                $relatedChildren = !empty($relatedChildIds)
                    ? Child::query()
                        ->whereIn('id', array_keys($relatedChildIds))
                        ->get(['id', 'last_name', 'first_name'])
                        ->keyBy('id')
                    : collect();

                foreach ($pageChildIds as $childId) {
                    $siblingIdSet = [];
                    $gSet = array_keys($guardianIdsByChild[$childId] ?? []);
                    foreach ($gSet as $gid) {
                        foreach (array_keys($childIdsByGuardian[$gid] ?? []) as $otherChildId) {
                            if ((int)$otherChildId === (int)$childId) {
                                continue;
                            }
                            $siblingIdSet[(int)$otherChildId] = true;
                        }
                    }

                    $siblings = collect(array_keys($siblingIdSet))
                        ->map(function ($sid) use ($relatedChildren) {
                            $c = $relatedChildren->get((int)$sid);
                            if (!$c) {
                                return null;
                            }
                            return trim((string)$c->last_name . ' ' . (string)$c->first_name);
                        })
                        ->filter(fn ($name) => !empty($name))
                        ->sort()
                        ->values()
                        ->all();

                    $siblingSummaryByChildId[(int)$childId] = [
                        'count' => count($siblings),
                        'names' => $siblings,
                        'code' => null,
                    ];
                }
            }
        }

        $schools = School::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // ★追加：grade / allergyOnly / sort / dir もViewへ渡す（Bladeが request() でも取れるが明示）
        return view('admin.children.index', compact(
            'children',
            'schools',
            'q',
            'schoolId',
            'grade',
            'enrolledOnly',
            'allergyOnly',
            'sort',
            'dir',
            'siblingSummaryByChildId',
        ));
    }

    public function create()
    {
        $bases = Base::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schools = School::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.children.create', compact('bases', 'schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name'       => ['required', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:50'],
            'last_name_kana'  => ['nullable', 'string', 'max:50'],
            'first_name_kana' => ['nullable', 'string', 'max:50'],
            'grade'           => ['required', 'integer', 'min:1', 'max:6'],
            'base_id'         => ['nullable', 'integer', 'exists:bases,id'],
            'school_id'       => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', 1)],
            'status'          => ['required', 'in:enrolled,withdrawn'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        // children.name がある環境用（互換）
        if (Schema::hasColumn('children', 'name')) {
            $validated['name'] = trim(($validated['last_name'] ?? '') . ' ' . ($validated['first_name'] ?? ''));
        }

        // children.base（文字列拠点名）カラムがある環境用（互換）
        if (Schema::hasColumn('children', 'base')) {
            $baseName = null;
            if (!empty($validated['base_id'])) {
                $baseName = Base::find((int)$validated['base_id'])?->name;
            }
            $validated['base'] = $baseName;
        }

        $child = DB::transaction(function () use ($validated) {
            // 4桁コード採番（ユニーク制約に守らせる）
            for ($i = 0; $i < 200; $i++) {
                $n = random_int(1, 9999);
                $code = str_pad((string)$n, 4, '0', STR_PAD_LEFT);

                try {
                    return Child::create($validated + [
                        'child_code' => $code,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $msg = $e->getMessage();

                    // unique違反なら採番やり直し。それ以外は投げる
                    if (
                        str_contains($msg, 'child_code')
                        && (str_contains($msg, 'Duplicate') || str_contains($msg, 'UNIQUE'))
                    ) {
                        continue;
                    }
                    throw $e;
                }
            }

            throw new \RuntimeException('child_code の採番に失敗しました。');
        });

        return redirect()
            ->route('admin.children.edit', $child)
            ->with('success', '児童を登録しました。続けて保護者を紐づけできます。');
    }

    public function edit(Child $child)
    {
        $child->load(['baseMaster', 'school', 'guardians']);

        $bases = Base::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $schools = School::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // 保護者検索（画面上で使う）
        $qg = trim((string)request()->query('qg', ''));
        $guardianResults = collect();

        if ($qg !== '') {
            $guardianResults = Guardian::query()
                ->where(function ($qq) use ($qg) {
                    $qq->where('last_name', 'like', "%{$qg}%")
                        ->orWhere('first_name', 'like', "%{$qg}%")
                        ->orWhere('last_name_kana', 'like', "%{$qg}%")
                        ->orWhere('first_name_kana', 'like', "%{$qg}%")
                        ->orWhere('email', 'like', "%{$qg}%")
                        ->orWhere('phone', 'like', "%{$qg}%")
                        ->orWhere('line_user_id', 'like', "%{$qg}%");
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(20)
                ->get();
        }

        return view('admin.children.edit', compact('child', 'bases', 'schools', 'qg', 'guardianResults'));
    }

    public function update(Request $request, Child $child)
    {
        $validated = $request->validate([
            'last_name'       => ['required', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:50'],
            'last_name_kana'  => ['nullable', 'string', 'max:50'],
            'first_name_kana' => ['nullable', 'string', 'max:50'],
            'grade'           => ['required', 'integer', 'min:1', 'max:6'],
            'base_id'         => ['nullable', 'integer', 'exists:bases,id'],
            'school_id'       => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', 1)],
            'status'          => ['required', 'in:enrolled,withdrawn'],
            'note'            => ['nullable', 'string', 'max:2000'],

            // ✅ 紐づけ
            'guardian_ids'    => ['sometimes', 'array'],
            'guardian_ids.*'  => ['integer', 'exists:guardians,id'],
            'relationships'   => ['sometimes', 'array'],
            'relationships.*' => ['nullable', 'string', 'max:30'],
        ]);

        // ✅ name（互換：常に埋める）
        if (Schema::hasColumn('children', 'name')) {
            $validated['name'] = trim((string)$request->last_name . ' ' . (string)$request->first_name);
        }

        // ✅ children.base（文字列拠点名）も同期（互換）
        if (Schema::hasColumn('children', 'base')) {
            $baseName = null;
            $baseId = $validated['base_id'] ?? null;
            if (!empty($baseId)) {
                $baseName = Base::find((int)$baseId)?->name;
            }
            $validated['base'] = $baseName;
        }

        $before = $child->status;

        DB::transaction(function () use ($request, $child, $validated) {
            // --- 児童本体更新 ---
            $childUpdate = $validated;
            unset($childUpdate['guardian_ids'], $childUpdate['relationships']);

            $child->fill($childUpdate);
            $child->save();

            // --- ✅ 保護者 sync ---
            $ids  = $request->input('guardian_ids', []);
            $rels = $request->input('relationships', []);

            $syncData = [];
            foreach ($ids as $gid) {
                $gid = (int)$gid;

                $rel = isset($rels[$gid]) ? trim((string)$rels[$gid]) : null;
                $rel = ($rel === '') ? null : $rel;

                // ✅ pivot の relationship と relation を両方埋める（DB仕様に合わせる）
                $syncData[$gid] = [
                    'relationship' => $rel,
                    'relation'     => $rel,
                ];
            }

            // 送られてきた guardian_ids だけにする（外したら消える）
            $child->guardians()->sync($syncData);
        });

        $child->refresh();

        Log::info('Child updated', [
            'id'             => $child->id,
            'status_before'  => $before,
            'status_after'   => $child->status,
            'payload_status' => $request->input('status'),
            'guardian_ids'   => $request->input('guardian_ids', []),
        ]);

        return redirect()
            ->route('admin.children.edit', ['child' => $child->id])
            ->with('success', '更新しました。');
    }
}
