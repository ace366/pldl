<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl p-4 sm:p-6">
                <div id="react-family-siblings" data-mounted="0"></div>
                <script id="family-siblings-props" type="application/json">
                    @json($props)
                </script>
            </div>
        </div>
    </div>

    @vite(['resources/js/app.jsx'])
</x-app-layout>
