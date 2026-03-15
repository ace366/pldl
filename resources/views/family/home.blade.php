<x-app-layout>
    <style>
        #nav-spacer { height: 0 !important; }
        /* メッセージ内容を少し下げる（Tailwind無効対策） */
        #react-family-home .overflow-y-auto.pr-1 {
            margin-top: 12px !important;
            padding-top: 30px !important;
            height: calc(100vh - 300px) !important;
            overflow-y: auto !important;
        }
        /* PC: 入力BOXを画面下に固定 */
        @media (min-width: 640px) {
            #react-family-home > div > div:last-child {
                position: fixed !important;
                bottom: 12px !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 40 !important;
            }
        }
    </style>
    <div class="pt-0 pb-6 sm:mt-0">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div id="react-family-home" data-mounted="0"></div>

                <script id="family-home-props" type="application/json">
                    @json($familyHomeProps)
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
