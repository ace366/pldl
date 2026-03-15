<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\FamilyMessage;
use App\Models\FamilyMessageRead;
use App\Models\FamilyMessageAdminRead;
use App\Support\FamilyChildContext;
use App\Support\FamilyGuardianResolver;
use App\Support\FamilyMessageChildScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FamilyAuthController extends Controller
{
    public function showLogin()
    {
        return view('family.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            // child_code は 4桁数字のみ
            'child_code' => ['required', 'digits:4'],
        ], [
            'child_code.required' => '4桁のIDを入力してください。',
            'child_code.digits'   => '4桁の数字で入力してください。',
        ]);

        $code = $validated['child_code'];
        $hasFamilyLoginCode = Schema::hasColumn('children', 'family_login_code');

        $child = Child::query()
            ->where(function ($q) use ($code, $hasFamilyLoginCode) {
                $q->where('child_code', $code);
                if ($hasFamilyLoginCode) {
                    $q->orWhere('family_login_code', $code);
                }
            })
            ->where('status', 'enrolled') // 在籍のみ（運用に合わせて必要なら外す）
            ->orderByRaw('CASE WHEN child_code = ? THEN 0 ELSE 1 END', [$code])
            ->first();

        if (!$child) {
            return back()
                ->withErrors(['child_code' => 'そのIDの児童が見つかりません。'])
                ->withInput();
        }

        $familyLoginCode = $hasFamilyLoginCode
            ? (string)($child->family_login_code ?: $code)
            : $code;

        // 初回移行：family_login_code が空なら child_code を採用して埋める
        if ($hasFamilyLoginCode && empty($child->family_login_code) && !empty($child->child_code)) {
            $child->family_login_code = (string)$child->child_code;
            $child->save();
            $familyLoginCode = (string)$child->child_code;
        }

        // ご家庭ログイン状態（session）
        $request->session()->put('family_child_id', $child->id);
        $request->session()->put('family_active_child_id', $child->id);
        $request->session()->put('family_login_code', $familyLoginCode);

        // セッション固定化攻撃対策（ログイン時は regenerate 推奨）
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['family_child_id', 'family_active_child_id', 'family_login_code', 'family_guardian_id', 'family_guardian_by_child']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('family.login');
    }

    public function home(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $child = $ctx['activeChild'];
        $siblings = $ctx['siblings'];
        $currentGuardian = FamilyGuardianResolver::resolve($request, (int)$child->id);
        $messageChildIds = FamilyMessageChildScope::forFamily($request);
        if (empty($messageChildIds)) {
            $messageChildIds = [(int)$child->id];
        }

        $messages = FamilyMessage::query()
            ->whereIn('child_id', $messageChildIds)
            ->latest('id')
            ->take(500)
            ->get()
            ->reverse()
            ->values();

        $familyReadIds = FamilyMessageRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();
        $adminReadIds = FamilyMessageAdminRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();

        $familyReadSet = array_fill_keys($familyReadIds, true);
        $adminReadSet = array_fill_keys($adminReadIds, true);

        $messagesPayload = $messages->map(function ($m) use ($familyReadSet, $adminReadSet) {
            $sentAt = $m->published_at ?: $m->created_at;
            $from = $m->sender_type ?: 'admin';
            $isRead = $from === 'family'
                ? isset($adminReadSet[$m->id]) // 管理者が既読にしたか
                : isset($familyReadSet[$m->id]); // 保護者が既読にしたか

            return [
                'id' => $m->id,
                'title' => $m->title ?: null,
                'body' => $m->body,
                'sentAt' => optional($sentAt)->format('Y-m-d H:i'),
                'isRead' => $isRead,
                'readUrl' => route('family.messages.read', ['message' => $m->id, 'child_id' => (int)$m->child_id]),
                'from' => $from,
            ];
        })->values();

        $unreadCount = count(array_filter($messagesPayload->all(), fn ($m) => empty($m['isRead']) && ($m['from'] ?? '') === 'admin'));
        $siblingsPayload = $siblings->map(function ($s) use ($child) {
            $selectedGuardian = FamilyGuardianResolver::resolveForChild((int)$s->id);
            $avatarVersion = (int)optional($selectedGuardian?->updated_at)->timestamp;

            return [
                'id' => (int)$s->id,
                'name' => $s->full_name,
                'code' => $s->child_code,
                'grade' => (int)($s->grade ?? 0),
                'school' => (string)($s->school?->name ?? '—'),
                'isActive' => (int)$s->id === (int)$child->id,
                'avatarUrl' => route('family.profile.avatar.show', [
                    'child_id' => (int)$s->id,
                    'v' => $avatarVersion,
                ]),
                'homeUrl' => route('family.home', ['child_id' => (int)$s->id]),
            ];
        })->values();

        $userAgent = (string)($request->header('User-Agent') ?? '');
        $familyHomeProps = [
            'child' => [
                'name' => $child->full_name,
                'code' => $child->child_code,
                'school' => optional($child->school)->name ?? '—',
                'grade' => $child->grade ?? '—',
                'base' => optional($child->baseMaster)->name ?? (optional($child->base)->name ?? '—'),
            ],
            'messages' => $messagesPayload,
            'csrf' => csrf_token(),
            'parentAvatar' => route('family.profile.avatar.show', [
                'child_id' => (int)$child->id,
                'v' => (int)optional($currentGuardian?->updated_at)->timestamp,
            ]),
            'adminAvatar' => asset('images/512_512.jpg'),
            'unreadCount' => $unreadCount,
            'isMobile' => str_contains(strtolower($userAgent), 'mobile'),
            'replyUrl' => route('family.messages.reply', ['child_id' => (int)$child->id]),
            'readStatusUrl' => route('family.messages.read_status', ['child_id' => (int)$child->id]),
            'fetchMessagesUrl' => route('family.messages.latest', ['child_id' => (int)$child->id]),
            'siblings' => $siblingsPayload,
            'line' => [
                'isLinked' => !empty($currentGuardian?->line_user_id),
                'connectUrl' => $currentGuardian
                    ? route('family.line.link', ['guardian_id' => (int)$currentGuardian->id])
                    : null,
                'settingsUrl' => route('family.profile.edit', $currentGuardian ? ['guardian_id' => (int)$currentGuardian->id] : []),
            ],
        ];

        return view('family.home', [
            'child' => $child,
            'familyHomeProps' => $familyHomeProps,
        ]);
    }

}
