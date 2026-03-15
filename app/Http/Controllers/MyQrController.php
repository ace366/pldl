<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use Illuminate\Http\Request;

class MyQrController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();

        // ✅ 権限で制御（ロール固定にしない）
        $canView = RolePermissionService::canUser($u, 'my_qr', 'view');
        abort_unless($canView, 403, 'このQRは表示権限がありません。');

        // ✅ QRは「users.id」を基準にする（要望どおり）
        $staffId = (string)$u->id;

        // 表示用
        $name = $u->name ?? trim(($u->last_name ?? '') . ' ' . ($u->first_name ?? ''));
        $name = $name !== '' ? $name : '—';

        // ✅ 管理者は「学校」ではなく「拠点」を表示
        $baseName = $this->guessBaseName($u);

        // ✅ スタッフQR payload（生徒・児童と確実に区別）
        $qrPayload = 'STAFF_ID:' . $staffId;

        return view('my_qr.show', [
            'qrPayload' => $qrPayload,
            'loginId'   => $staffId,     // 画面にもIDを表示
            'name'      => $name,

            // スタッフ画面では学校は使わない（JSON表示事故の根治）
            'orgLabel'  => '拠点',
            'orgValue'  => $baseName,

            // gradeはスタッフだと無いことが多いので “—” 固定でOK
            'grade'     => '—',
        ]);
    }

    /**
     * 拠点名を推測取得（存在するものだけ使う）
     * - baseMaster.name
     * - base.name
     * - base_name
     * - base_master_name
     */
    private function guessBaseName($user): string
    {
        $candidates = [
            data_get($user, 'baseMaster.name'),
            data_get($user, 'base.name'),
            data_get($user, 'base_name'),
            data_get($user, 'base_master_name'),
        ];

        foreach ($candidates as $v) {
            $s = is_string($v) ? trim($v) : '';
            if ($s !== '') return $s;
        }
        return '—';
    }
}
