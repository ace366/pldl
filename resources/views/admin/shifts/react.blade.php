@php
    $role = Auth::user()->role ?? '';
    $isAdmin = ($role === 'admin');

    $bases = ($bases ?? collect())->map(fn ($base) => [
        'id' => (int)$base->id,
        'name' => $base->name ?? ('拠点#'.$base->id),
    ])->values()->all();

    $staffUsers = ($staffUsers ?? collect())->map(function ($user) {
        $name = ($user->name ?? '');
        if ($name === '') {
            $name = trim(($user->last_name ?? '').' '.($user->first_name ?? ''));
        }
        return [
            'id' => (int)$user->id,
            'name' => $name !== '' ? $name : ('User#'.$user->id),
        ];
    })->values()->all();

    $bulkPreview = session('shift_bulk_preview', []);

    $props = [
        'csrf' => csrf_token(),
        'isAdmin' => $isAdmin,
        'displayName' => Auth::user()?->name ?? 'ユーザー',
        'bases' => $bases,
        'staffUsers' => $staffUsers,
        'errors' => $errors->all(),
        'preview' => [
            'targetDates' => $bulkPreview['target_dates'] ?? [],
            'newDates' => $bulkPreview['new_dates'] ?? [],
            'duplicateDates' => $bulkPreview['duplicate_dates'] ?? [],
            'blocked' => $bulkPreview['blocked'] ?? [],
            'excludedWeekends' => $bulkPreview['excluded_weekends'] ?? [],
            'excludedHolidays' => $bulkPreview['excluded_holidays'] ?? [],
            'excludedByPattern' => $bulkPreview['excluded_by_pattern'] ?? [],
        ],
        'initialForm' => [
            'entryMode' => old('entry_mode', request()->query('entry_mode', 'single')),
            'baseId' => (int)old('base_id', $baseId ?? request()->query('base_id', 0)),
            'userId' => (int)old('user_id', request()->query('user_id', Auth::id())),
            'shiftDate' => old('shift_date', $date ?? request()->query('date', now()->toDateString())),
            'startTime' => old('start_time', request()->query('start_time', '14:00')),
            'endTime' => old('end_time', request()->query('end_time', '18:00')),
            'note' => old('note', request()->query('note', '')),
            'bulkStartDate' => old('bulk_start_date', request()->query('bulk_start_date', $date ?? now()->toDateString())),
            'bulkEndDate' => old('bulk_end_date', request()->query('bulk_end_date', $date ?? now()->toDateString())),
            'bulkPattern' => old('bulk_pattern', request()->query('bulk_pattern', 'daily')),
            'bulkWeekdays' => collect((array)old('bulk_weekdays', request()->query('bulk_weekdays', [1, 2, 3, 4, 5])))
                ->map(fn ($day) => (int)$day)
                ->filter(fn ($day) => $day >= 1 && $day <= 5)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'confirmOverwrite' => old('confirm_overwrite', '0') === '1',
        ],
        'urls' => [
            'store' => route('admin.shifts.store'),
            'index' => route('admin.shifts.index', ['base_id' => $baseId ?? 0, 'date' => $date ?? now()->toDateString()]),
            'create' => route('admin.shifts.create', ['base_id' => $baseId ?? 0, 'date' => $date ?? now()->toDateString()]),
            'react' => route('admin.shifts.create.react'),
        ],
        'assets' => [
            'shiftIcon' => asset('images/icons8.png'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="py-4 sm:py-6">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
            <div
                id="react-admin-shift-create"
                class="min-h-[60vh]"
            ></div>
            <script id="admin-shift-create-props" type="application/json">@json($props)</script>
        </div>
    </div>
</x-app-layout>
