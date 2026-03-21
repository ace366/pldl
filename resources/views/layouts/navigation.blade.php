<nav x-data="{
        mobileMenu: false,
        sections: {
            general: true,
            kintai: true,
            ops: true,
            account: true,
        },
        toggleSection(name) {
            this.sections[name] = !this.sections[name];
        },
    }"
     @keydown.escape.window="mobileMenu = false"
     class="bg-white border-b border-gray-100
            fixed top-0 inset-x-0 z-50 shadow">
    @php
        $isFamilySession = session()->has('family_child_id');
        $isFamilyRoute = request()->routeIs('family.*');
        $isFamily = $isFamilySession && (!Auth::check() || $isFamilyRoute);

        $authUser = Auth::user();
        $role = $authUser->role ?? 'user';

        $isStaffRole = in_array($role, ['teacher', 'staff'], true);
        $isAdminRole = ($role === 'admin');
        $isAdminOrStaffRole = (Auth::check() && in_array($role, ['admin', 'staff'], true));
        $showAdminMobileSidebar = (Auth::check() && $isAdminRole && !$isFamily);

        $permCan = function (string $feature, string $action = 'view') use ($role) {
            return \App\Services\RolePermissionService::canRole((string)$role, $feature, $action);
        };

        $canMyQr = $permCan('my_qr');
        $canTodayAttendance = $permCan('today_attendance');
        $canAttendanceQr = $permCan('attendance_qr');
        $canChildQrScan = $permCan('child_qr_scan');
        $canShiftDay = $permCan('shift_day');
        $canShiftCreate = $permCan('shift_day', 'create');
        $canShiftMonth = $permCan('shift_month');
        $canAttendanceMonth = $permCan('attendance_month');
        $canAuditLogs = $permCan('audit_logs');
        $canClosings = $permCan('closings');
        $canAttendanceIntents = $permCan('attendance_intents');
        $canSchools = $permCan('schools_master');
        $canBases = $permCan('bases_master');
        $canChildren = $permCan('children_index');
        $canGuardians = $permCan('guardians_index');
        $canAdminUsers = $permCan('admin_users');
        $canShowKintaiDropdown = $canTodayAttendance || $canAttendanceQr || $canShiftDay || $canShiftMonth || $canAttendanceMonth || $canAuditLogs || $canClosings || $isAdminRole;
        $canOpsMenu = $canAttendanceIntents || $canChildQrScan || $canSchools || $canBases || $canChildren || $canGuardians || $canAdminUsers;

        // ----------------------------
        // URL/Route 解決（例外防止）
        // ----------------------------

        // おしらせリンク
        $noticeHref = $isFamily
            ? (\Illuminate\Support\Facades\Route::has('family.notices.index') ? route('family.notices.index') : ( \Illuminate\Support\Facades\Route::has('family.home') ? route('family.home') : url('/') ))
            : (\Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/'));

        // ✅ 自分のQR（auth）
        $myQrHref = null;
        if ($isFamily && \Illuminate\Support\Facades\Route::has('family.child.qr')) {
            $myQrHref = route('family.child.qr');
        } elseif (Auth::check() && $canMyQr) {
            if (\Illuminate\Support\Facades\Route::has('myqr.show')) {
                $myQrHref = route('myqr.show');
            } elseif (\Illuminate\Support\Facades\Route::has('my-qr')) {
                $myQrHref = route('my-qr');
            } else {
                $myQrHref = url('/my-qr');
            }
        } elseif (Auth::check() && \Illuminate\Support\Facades\Route::has('qr.show')) {
            $myQrHref = route('qr.show');
        }

        // ✅ スタッフ用QR読み取り（出退勤）
        $staffQrScanHref = null;
        if (Auth::check() && $canAttendanceQr && \Illuminate\Support\Facades\Route::has('staff.attendance.qr')) {
            $staffQrScanHref = route('staff.attendance.qr');
        }

        // ✅ 児童用QR読み取り（出席登録）…adminのみ
        $childQrScanHref = null;
        if (Auth::check() && $canChildQrScan && \Illuminate\Support\Facades\Route::has('admin.attendance.scan')) {
            $childQrScanHref = route('admin.attendance.scan');
        }

        // ✅ 参加予定（送迎）…staff/teacher/admin（Blade版）
        $pickupHref = null;
        if (Auth::check() && $canAttendanceIntents && \Illuminate\Support\Facades\Route::has('admin.attendance_intents.index')) {
            $pickupHref = route('admin.attendance_intents.index');
        }

        // ✅ 参加予定（送迎）…staff/teacher/admin（React版：スマホ下タブのデフォルトにする）
        $pickupReactHref = null;
        if (Auth::check() && $canAttendanceIntents && \Illuminate\Support\Facades\Route::has('admin.attendance_intents.react')) {
            $pickupReactHref = route('admin.attendance_intents.react');
        }

        // ✅ 今日の勤怠（打刻）：staff/teacher/admin
        $todayKintaiHref = null;
        if (Auth::check() && $canTodayAttendance && \Illuminate\Support\Facades\Route::has('staff.attendance.today')) {
            $todayKintaiHref = route('staff.attendance.today');
        }

        // 勤怠リンク（下タブの「勤怠」アイコン）
        $kintaiHref = null;
        if ($todayKintaiHref) {
            $kintaiHref = $todayKintaiHref;
        } elseif (Auth::check() && $canShiftDay && \Illuminate\Support\Facades\Route::has('admin.shifts.index')) {
            $kintaiHref = route('admin.shifts.index');
        }

        $shiftCreateHref = null;
        if (Auth::check() && $canShiftCreate) {
            if (\Illuminate\Support\Facades\Route::has('admin.shifts.create.react')) {
                $shiftCreateHref = route('admin.shifts.create.react');
            } elseif (\Illuminate\Support\Facades\Route::has('admin.shifts.create')) {
                $shiftCreateHref = route('admin.shifts.create');
            }
        }

        // 児童管理（adminのみ）
        $childrenIndexHref = (Auth::check() && $canChildren && \Illuminate\Support\Facades\Route::has('admin.children.index'))
            ? route('admin.children.index')
            : null;

        // メッセージ（新チャット優先）
        $staffMessagesHref = null;
        if ($isAdminOrStaffRole && \Illuminate\Support\Facades\Route::has('admin.chats.index')) {
            $staffMessagesHref = route('admin.chats.index');
        } elseif ($isAdminOrStaffRole && $canChildren && \Illuminate\Support\Facades\Route::has('admin.children.index')) {
            $staffMessagesHref = route('admin.children.index');
        }

        $isActiveStaffMessages = request()->routeIs('admin.chats.*')
            || request()->routeIs('admin.children.messages.*');

        // ✅ 児童（family）用：参加よてい（スマホ下タブ用）
        $familyAvailabilityHref = null;
        if ($isFamily && \Illuminate\Support\Facades\Route::has('family.availability.index')) {
            $familyAvailabilityHref = route('family.availability.index');
        }

        // ✅ 児童（family）用：メッセージ（スマホ下タブ用）
        $familyMessagesHref = null;
        if ($isFamily && \Illuminate\Support\Facades\Route::has('family.home')) {
            $familyMessagesHref = route('family.home');
        }

        $familyProfileHref = null;
        if ($isFamily && \Illuminate\Support\Facades\Route::has('family.profile.edit')) {
            $familyProfileHref = route('family.profile.edit');
        }
        $familySiblingsHref = null;
        if ($isFamily && \Illuminate\Support\Facades\Route::has('family.siblings.index')) {
            $familySiblingsHref = route('family.siblings.index');
        }

        $isActiveFamilyMessages = $isFamily
            && (request()->routeIs('family.home') || request()->routeIs('family.messages.*'));
        $isActiveFamilyProfile = $isFamily && request()->routeIs('family.profile.*');
        $isActiveFamilySiblings = $isFamily && request()->routeIs('family.siblings.*');

        $familyUnreadCount = 0;
        if ($isFamily) {
            $familyChildId = (int)session()->get('family_active_child_id', session()->get('family_child_id'));
            if ($familyChildId) {
                $familyUnreadCount = \App\Models\FamilyMessage::query()
                    ->where('child_id', $familyChildId)
                    ->when(\Illuminate\Support\Facades\Schema::hasColumn('family_messages', 'sender_type'), function ($q) {
                        $q->where('sender_type', 'admin');
                    })
                    ->whereNotExists(function ($q) use ($familyChildId) {
                        $q->from('family_message_reads as r')
                        ->whereColumn('r.family_message_id', 'family_messages.id')
                        ->where('r.child_id', $familyChildId);
                    })
                    ->count();
            }
        }

        // ----------------------------
        // スマホ下タブ：4つ目アイコン（管理者）
        // ✅ 参加者一覧（React）を最優先 → 次に送迎 → 次に児童QR → 次に児童管理
        // ----------------------------
        $adminFourthHref = null;
        $adminFourthLabel = null;
        $adminFourthIcon = null;
        $adminFourthActive = false;

        $todayParticipantsHref = null;
        if (Auth::check() && $canChildren && \Illuminate\Support\Facades\Route::has('admin.children.today.react')) {
            $todayParticipantsHref = route('admin.children.today.react');
        }

        // ★スマホ下タブはReact優先
        $pickupForMobile = $pickupReactHref ?: $pickupHref;

        if ($todayParticipantsHref) {
            $adminFourthHref = $todayParticipantsHref;
            $adminFourthLabel = '参加者';
            $adminFourthIcon = '📋';
            $adminFourthActive = request()->routeIs('admin.children.today.react')
                || request()->routeIs('admin.children.today.*');
        } elseif ($pickupForMobile) {
            $adminFourthHref = $pickupForMobile;
            $adminFourthLabel = '送迎';
            $adminFourthIcon = '🚗';

            $adminFourthActive =
                request()->routeIs('attendance_intents.react')
                || request()->routeIs('attendance_intents.api.*')
                || request()->routeIs('admin.attendance_intents.*');
        } elseif ($childQrScanHref) {
            $adminFourthHref = $childQrScanHref;
            $adminFourthLabel = '児童QR';
            $adminFourthIcon = '📷';
            $adminFourthActive = request()->routeIs('admin.attendance.scan');
        } elseif ($childrenIndexHref) {
            $adminFourthHref = $childrenIndexHref;
            $adminFourthLabel = '児童管理';
            $adminFourthIcon = '🧒';
            $adminFourthActive = request()->routeIs('admin.children.*');
        }

        // ログアウト
        $logoutAction = $isFamily
            ? (\Illuminate\Support\Facades\Route::has('family.logout') ? route('family.logout') : url('/family/logout'))
            : (\Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout'));

        // PCで「今日の勤怠（打刻）」リンクを出すか
        $showTodayKintaiLink = (bool)$todayKintaiHref;
        $profileHref = Auth::check() && \Illuminate\Support\Facades\Route::has('profile.edit')
            ? route('profile.edit')
            : '#';
        $attendanceHistoryHref = \Illuminate\Support\Facades\Route::has('staff.attendance.history')
            ? route('staff.attendance.history')
            : null;
        $attendanceMonthHref = ($canAttendanceMonth && \Illuminate\Support\Facades\Route::has('admin.attendances.index'))
            ? route('admin.attendances.index')
            : null;
        $auditLogsHref = ($canAuditLogs && \Illuminate\Support\Facades\Route::has('admin.attendance_logs.index'))
            ? route('admin.attendance_logs.index')
            : null;
        $closingsHref = ($canClosings && \Illuminate\Support\Facades\Route::has('admin.closings.index'))
            ? route('admin.closings.index')
            : null;
        $payrollHref = ($isAdminRole && \Illuminate\Support\Facades\Route::has('admin.payroll.index'))
            ? route('admin.payroll.index')
            : null;
        $withholdingHref = ($isAdminRole && \Illuminate\Support\Facades\Route::has('admin.payroll.withholding.index'))
            ? route('admin.payroll.withholding.index', ['year' => now()->format('Y')])
            : null;
        $schoolsHref = ($canSchools && \Illuminate\Support\Facades\Route::has('admin.schools.index'))
            ? route('admin.schools.index')
            : null;
        $basesHref = ($canBases && \Illuminate\Support\Facades\Route::has('admin.bases.index'))
            ? route('admin.bases.index')
            : null;
        $guardiansHref = ($canGuardians && \Illuminate\Support\Facades\Route::has('admin.guardians.index'))
            ? route('admin.guardians.index')
            : null;
        $adminUsersHref = ($canAdminUsers && \Illuminate\Support\Facades\Route::has('admin.users.index'))
            ? route('admin.users.index')
            : null;
        $permissionsHref = ($isAdminRole && \Illuminate\Support\Facades\Route::has('admin.permissions.index'))
            ? route('admin.permissions.index')
            : null;
        $childrenTodayHref = ($canChildren && \Illuminate\Support\Facades\Route::has('admin.children.today'))
            ? route('admin.children.today')
            : null;

        // Active判定
        $isActiveMyQr = request()->is('my-qr*') || request()->routeIs('myqr.*') || request()->routeIs('qr.*') || request()->routeIs('family.child.qr');
        $isActiveKintai = request()->routeIs('staff.attendance.*')
            || request()->routeIs('admin.shifts.index')
            || request()->routeIs('admin.shifts.month')
            || request()->routeIs('admin.shifts.edit')
            || request()->routeIs('admin.attendances.*')
            || request()->routeIs('admin.attendance_logs.*')
            || request()->routeIs('admin.closings.*')
            || request()->routeIs('admin.payroll.*');

        $isActiveNotice = $isFamily ? request()->routeIs('family.notices.*') : request()->routeIs('dashboard');
        $isActiveShiftCreate = request()->routeIs('admin.shifts.create')
            || request()->routeIs('admin.shifts.create.react');

        // 児童QR読み取り（出席登録）をPCナビでActive扱いにする
        $isActiveChildScan = request()->routeIs('admin.attendance.scan') || request()->routeIs('admin.attendance.*');

        // 送迎をPCでActive扱いにする（ドロップダウン等で使える）
        // ★React も Active に含める
        $isActivePickup =
            request()->routeIs('admin.attendance_intents.*')
            || request()->routeIs('attendance_intents.react')
            || request()->routeIs('attendance_intents.api.*');
        $isActiveOpsMenu =
            request()->routeIs('admin.attendance.*')
            || request()->routeIs('admin.attendance_intents.*')
            || request()->routeIs('attendance_intents.react')
            || request()->routeIs('attendance_intents.api.*')
            || request()->routeIs('admin.schools.*')
            || request()->routeIs('admin.bases.*')
            || request()->routeIs('admin.children.*')
            || request()->routeIs('admin.guardians.*')
            || request()->routeIs('admin.users.*')
            || request()->routeIs('admin.permissions.*');
        $isActiveAccount = request()->routeIs('profile.*');

        // ✅ 児童（family）参加よてい Active
        $isActiveFamilyAvailability = $isFamily && request()->routeIs('family.availability.*');
    @endphp

    <!-- メインナビ（PCは左寄せ） -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-12">
            @if($showAdminMobileSidebar)
                <div class="flex w-full items-center justify-between sm:hidden">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-2xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:border-gray-300 hover:text-gray-900"
                        aria-label="管理メニュー"
                        @click="mobileMenu = true"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex flex-1 justify-center px-3">
                        <a href="{{ $noticeHref }}" class="inline-flex items-center justify-center">
                            <img src="{{ asset('images/ver2.png') }}"
                                 alt="{{ config('app.name') }}"
                                 class="h-9 w-auto">
                        </a>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-50 text-[10px] font-bold text-gray-400">
                        管理
                    </div>
                </div>
            @endif

            <!-- 左側：ロゴ＋PCリンク群（左寄せ固定） -->
            <div class="{{ $showAdminMobileSidebar ? 'hidden sm:flex' : 'flex' }} items-center flex-1 min-w-0">
                <!-- ロゴ -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $isFamily ? ( \Illuminate\Support\Facades\Route::has('family.home') ? route('family.home') : url('/') ) : ( \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') ) }}">
                        <img src="{{ asset('images/ver2.png') }}"
                             alt="{{ config('app.name') }}"
                             class="h-10 w-auto">
                    </a>
                </div>

                <!-- ナビ（PC） -->
                <div class="hidden sm:flex sm:items-center sm:gap-8 sm:ms-10">
                    <x-nav-link :href="$noticeHref" :active="$isActiveNotice">
                        おしらせ
                    </x-nav-link>

                    @if($isFamily && $familyMessagesHref)
                        <x-nav-link :href="$familyMessagesHref" :active="$isActiveFamilyMessages">
                            メッセージ
                        </x-nav-link>
                    @endif

                    {{-- ✅ マイQR（スタッフ/管理者も /my-qr を出す） --}}
                    @if($myQrHref)
                        <x-nav-link :href="$myQrHref" :active="$isActiveMyQr">
                            マイQR
                        </x-nav-link>
                    @endif

                    @if($pickupHref)
                        <x-nav-link :href="$pickupHref" :active="$isActivePickup">
                            送迎
                        </x-nav-link>
                    @endif

                    @if($staffMessagesHref)
                        <x-nav-link :href="$staffMessagesHref" :active="$isActiveStaffMessages">
                            メッセージ
                        </x-nav-link>
                    @endif

                    @if($isFamily && $familyAvailabilityHref)
                        <x-nav-link :href="$familyAvailabilityHref" :active="$isActiveFamilyAvailability">
                            送迎
                        </x-nav-link>
                    @endif

                    {{-- ✅ 今日の勤怠（打刻） --}}
                    @if($showTodayKintaiLink)
                        <x-nav-link :href="$todayKintaiHref" :active="request()->routeIs('staff.attendance.today')">
                            今日の勤怠<br>（打刻）
                        </x-nav-link>
                    @endif

                    {{-- ✅ 出退勤QR読み取り（スタッフ） --}}
                    @if($staffQrScanHref)
                        <x-nav-link :href="$staffQrScanHref" :active="request()->routeIs('staff.attendance.qr')">
                            出退勤<br>（読み取り）
                        </x-nav-link>
                    @endif

                    {{-- ✅ 児童QR読み取り（出席登録）adminのみ：PCにも表示 --}}
                    @if($childQrScanHref)
                        <x-nav-link :href="$childQrScanHref" :active="$isActiveChildScan">
                            児童QR<br>（読み取り）
                        </x-nav-link>
                    @endif

                    {{-- 勤怠管理ドロップダウン（PC） --}}
                    @auth
                        @if($canShowKintaiDropdown)
                            <x-dropdown align="left" width="w-80">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold leading-5 transition
                                                {{ $isActiveKintai
                                                        ? 'border-indigo-400 text-gray-900'
                                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                }}">
                                        <span>勤怠<br>管理</span>
                                        <svg class="ms-1 fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 0 011.414 0L10 10.586l3.293-3.293a1 0 111.414 1.414l-4 4a1 0 01-1.414 0l-4-4a1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    @if($canTodayAttendance && \Illuminate\Support\Facades\Route::has('staff.attendance.today'))
                                        <x-dropdown-link :href="route('staff.attendance.today')">
                                            🕒 今日の勤怠（打刻）
                                        </x-dropdown-link>
                                    @endif

                                    @if($canAttendanceQr && \Illuminate\Support\Facades\Route::has('staff.attendance.qr'))
                                        <x-dropdown-link :href="route('staff.attendance.qr')">
                                            📷 出退勤（読み取り）
                                        </x-dropdown-link>
                                    @endif

                                    @if(\Illuminate\Support\Facades\Route::has('staff.attendance.history'))
                                        <x-dropdown-link :href="route('staff.attendance.history')">
                                            📚 勤怠履歴
                                        </x-dropdown-link>
                                    @endif

                                    @if($canShiftDay && \Illuminate\Support\Facades\Route::has('admin.shifts.index'))
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <x-dropdown-link :href="route('admin.shifts.index')">
                                            🗓️ シフト（日別）
                                        </x-dropdown-link>
                                    @endif

                                    @if($canShiftMonth && \Illuminate\Support\Facades\Route::has('admin.shifts.month'))
                                        <x-dropdown-link :href="route('admin.shifts.month')">
                                            🗓️ シフト（月表示）
                                        </x-dropdown-link>
                                    @endif

                                    @if($canAttendanceMonth && \Illuminate\Support\Facades\Route::has('admin.attendances.index'))
                                        <x-dropdown-link :href="route('admin.attendances.index')">
                                            📊 勤怠（月次）
                                        </x-dropdown-link>
                                    @endif

                                    @if($canAuditLogs && \Illuminate\Support\Facades\Route::has('admin.attendance_logs.index'))
                                        <x-dropdown-link :href="route('admin.attendance_logs.index')">
                                            🧾 監査ログ
                                        </x-dropdown-link>
                                    @endif

                                    @if($canClosings && \Illuminate\Support\Facades\Route::has('admin.closings.index'))
                                        <x-dropdown-link :href="route('admin.closings.index')">
                                            🔒 月次締め
                                        </x-dropdown-link>
                                    @endif

                                    @if($isAdminRole && \Illuminate\Support\Facades\Route::has('admin.payroll.index'))
                                        <x-dropdown-link :href="route('admin.payroll.index')">
                                            💴 従業員給与一覧
                                        </x-dropdown-link>
                                    @endif

                                    @if($isAdminRole && \Illuminate\Support\Facades\Route::has('admin.payroll.withholding.index'))
                                        <x-dropdown-link :href="route('admin.payroll.withholding.index', ['year' => now()->format('Y')])">
                                            📥 源泉税テーブル取込
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        @endif
                    @endauth

                    {{-- 運営管理（PCのみ） --}}
                    @auth
                        @if($canOpsMenu || (($role ?? '') === 'admin' && \Illuminate\Support\Facades\Route::has('admin.permissions.index')))
                            <x-dropdown align="left" width="72">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold leading-5 transition
                                                {{ $isActiveOpsMenu
                                                        ? 'border-indigo-400 text-gray-900'
                                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                }}">
                                        <span>運営<br>管理</span>
                                        <svg class="ms-1 fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 0 011.414 0L10 10.586l3.293-3.293a1 0 111.414 1.414l-4 4a1 0 01-1.414 0l-4-4a1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-72">
                                        {{-- ✅ 参加予定（送迎） --}}
                                        {{-- PCはBlade版を維持（必要ならここもReactへ差し替え可能） --}}
                                        @if($canAttendanceIntents && \Illuminate\Support\Facades\Route::has('admin.attendance_intents.index'))
                                            <x-dropdown-link :href="route('admin.attendance_intents.index')">
                                                🚗 参加予定（送迎）
                                            </x-dropdown-link>
                                        @endif

                                        {{-- ✅ 児童QR読み取り（出席登録） --}}
                                        @if($canChildQrScan && \Illuminate\Support\Facades\Route::has('admin.attendance.scan'))
                                            <x-dropdown-link :href="route('admin.attendance.scan')">
                                                📷 児童QR（読み取り）
                                            </x-dropdown-link>
                                        @endif

                                        @if($canAttendanceQr && \Illuminate\Support\Facades\Route::has('staff.attendance.qr'))
                                            <x-dropdown-link :href="route('staff.attendance.qr')">
                                                🧑‍🏫 出退勤（読み取り）
                                            </x-dropdown-link>
                                        @endif
                                    </div>

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <div class="px-4 py-2 text-xs font-bold text-gray-500">マスタ</div>
                                    @if($canSchools && \Illuminate\Support\Facades\Route::has('admin.schools.index'))
                                        <x-dropdown-link :href="route('admin.schools.index')">
                                            🏫 学校マスタ
                                        </x-dropdown-link>
                                    @endif
                                    @if($canBases && \Illuminate\Support\Facades\Route::has('admin.bases.index'))
                                        <x-dropdown-link :href="route('admin.bases.index')">
                                            📍 拠点マスタ
                                        </x-dropdown-link>
                                    @endif

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <div class="px-4 py-2 text-xs font-bold text-gray-500">管理</div>
                                    @if($canChildren && \Illuminate\Support\Facades\Route::has('admin.children.index'))
                                        <x-dropdown-link :href="route('admin.children.index')">
                                            🧒 児童管理（一覧）
                                        </x-dropdown-link>
                                    @endif
                                    @if($canChildren && \Illuminate\Support\Facades\Route::has('admin.children.today'))
                                        <x-dropdown-link :href="route('admin.children.today')">
                                            📋 当日の参加者
                                        </x-dropdown-link>
                                    @endif
                                    @if($canGuardians && \Illuminate\Support\Facades\Route::has('admin.guardians.index'))
                                        <x-dropdown-link :href="route('admin.guardians.index')">
                                            👪 保護者管理
                                        </x-dropdown-link>
                                    @endif
                                    @if($canAdminUsers && \Illuminate\Support\Facades\Route::has('admin.users.index'))
                                        <x-dropdown-link :href="route('admin.users.index')">
                                            🛡️ 管理者管理
                                        </x-dropdown-link>
                                    @endif
                                        @if(($role ?? '') === 'admin' && \Illuminate\Support\Facades\Route::has('admin.permissions.index'))
                                            <x-dropdown-link :href="route('admin.permissions.index')">
                                                ⚙️ 権限設定
                                            </x-dropdown-link>
                                        @endif
                                </x-slot>
                            </x-dropdown>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- スマホ：ハンバーガー（右端） -->
            @if((Auth::check() || $isFamily) && !$showAdminMobileSidebar)
                <button
                    type="button"
                    class="sm:hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 text-gray-600 hover:text-gray-800 hover:border-gray-300 transition"
                    aria-label="メニュー"
                    @click="mobileMenu = !mobileMenu"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            @endif

            <!-- 右側：ユーザーDropdown（PCのみ） -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if($isFamily)
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-gray-700 bg-yellow-50 hover:bg-yellow-100 shadow-sm transition">
                                <div class="flex items-center gap-2">
                                    <span aria-hidden="true">🧒</span>
                                    <span>児童メニュー</span>
                                </div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 0 011.414 0L10 10.586l3.293-3.293a1 0 111.414 1.414l-4 4a1 0 01-1.414 0l-4-4a1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if($familyProfileHref)
                                <x-dropdown-link :href="$familyProfileHref"> 🛠️ 登録内容の変更 </x-dropdown-link>
                            @endif
                            @if($familySiblingsHref)
                                <x-dropdown-link :href="$familySiblingsHref"> 👨‍👩‍👧‍👦 きょうだい登録 </x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('family.logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('family.logout')"
                                                 onclick="event.preventDefault(); this.closest('form').submit();">
                                    🚪 ログアウト
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}"
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none transition ease-in-out duration-150">
                            {{ Auth::user()->name ?? 'ユーザー' }}
                        </a>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center justify-center w-9 h-9 border border-gray-200 rounded-full text-gray-500 bg-white hover:text-gray-700 hover:border-gray-300 transition">
                                    <img src="{{ asset('images/info.png') }}" alt="メニュー" class="w-5 h-5 object-contain">
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')"> 登録情報の変更 </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                                     onclick="event.preventDefault(); this.closest('form').submit();">
                                        ログアウト
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- スマホメニュー（上部） -->
    @if($showAdminMobileSidebar)
        <div
            x-show="mobileMenu"
            class="sm:hidden fixed inset-0 z-[70]"
            style="display: none;"
        >
            <div
                x-show="mobileMenu"
                x-transition.opacity
                class="absolute inset-0 bg-slate-950/45 backdrop-blur-[1px]"
                @click="mobileMenu = false"
            ></div>

            <aside
                x-show="mobileMenu"
                x-transition:enter="transform transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="absolute inset-y-0 left-0 flex w-[86vw] max-w-sm flex-col overflow-hidden rounded-r-[2rem] bg-white shadow-2xl"
            >
                <div class="border-b border-slate-200 bg-slate-950 px-5 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-300">Admin Menu</div>
                            <div class="mt-1 text-lg font-black leading-tight">管理メニュー</div>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white transition hover:bg-white/20"
                            aria-label="閉じる"
                            @click="mobileMenu = false"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 flex items-center gap-3 rounded-2xl bg-white/10 px-3 py-3">
                        <img src="{{ asset('images/ver2.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto rounded-xl bg-white p-1.5">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold">{{ Auth::user()->name ?? '管理者' }}</div>
                            <div class="text-xs text-slate-300">スマホ用ショートカット</div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto bg-slate-50 px-3 py-3">
                    <div class="space-y-3">
                        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <button type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                                    @click="toggleSection('general')">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">General</div>
                                    <div class="text-sm font-black text-slate-900">おしらせ / 基本導線</div>
                                </div>
                                <svg class="h-5 w-5 text-slate-500 transition" :class="sections.general ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="sections.general" x-transition.opacity.duration.150ms class="border-t border-slate-100 px-2 py-2">
                                <div class="space-y-1">
                                    <a href="{{ $noticeHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveNotice ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">おしらせ</a>
                                    @if($myQrHref)
                                        <a href="{{ $myQrHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveMyQr ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-100' }}">マイQR</a>
                                    @endif
                                    @if($pickupHref)
                                        <a href="{{ $pickupHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActivePickup ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">参加予定（送迎）</a>
                                    @endif
                                    @if($staffMessagesHref)
                                        <a href="{{ $staffMessagesHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveStaffMessages ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-100' }}">メッセージ</a>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <button type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                                    @click="toggleSection('kintai')">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Work</div>
                                    <div class="text-sm font-black text-slate-900">勤怠管理</div>
                                </div>
                                <svg class="h-5 w-5 text-slate-500 transition" :class="sections.kintai ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="sections.kintai" x-transition.opacity.duration.150ms class="border-t border-slate-100 px-2 py-2">
                                <div class="space-y-1">
                                    @if($todayKintaiHref)
                                        <a href="{{ $todayKintaiHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('staff.attendance.today') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">今日の勤怠（打刻）</a>
                                    @endif
                                    @if($staffQrScanHref)
                                        <a href="{{ $staffQrScanHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('staff.attendance.qr') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">出退勤（読み取り）</a>
                                    @endif
                                    @if($attendanceHistoryHref)
                                        <a href="{{ $attendanceHistoryHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('staff.attendance.history') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">勤怠履歴</a>
                                    @endif
                                    @if($canShiftDay && \Illuminate\Support\Facades\Route::has('admin.shifts.index'))
                                        <a href="{{ route('admin.shifts.index') }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.shifts.index') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">シフト（日別）</a>
                                    @endif
                                    @if($canShiftMonth && \Illuminate\Support\Facades\Route::has('admin.shifts.month'))
                                        <a href="{{ route('admin.shifts.month') }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.shifts.month') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">シフト（月表示）</a>
                                    @endif
                                    @if($shiftCreateHref)
                                        <a href="{{ $shiftCreateHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveShiftCreate ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-100' }}">シフト登録</a>
                                    @endif
                                    @if($attendanceMonthHref)
                                        <a href="{{ $attendanceMonthHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.attendances.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">勤怠（月次）</a>
                                    @endif
                                    @if($auditLogsHref)
                                        <a href="{{ $auditLogsHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.attendance_logs.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">監査ログ</a>
                                    @endif
                                    @if($closingsHref)
                                        <a href="{{ $closingsHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.closings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">月次締め</a>
                                    @endif
                                    @if($payrollHref)
                                        <a href="{{ $payrollHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.payroll.index') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">従業員給与一覧</a>
                                    @endif
                                    @if($withholdingHref)
                                        <a href="{{ $withholdingHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.payroll.withholding.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">源泉税テーブル取込</a>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <button type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                                    @click="toggleSection('ops')">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Ops</div>
                                    <div class="text-sm font-black text-slate-900">運営管理</div>
                                </div>
                                <svg class="h-5 w-5 text-slate-500 transition" :class="sections.ops ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="sections.ops" x-transition.opacity.duration.150ms class="border-t border-slate-100 px-2 py-2">
                                <div class="space-y-1">
                                    @if($pickupHref)
                                        <a href="{{ $pickupHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActivePickup ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">参加予定（送迎）</a>
                                    @endif
                                    @if($childQrScanHref)
                                        <a href="{{ $childQrScanHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveChildScan ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">児童QR（読み取り）</a>
                                    @endif
                                    @if($childrenTodayHref)
                                        <a href="{{ $childrenTodayHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.children.today') || request()->routeIs('admin.children.today.react') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">当日の参加者</a>
                                    @endif
                                    @if($schoolsHref)
                                        <a href="{{ $schoolsHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.schools.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">学校マスタ</a>
                                    @endif
                                    @if($basesHref)
                                        <a href="{{ $basesHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.bases.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">拠点マスタ</a>
                                    @endif
                                    @if($childrenIndexHref)
                                        <a href="{{ $childrenIndexHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.children.index') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">児童管理（一覧）</a>
                                    @endif
                                    @if($guardiansHref)
                                        <a href="{{ $guardiansHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.guardians.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">保護者管理</a>
                                    @endif
                                    @if($adminUsersHref)
                                        <a href="{{ $adminUsersHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">管理者管理</a>
                                    @endif
                                    @if($permissionsHref)
                                        <a href="{{ $permissionsHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('admin.permissions.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">権限設定</a>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <button type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                                    @click="toggleSection('account')">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Account</div>
                                    <div class="text-sm font-black text-slate-900">アカウント</div>
                                </div>
                                <svg class="h-5 w-5 text-slate-500 transition" :class="sections.account ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="sections.account" x-transition.opacity.duration.150ms class="border-t border-slate-100 px-2 py-2">
                                <div class="space-y-1">
                                    <a href="{{ $profileHref }}" class="block rounded-2xl px-3 py-3 text-sm font-semibold {{ $isActiveAccount ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100' }}">登録情報の変更</a>
                                    <form method="POST" action="{{ $logoutAction }}">
                                        @csrf
                                        <button type="submit" class="block w-full rounded-2xl px-3 py-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">ログアウト</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </aside>
        </div>
    @elseif(Auth::check() || $isFamily)
        <div
            x-show="mobileMenu"
            x-transition
            @click.outside="mobileMenu = false"
            class="sm:hidden border-t border-gray-100 bg-white"
            style="display: none;"
        >
            <div class="px-4 py-3 space-y-3">
                <a href="{{ $isFamily ? ($familyProfileHref ?: '#') : route('profile.edit') }}"
                   class="block text-sm font-semibold {{ $isActiveFamilyProfile ? 'text-emerald-700' : 'text-gray-700 hover:text-gray-900' }}">
                    {{ $isFamily ? '情報変更' : '登録情報の変更' }}
                </a>

                @if($isFamily && $familySiblingsHref)
                    <a href="{{ $familySiblingsHref }}"
                       class="block text-sm font-semibold {{ $isActiveFamilySiblings ? 'text-emerald-700' : 'text-gray-700 hover:text-gray-900' }}">
                        きょうだい登録
                    </a>
                @endif

                <form method="POST" action="{{ $logoutAction }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left text-sm font-semibold text-gray-700 hover:text-gray-900">
                        ログアウト
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- ✅ スマホ下固定メニューバー --}}
    <div class="sm:hidden fixed bottom-0 inset-x-0 z-50">
        <div class="bg-white/95 backdrop-blur border-t border-gray-200 shadow-[0_-6px_20px_rgba(0,0,0,0.08)]">
            <div class="max-w-7xl mx-auto px-2">
                <div class="flex items-stretch justify-between gap-1 py-1">

                    {{-- おしらせ --}}
                    <a href="{{ $noticeHref }}"
                       class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                              {{ $isActiveNotice ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600' }}">
                        <div class="text-xl leading-none">📣</div>
                        <div class="text-[9px] font-semibold leading-none">おしらせ</div>
                    </a>

                    {{-- マイQR（本人用） --}}
                    @if($myQrHref)
                        <a href="{{ $myQrHref }}"
                           class="relative flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                  {{ $isActiveMyQr ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                            <div class="text-xl leading-none">🔳</div>
                            <div class="text-[9px] font-semibold leading-none">マイQR</div>
                            @if($isFamily)
                                <span id="family-myqr-status"
                                      class="absolute -top-0.5 right-3 inline-flex items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] font-bold text-white shadow hidden">
                                    参加中
                                </span>
                            @endif
                        </a>
                    @else
                        <div class="flex-1"></div>
                    @endif

                    {{-- ✅ A案：児童画面のときは「参加よてい」を表示（勤怠の位置を置き換え） --}}
                    @if($isFamily)
                        @if($familyAvailabilityHref)
                            <a href="{{ $familyAvailabilityHref }}"
                               class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $isActiveFamilyAvailability ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600' }}">
                                <div class="text-xl leading-none">📅</div>
                                <div class="text-[9px] font-semibold leading-none">送迎</div>
                            </a>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @else
                        {{-- 勤怠 --}}
                        @if($kintaiHref)
                            <a href="{{ $kintaiHref }}"
                               class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $isActiveKintai ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600' }}">
                                <div class="text-xl leading-none">🕒</div>
                                <div class="text-[9px] font-semibold leading-none">勤怠</div>
                            </a>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @endif

                    {{-- シフト登録 --}}
                    @if(!$isFamily)
                        @if($shiftCreateHref)
                            <a href="{{ $shiftCreateHref }}"
                               class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $isActiveShiftCreate ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                                <img src="{{ asset('images/icons8.png') }}"
                                     alt="シフト登録"
                                     class="h-5 w-5 object-contain">
                                <div class="text-[9px] font-semibold leading-none">シフト</div>
                            </a>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @endif

                    {{-- ✅ 4つ目：adminのみ（児童画面は空枠にする） --}}
                    @if($isFamily)
                        @if($familyMessagesHref)
                            <a href="{{ $familyMessagesHref }}"
                               class="relative flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $isActiveFamilyMessages ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                                <div class="text-xl leading-none">💬</div>
                                <div class="text-[9px] font-semibold leading-none">メッセージ</div>
                                <span id="family-message-badge"
                                      data-count="{{ $familyUnreadCount ?? 0 }}"
                                      class="absolute top-0.5 right-4 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white {{ ($familyUnreadCount ?? 0) > 0 ? '' : 'hidden' }}">
                                    1
                                </span>
                            </a>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @else
                        @if($staffMessagesHref)
                            @php
                                $adminStaffUnreadMessageCount = (int)($adminStaffUnreadMessageCount ?? 0);
                                $adminStaffUnreadMessageBadge = $adminStaffUnreadMessageCount > 99 ? '99+' : (string)$adminStaffUnreadMessageCount;
                            @endphp
                            <a href="{{ $staffMessagesHref }}"
                               class="relative flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $isActiveStaffMessages ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                                <div class="text-xl leading-none">💬</div>
                                <div class="text-[9px] font-semibold leading-none">メッセージ</div>
                                <span id="admin-message-badge"
                                      data-count="{{ $adminStaffUnreadMessageCount }}"
                                      class="absolute top-0.5 right-4 inline-flex h-4 min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white shadow {{ $adminStaffUnreadMessageCount > 0 ? '' : 'hidden' }}">
                                    {{ $adminStaffUnreadMessageBadge }}
                                </span>
                            </a>
                        @elseif($adminFourthHref)
                            <a href="{{ $adminFourthHref }}"
                               class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                      {{ $adminFourthActive ? 'bg-yellow-50 text-yellow-800' : 'text-gray-600' }}">
                                <div class="text-xl leading-none">{{ $adminFourthIcon }}</div>
                                <div class="text-[9px] font-semibold leading-none">{{ $adminFourthLabel }}</div>
                            </a>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @endif

                    {{-- 保護者：ログアウト（下部メニューに復活） --}}
                    @if($isFamily)
                        <form method="POST" action="{{ $logoutAction }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-gray-600 hover:bg-gray-50">
                                <div class="text-xl leading-none">🚪</div>
                                <div class="text-[9px] font-semibold leading-none">ログアウト</div>
                            </button>
                        </form>
                    @elseif($pickupForMobile)
                        {{-- 管理者：送迎アイコン --}}
                        <a href="{{ $pickupForMobile }}"
                           class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                  {{ $isActivePickup ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600' }}">
                            <div class="text-xl leading-none">🚗</div>
                            <div class="text-[9px] font-semibold leading-none">送迎</div>
                        </a>
                    @elseif($staffMessagesHref && $adminFourthHref && $adminFourthHref !== $staffMessagesHref)
                        <a href="{{ $adminFourthHref }}"
                           class="flex-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1
                                  {{ $adminFourthActive ? 'bg-yellow-50 text-yellow-800' : 'text-gray-600' }}">
                            <div class="text-xl leading-none">{{ $adminFourthIcon }}</div>
                            <div class="text-[9px] font-semibold leading-none">{{ $adminFourthLabel }}</div>
                        </a>
                    @else
                        <div class="flex-1"></div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ✅ スマホの下余白は撤廃（要望） --}}
</nav>

{{-- ✅ 上部固定にした分、本文が潜らないようにスペーサー --}}
<div id="nav-spacer" class="h-12"></div>

@php
    $adminUnreadCountEndpoint = null;
    if (($isAdminOrStaffRole ?? false) && \Illuminate\Support\Facades\Route::has('admin.messages.unread_count')) {
        $adminUnreadCountEndpoint = route('admin.messages.unread_count');
    }

    $familyUnreadCountEndpoint = null;
    if (($isFamily ?? false) && \Illuminate\Support\Facades\Route::has('family.messages.unread_count')) {
        $familyUnreadCountEndpoint = route('family.messages.unread_count');
    }

    $familyMyQrStatusEndpoint = null;
    if (($isFamily ?? false) && \Illuminate\Support\Facades\Route::has('family.child.qr.status')) {
        $familyMyQrStatusEndpoint = route('family.child.qr.status');
    }
@endphp

@if($adminUnreadCountEndpoint && $isAdminRole)
    <div id="admin-unread-toast"
         class="hidden fixed top-16 right-6 z-50 rounded-xl bg-red-600 px-4 py-3 text-white shadow-lg flex items-center gap-3">
        <div class="text-sm font-semibold">保護者から未読メッセージがあります</div>
        <div id="admin-unread-toast-count"
             class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">
            0
        </div>
        <a href="{{ \Illuminate\Support\Facades\Route::has('admin.chats.index') ? route('admin.chats.index', ['unread_only' => 1]) : route('admin.children.index') }}"
           class="text-xs underline text-white/90 hover:text-white">
            チャット一覧へ
        </a>
    </div>
@endif

@if($adminUnreadCountEndpoint)
    <script>
        (function () {
            const endpoint = @json($adminUnreadCountEndpoint);
            if (!endpoint) return;

            const badge = document.getElementById('admin-message-badge');
            const toast = document.getElementById('admin-unread-toast');
            const countEl = document.getElementById('admin-unread-toast-count');
            const POLL_INTERVAL_MS = 30000;

            const applyBadge = (count) => {
                if (!badge) return;

                badge.dataset.count = String(count);
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            };

            const applyToast = (count) => {
                if (!toast || !countEl) return;

                if (count > 0) {
                    countEl.textContent = count > 99 ? '99+' : String(count);
                    toast.classList.remove('hidden');
                } else {
                    toast.classList.add('hidden');
                }
            };

            const apply = (count) => {
                applyBadge(count);
                applyToast(count);
            };

            const fetchCount = async () => {
                try {
                    const res = await fetch(endpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    apply(Number(data?.count || 0));
                } catch (e) {
                    // no-op
                }
            };

            apply(Number(badge?.dataset.count || 0));
            fetchCount();
            setInterval(fetchCount, POLL_INTERVAL_MS);
        })();
    </script>
@endif

@if($familyUnreadCountEndpoint)
    <script>
        (function () {
            const endpoint = @json($familyUnreadCountEndpoint);
            const badge = document.getElementById('family-message-badge');
            if (!endpoint || !badge) return;

            const POLL_INTERVAL_MS = 10000;
            const isMobile = (() => {
                const ua = navigator.userAgent || '';
                if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) return true;
                return /Mobi|Android|iPhone|iPad/i.test(ua);
            })();

            let lastCount = Number(badge.dataset.count || 0);
            let hasFetched = false;

            const apply = (count) => {
                badge.dataset.count = String(count);
                if (count > 0) {
                    badge.textContent = '1';
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            };

            const maybeVibrate = (count) => {
                if (!isMobile) return;
                if (typeof navigator.vibrate !== 'function') return;
                if (!hasFetched) return;
                if (count <= lastCount) return;
                navigator.vibrate(80);
            };

            const fetchCount = async () => {
                try {
                    const res = await fetch(endpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const count = Number(data?.count || 0);
                    maybeVibrate(count);
                    lastCount = count;
                    hasFetched = true;
                    apply(count);
                } catch (e) {
                    // no-op
                }
            };

            apply(lastCount);
            fetchCount();
            setInterval(fetchCount, POLL_INTERVAL_MS);
        })();
    </script>
@endif

@if($familyMyQrStatusEndpoint)
    <script>
        (function () {
            const endpoint = @json($familyMyQrStatusEndpoint);
            const badge = document.getElementById('family-myqr-status');
            if (!endpoint || !badge) return;

            const POLL_INTERVAL_MS = 8000;

            const apply = (state) => {
                if (!state) {
                    badge.classList.add('hidden');
                    return;
                }
                badge.textContent = state;
                badge.classList.remove('hidden');

                if (state === '参加中') {
                    badge.classList.remove('bg-amber-500');
                    badge.classList.add('bg-rose-500');
                } else {
                    badge.classList.remove('bg-rose-500');
                    badge.classList.add('bg-amber-500');
                }
            };

            const fetchStatus = async () => {
                try {
                    const res = await fetch(endpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const state = String(data?.state || '');
                    if (state === 'pickup') {
                        apply('送迎中');
                    } else if (state === 'attending') {
                        apply('参加中');
                    } else {
                        apply('');
                    }
                } catch (e) {
                    // no-op
                }
            };

            fetchStatus();
            setInterval(fetchStatus, POLL_INTERVAL_MS);
        })();
    </script>
@endif
