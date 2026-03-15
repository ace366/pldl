<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Enroll\EnrollController;
use App\Http\Controllers\NoticeController;

use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\BaseController;
use App\Http\Controllers\Admin\ChildController;
use App\Http\Controllers\Admin\GuardianController;
use App\Http\Controllers\Admin\NoticeController as AdminNoticeController;
use App\Http\Controllers\Admin\ChildMessageController;
use App\Http\Controllers\Admin\TodayAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AttendanceScanController;
use App\Http\Controllers\Admin\ChildGuardianEnrollmentController;
use App\Http\Controllers\Admin\ChildTelController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ChatThreadController;

use App\Http\Controllers\MyQrController;

use App\Http\Controllers\FamilyRegistrationController;
use App\Http\Controllers\Family\FamilyAuthController;
use App\Http\Controllers\Family\FamilyProfileController;
use App\Http\Controllers\Family\FamilySiblingController;
use App\Http\Controllers\Family\FamilyLineLinkController;

use App\Http\Middleware\EnsureFamilyChildAuthenticated;
use App\Http\Middleware\RedirectIfFamilyChildAuthenticated;
use App\Http\Middleware\EnsureWebOrFamilyAuthenticated;

use App\Http\Controllers\Staff\StaffAttendanceController;

use App\Http\Controllers\Admin\AttendanceIntentController;
use App\Http\Controllers\Admin\AttendanceIntentReactController;
use App\Http\Controllers\Admin\AttendanceIntentApiController;

// ✅ aliasに依存しないため「クラスを直接使う」
use App\Http\Middleware\EnsureAdmin;
Route::get('/_ping', function () {
    return 'pong';
});

/*
|--------------------------------------------------------------------------
| 公開（トップ）
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    abort(404);
});

/*
|--------------------------------------------------------------------------
| ご家庭用（子どもログイン）
|--------------------------------------------------------------------------
*/
Route::prefix('family')->name('family.')->group(function () {
    Route::get('/login', [FamilyAuthController::class, 'showLogin'])
        ->middleware(RedirectIfFamilyChildAuthenticated::class)
        ->name('login');

    Route::post('/login', [FamilyAuthController::class, 'login'])
        ->middleware(RedirectIfFamilyChildAuthenticated::class)
        ->name('login.post');

    Route::post('/logout', [FamilyAuthController::class, 'logout'])
        ->name('logout');

    // ログイン後ホーム
    Route::get('/home', [FamilyAuthController::class, 'home'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('home');

    Route::get('/child-qr', [\App\Http\Controllers\Family\ChildQrController::class, 'show'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('child.qr');

    Route::get('/child-qr/status', [\App\Http\Controllers\Family\ChildQrController::class, 'status'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('child.qr.status');

    // 参加可能日（カレンダー）
    Route::get('/availability', [\App\Http\Controllers\Family\AvailabilityController::class, 'index'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('availability.index');

    // 参加可能日のトグル（ON/OFF）
    Route::post('/availability/toggle', [\App\Http\Controllers\Family\AvailabilityController::class, 'toggle'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('availability.toggle');

    // 一括ON（※現状仕様維持）
    Route::post('availability/bulk-on', [\App\Http\Controllers\Family\AvailabilityController::class, 'bulkOn'])
        ->name('availability.bulk_on');

    // 管理者メッセージ：過去ログ
    Route::get('/messages', [\App\Http\Controllers\Family\FamilyMessageController::class, 'index'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.index');

    // 未読件数（スマホ下タブのバッジ用）
    Route::get('/messages/unread-count', [\App\Http\Controllers\Family\FamilyMessageController::class, 'unreadCount'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.unread_count');

    // 既読状態（相手の既読を取得）
    Route::get('/messages/read-status', [\App\Http\Controllers\Family\FamilyMessageController::class, 'readStatus'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.read_status');

    // 新着メッセージ（ポーリング）
    Route::get('/messages/latest', [\App\Http\Controllers\Family\FamilyMessageController::class, 'latest'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.latest');

    // 既読（1件）
    Route::post('/messages/{message}/read', [\App\Http\Controllers\Family\FamilyMessageController::class, 'markRead'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.read');

    // ご家庭からの返信
    Route::post('/messages/reply', [\App\Http\Controllers\Family\FamilyMessageController::class, 'reply'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('messages.reply');

    // お知らせ（家庭向け）
    Route::get('/notices', [\App\Http\Controllers\Family\FamilyNoticeController::class, 'index'])
        ->name('notices.index');

    // ご家庭：登録内容変更
    Route::get('/profile/edit', [FamilyProfileController::class, 'edit'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('profile.edit');
    Route::patch('/profile', [FamilyProfileController::class, 'update'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('profile.update');
    Route::post('/profile/avatar', [FamilyProfileController::class, 'updateAvatar'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('profile.avatar.update');
    Route::get('/profile/avatar-image', [FamilyProfileController::class, 'avatar'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('profile.avatar.show');
    Route::post('/profile/guardians', [FamilyProfileController::class, 'storeGuardian'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('profile.guardians.store');
    Route::get('/line/link', [FamilyLineLinkController::class, 'redirectToLine'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('line.link');
    Route::get('/line/callback', [FamilyLineLinkController::class, 'callback'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('line.callback');
    Route::post('/line/link-token', [FamilyLineLinkController::class, 'createLinkToken'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('line.link_token.create');
    Route::get('/siblings', [FamilySiblingController::class, 'index'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('siblings.index');
    Route::post('/siblings', [FamilySiblingController::class, 'store'])
        ->middleware(EnsureFamilyChildAuthenticated::class)
        ->name('siblings.store');
});

/*
|--------------------------------------------------------------------------
| enroll（児童＋保護者 登録フロー）
|--------------------------------------------------------------------------
*/
Route::prefix('enroll')->name('enroll.')->group(function () {
    Route::get('/', [EnrollController::class, 'create'])->name('create');
    Route::post('/confirm', [EnrollController::class, 'confirm'])->name('confirm');
    Route::post('/store', [EnrollController::class, 'store'])->name('store');
    Route::get('/complete', [EnrollController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| 保護者用 確認ページ（公開：署名URL必須）
|--------------------------------------------------------------------------
*/
Route::get('/guardian/confirm/{guardian}', [ChildGuardianEnrollmentController::class, 'confirm'])
    ->middleware('signed')
    ->name('guardian.confirm');

/*
|--------------------------------------------------------------------------
| 家庭の新規登録（guestのみ）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/family/register', [FamilyRegistrationController::class, 'create'])
        ->name('family.register');

    Route::post('/family/register', [FamilyRegistrationController::class, 'store'])
        ->name('family.register.store');
});

/*
|--------------------------------------------------------------------------
| ダッシュボード（全員：webログイン or 家庭ログイン）
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [NoticeController::class, 'index'])
    ->middleware([EnsureWebOrFamilyAuthenticated::class])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| auth 必須（共通）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::middleware(['role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('chats', [ChatThreadController::class, 'index'])->name('chats.index');
        Route::get('chats/{thread}', [ChatThreadController::class, 'show'])->name('chats.show');
        Route::post('chats/{thread}/reply', [ChatThreadController::class, 'reply'])->name('chats.reply');
        Route::patch('chats/{thread}/status', [ChatThreadController::class, 'updateStatus'])->name('chats.status.update');
        Route::get('chats/{thread}/messages', [ChatThreadController::class, 'messages'])->name('chats.messages');
        Route::get('messages/unread-count', [\App\Http\Controllers\Admin\AdminMessageNotificationController::class, 'unreadCount'])
            ->name('messages.unread_count');
    });

    // マイQR
    Route::get('/my-qr', [MyQrController::class, 'show'])
        ->middleware('perm:my_qr,view')
        ->name('myqr.show');

    /*
    |--------------------------------------------------------------------------
    | Staff（teacher / staff）
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::prefix('attendance')->name('attendance.')->group(function () {

            Route::get('today', [StaffAttendanceController::class, 'today'])
                ->middleware('perm:today_attendance,view')
                ->name('today');

            Route::post('clock-in', [StaffAttendanceController::class, 'clockIn'])
                ->middleware('perm:today_attendance,update')
                ->name('clock_in');

            Route::post('clock-out', [StaffAttendanceController::class, 'clockOut'])
                ->middleware('perm:today_attendance,update')
                ->name('clock_out');

            Route::get('qr', [StaffAttendanceController::class, 'qr'])
                ->middleware('perm:attendance_qr,view')
                ->name('qr');

            Route::get('history', [StaffAttendanceController::class, 'history'])
                ->name('history');

            // 必要なら復活
            Route::post('qr/clock', [StaffAttendanceController::class, 'qrClock'])
                ->middleware('perm:attendance_qr,update')
                ->name('qr_clock');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Staff/Teacher/Admin 表示許可（read-only 系）
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin,staff,teacher,user'])->prefix('admin')->name('admin.')->group(function () {
        /*
        | 参加予定・送迎管理（表示）
        */
        Route::get('/attendance-intents', [AttendanceIntentController::class, 'index'])
            ->middleware('perm:attendance_intents,view')
            ->name('attendance_intents.index');

        Route::get('/attendance-intents/react', [AttendanceIntentReactController::class, 'index'])
            ->middleware('perm:attendance_intents,view')
            ->name('attendance_intents.react');

        Route::get('/attendance-intents/api/summary', [AttendanceIntentApiController::class, 'summary'])
            ->middleware('perm:attendance_intents,view')
            ->name('attendance_intents.api.summary');

        /*
        | 参加予定・送迎管理（更新）
        */
        Route::post('/attendance-intents/toggle', [AttendanceIntentController::class, 'toggleStatus'])
            ->middleware('perm:attendance_intents,update')
            ->name('attendance_intents.toggle');

        Route::post('/attendance-intents/toggle-pickup', [AttendanceIntentController::class, 'togglePickup'])
            ->middleware('perm:attendance_intents,update')
            ->name('attendance_intents.toggle_pickup');

        Route::post('/attendance-intents/api/toggle-pickup', [AttendanceIntentApiController::class, 'togglePickup'])
            ->middleware('perm:attendance_intents,update')
            ->name('attendance_intents.api.toggle_pickup');

        Route::post('/attendance-intents/api/toggle-manual', [AttendanceIntentApiController::class, 'toggleManual'])
            ->middleware('perm:attendance_intents,update')
            ->name('attendance_intents.api.toggle_manual');

        /*
        | シフト（表示）
        */
        Route::get('/shifts', [\App\Http\Controllers\Admin\ShiftController::class, 'index'])
            ->middleware('perm:shift_day,view')
            ->name('shifts.index');

        Route::get('/shifts/month', [\App\Http\Controllers\Admin\ShiftController::class, 'month'])
            ->middleware('perm:shift_month,view')
            ->name('shifts.month');

        /*
        | シフト（作成/編集/削除）
        */
        Route::get('/shifts/create', [\App\Http\Controllers\Admin\ShiftController::class, 'create'])
            ->middleware('perm:shift_day,create')
            ->name('shifts.create');
        Route::post('/shifts', [\App\Http\Controllers\Admin\ShiftController::class, 'store'])
            ->middleware('perm:shift_day,create')
            ->name('shifts.store');
        Route::get('/shifts/{shift}/edit', [\App\Http\Controllers\Admin\ShiftController::class, 'edit'])
            ->middleware('perm:shift_day,update')
            ->name('shifts.edit');
        Route::patch('/shifts/{shift}', [\App\Http\Controllers\Admin\ShiftController::class, 'update'])
            ->middleware('perm:shift_day,update')
            ->name('shifts.update');
        Route::delete('/shifts/{shift}', [\App\Http\Controllers\Admin\ShiftController::class, 'destroy'])
            ->middleware('perm:shift_day,delete')
            ->name('shifts.destroy');

        /*
        | 勤怠（月次）表示
        */
        Route::get('/attendances', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])
            ->middleware('perm:attendance_month,view')
            ->name('attendances.index');

        Route::get('/attendances/user/{user}', [\App\Http\Controllers\Admin\AttendanceController::class, 'userMonth'])
            ->middleware('perm:attendance_month,view')
            ->name('attendances.user_month');

        Route::get('/attendances/export/csv', [\App\Http\Controllers\Admin\AttendanceController::class, 'exportCsv'])
            ->middleware('perm:attendance_month,view')
            ->name('attendances.export_csv');
        Route::get('/attendances/export/pdf', [\App\Http\Controllers\Admin\AttendanceController::class, 'exportPdf'])
            ->middleware('perm:attendance_month,view')
            ->name('attendances.export_pdf');

        /*
        | 監査ログ（表示）
        */
        Route::get('/attendance-logs', [\App\Http\Controllers\Admin\AttendanceLogController::class, 'index'])
            ->middleware('perm:audit_logs,view')
            ->name('attendance_logs.index');

        /*
        | 月次締め（表示）
        */
        Route::get('/closings', [\App\Http\Controllers\Admin\AttendanceClosingController::class, 'index'])
            ->middleware('perm:closings,view')
            ->name('closings.index');

        /*
        | マスタ（表示）
        */
        Route::get('bases', [BaseController::class, 'index'])
            ->middleware('perm:bases_master,view')
            ->name('bases.index');

        Route::get('schools', [SchoolController::class, 'index'])
            ->middleware('perm:schools_master,view')
            ->name('schools.index');

        /*
        | 児童一覧（メッセージ閲覧の入口）
        */
        Route::get('children', [ChildController::class, 'index'])
            ->middleware('perm:children_index,view')
            ->name('children.index');

        /*
        | マスタ（作成/編集/削除）
        */
        Route::get('bases/create', [BaseController::class, 'create'])
            ->middleware('perm:bases_master,create')
            ->name('bases.create');
        Route::post('bases', [BaseController::class, 'store'])
            ->middleware('perm:bases_master,create')
            ->name('bases.store');
        Route::get('bases/{base}/edit', [BaseController::class, 'edit'])
            ->middleware('perm:bases_master,update')
            ->name('bases.edit');
        Route::put('bases/{base}', [BaseController::class, 'update'])
            ->middleware('perm:bases_master,update')
            ->name('bases.update');
        Route::delete('bases/{base}', [BaseController::class, 'destroy'])
            ->middleware('perm:bases_master,delete')
            ->name('bases.destroy');

        Route::get('schools/create', [SchoolController::class, 'create'])
            ->middleware('perm:schools_master,create')
            ->name('schools.create');
        Route::post('schools', [SchoolController::class, 'store'])
            ->middleware('perm:schools_master,create')
            ->name('schools.store');
        Route::get('schools/{school}/edit', [SchoolController::class, 'edit'])
            ->middleware('perm:schools_master,update')
            ->name('schools.edit');
        Route::put('schools/{school}', [SchoolController::class, 'update'])
            ->middleware('perm:schools_master,update')
            ->name('schools.update');
        Route::delete('schools/{school}', [SchoolController::class, 'destroy'])
            ->middleware('perm:schools_master,delete')
            ->name('schools.destroy');

        /*
        | 児童（作成/編集/削除）
        */
        Route::get('children/create', [ChildController::class, 'create'])
            ->middleware('perm:children_index,create')
            ->name('children.create');
        Route::post('children', [ChildController::class, 'store'])
            ->middleware('perm:children_index,create')
            ->name('children.store');
        Route::get('children/{child}/edit', [ChildController::class, 'edit'])
            ->middleware('perm:children_index,update')
            ->name('children.edit');
        Route::put('children/{child}', [ChildController::class, 'update'])
            ->middleware('perm:children_index,update')
            ->name('children.update');

        /*
        | 保護者（作成）
        */
        Route::get('guardians', [GuardianController::class, 'index'])
            ->middleware('perm:guardians_index,view')
            ->name('guardians.index');
        Route::get('guardians/create', [GuardianController::class, 'create'])
            ->middleware('perm:guardians_index,create')
            ->name('guardians.create');
        Route::post('guardians', [GuardianController::class, 'store'])
            ->middleware('perm:guardians_index,create')
            ->name('guardians.store');

        /*
        | ユーザー一覧/権限変更
        */
        Route::get('/users', [AdminUserController::class, 'index'])
            ->middleware('perm:admin_users,view')
            ->name('users.index');

        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->middleware('perm:admin_users,update')
            ->name('users.updateRole');

        /*
        | TEL票（やり取り履歴）
        */
        Route::get('children/{child}/tel', [ChildTelController::class, 'index'])
            ->middleware('perm:children_index,view')
            ->name('children.tel.index');

        Route::post('children/{child}/tel', [ChildTelController::class, 'store'])
            ->middleware('perm:children_index,create')
            ->name('children.tel.store');

        /*
        | 児童メッセージ（表示/既読）
        */
        Route::get('children/{child}/messages', [ChildMessageController::class, 'index'])
            ->middleware('perm:children_index,view')
            ->name('children.messages.index');

        Route::post('children/{child}/messages/{message}/read', [ChildMessageController::class, 'markRead'])
            ->middleware('perm:children_index,view')
            ->name('children.messages.read');

        Route::get('children/{child}/messages/read-status', [ChildMessageController::class, 'readStatus'])
            ->middleware('perm:children_index,view')
            ->name('children.messages.read_status');

        Route::get('children/{child}/messages/latest', [ChildMessageController::class, 'latest'])
            ->middleware('perm:children_index,view')
            ->name('children.messages.latest');

        Route::get('children/{child}/messages/parent-avatar', [ChildMessageController::class, 'parentAvatar'])
            ->middleware('perm:children_index,view')
            ->name('children.messages.parent_avatar');

        // 当日の参加者一覧
        Route::get('children/today', [TodayAttendanceController::class, 'index'])
            ->middleware('perm:children_index,view')
            ->name('children.today');

        Route::get('children/today/react', [TodayAttendanceController::class, 'react'])
            ->middleware('perm:children_index,view')
            ->name('children.today.react');

        Route::get('children/today/summary', [TodayAttendanceController::class, 'summary'])
            ->middleware('perm:children_index,view')
            ->name('children.today.summary');

        // 帰宅
        Route::post('children/{child}/checkout', [TodayAttendanceController::class, 'checkout'])
            ->middleware('perm:children_index,update')
            ->name('children.checkout');

        /*
        | 児童QR読み取り（出席登録）
        */
        Route::get('/attendance/scan', [AttendanceScanController::class, 'scan'])
            ->middleware('perm:child_qr_scan,view')
            ->name('attendance.scan');

        Route::post('/attendance/log', [AttendanceScanController::class, 'log'])
            ->middleware('perm:child_qr_scan,update')
            ->name('attendance.log');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin（EnsureAdmin をクラス指定で一括管理：aliasに依存しない）
    |--------------------------------------------------------------------------
    */
    Route::middleware([EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {

        /*
        | お知らせ（管理）
        */
        Route::get('/notices/edit', [AdminNoticeController::class, 'edit'])
            ->name('notices.edit');

        Route::put('/notices', [AdminNoticeController::class, 'update'])
            ->name('notices.update');

        Route::post('/notices/images', [AdminNoticeController::class, 'uploadImage'])
            ->name('notices.images.store');

        /*
        | 給与管理（管理者専用）
        */
        Route::get('/payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])
            ->name('payroll.index');
        Route::get('/payroll/withholding/import', [\App\Http\Controllers\Admin\WithholdingTaxImportController::class, 'index'])
            ->name('payroll.withholding.index');
        Route::post('/payroll/withholding/import', [\App\Http\Controllers\Admin\WithholdingTaxImportController::class, 'import'])
            ->name('payroll.withholding.import');
        Route::post('/payroll/withholding/import/mapped', [\App\Http\Controllers\Admin\WithholdingTaxImportController::class, 'importMapped'])
            ->name('payroll.withholding.import_mapped');
        Route::get('/payroll/{user}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])
            ->name('payroll.show');
        Route::post('/payroll/{user}/payment', [\App\Http\Controllers\Admin\PayrollController::class, 'savePayment'])
            ->name('payroll.payment.save');

        /*
        | 権限設定（admin専用）
        */
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');
        Route::put('/permissions', [PermissionController::class, 'update'])
            ->name('permissions.update');

        /*
        | 職員所属（staff_bases）
        */
        Route::get('/staff-bases/create', [\App\Http\Controllers\Admin\StaffBaseController::class, 'create'])
            ->name('staff_bases.create');

        Route::post('/staff-bases', [\App\Http\Controllers\Admin\StaffBaseController::class, 'store'])
            ->name('staff_bases.store');

        /*
        | 児童＋保護者 一括登録
        */
        Route::get('enrollments/child-guardian/create', [ChildGuardianEnrollmentController::class, 'create'])
            ->name('enrollments.child_guardian.create');

        Route::post('enrollments/child-guardian', [ChildGuardianEnrollmentController::class, 'store'])
            ->name('enrollments.child_guardian.store');

        Route::get('enrollments/child-guardian/completed', [ChildGuardianEnrollmentController::class, 'completed'])
            ->name('enrollments.child_guardian.completed');

        /*
        | 児童メッセージ（送信はadminのみ）
        */
        Route::post('children/{child}/messages', [ChildMessageController::class, 'store'])
            ->middleware('perm:children_index,create')
            ->name('children.messages.store');

        Route::get('/attendances/{shiftAttendance}/edit', [\App\Http\Controllers\Admin\AttendanceController::class, 'edit'])
            ->middleware('perm:attendance_month,update')
            ->name('attendances.edit');
        Route::patch('/attendances/{shiftAttendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])
            ->middleware('perm:attendance_month,update')
            ->name('attendances.update');
        Route::post('/closings/close', [\App\Http\Controllers\Admin\AttendanceClosingController::class, 'close'])
            ->middleware('perm:closings,update')
            ->name('closings.close');
        Route::post('/closings/open', [\App\Http\Controllers\Admin\AttendanceClosingController::class, 'open'])
            ->middleware('perm:closings,update')
            ->name('closings.open');
    });

    /*
    |--------------------------------------------------------------------------
    | プロフィール（auth）
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | （auth）admin enroll（既存仕様維持）
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/enroll', [EnrollmentController::class, 'create'])->name('admin.enroll.create');
    Route::post('/admin/enroll', [EnrollmentController::class, 'store'])->name('admin.enroll.store');
});

/*
|--------------------------------------------------------------------------
| auth scaffolding
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
