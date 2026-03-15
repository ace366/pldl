@php
    $canEdit = \App\Services\RolePermissionService::canUser(auth()->user(), 'attendance_intents', 'update');
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl p-4 sm:p-6">

                <div class="flex items-center justify-between gap-3 mb-4">
                    <h1 class="text-lg font-semibold">
                        参加予定・送迎管理
                    </h1>

                    <a href="{{ route('admin.attendance_intents.index', ['date' => $date]) }}"
                       class="text-sm px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">
                        PC版へ
                    </a>
                </div>

                {{-- React Mount --}}
                <div
                    id="react-admin-attendance-intents"
                    data-date="{{ $date }}"
                    data-api-summary="{{ $apiSummaryUrl }}"
                    data-api-toggle-pickup="{{ $canEdit ? $apiTogglePickupUrl : '' }}"
                    data-api-toggle-manual="{{ $canEdit ? $apiToggleManualUrl : '' }}"
                    data-csrf="{{ csrf_token() }}"
                    data-car="{{ asset('images/car.png') }}"
                    data-ccar="{{ asset('images/ccar.png') }}"
                    data-can-edit="{{ $canEdit ? '1' : '0' }}"
                ></div>

            </div>
        </div>
    </div>
</x-app-layout>
