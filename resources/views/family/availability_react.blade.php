{{-- resources/views/family/availability_react.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl p-6">

                <div id="react-family-availability"></div>

                <script id="family-availability-props" type="application/json">
                    {!! json_encode([
                        'child' => [
                            'id' => (int)$child->id,
                            'full_name'  => $child->full_name,
                            'child_code' => $child->child_code,
                        ],
                        'siblings' => ($siblingTabs ?? collect())->values()->all(),
                        'calendar' => [
                            'selectedDates' => collect($intents)->keys()->values()->all(),
                            'today' => now()->toDateString(),

                            'gridStart' => $gridStart->toDateString(),
                            'gridEnd'   => $gridEnd->toDateString(),

                            'monthLabel' => $gridStart->format('Y年n月'),
                            'monthStart' => $gridStart->copy()->startOfMonth()->toDateString(),
                            'monthEnd'   => $gridStart->copy()->endOfMonth()->toDateString(),

                            'rangeLabel' => $gridStart->format('n/j') . ' 〜 ' . $gridEnd->format('n/j'),
                            'weekdays'   => ['日','月','火','水','木','金','土'],
                        ],
                        'routes' => [
                            'home'     => route('family.home'),
                            'toggle'   => route('family.availability.toggle'),
                            'bulk'     => route('family.availability.bulk_on'),
                            'indexNow' => route('family.availability.index', ['child_id' => (int)$child->id]),
                            'indexPrev'=> route('family.availability.index', ['child_id' => (int)$child->id, 'start' => $gridStart->copy()->subDays(28)->toDateString()]),
                            'indexNext'=> route('family.availability.index', ['child_id' => (int)$child->id, 'start' => $gridStart->copy()->addDays(28)->toDateString()]),
                        ],
                        // ✅ 追加：画像URLをLaravel側で確定させて渡す
                        'assets' => [
                            'attendanceIcon' => asset('images/attendance.png'),
                        ],
                        'csrf' => csrf_token(),
                    ], JSON_UNESCAPED_UNICODE) !!}
                </script>

            </div>
        </div>
    </div>

    {{-- ※あなたの環境は app.jsx がエントリなのでこっち --}}
    @vite(['resources/js/app.jsx'])
</x-app-layout>
