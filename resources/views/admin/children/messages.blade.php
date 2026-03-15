<x-app-layout>
    <style>
        #nav-spacer { height: 0 !important; }
        @media (max-width: 639px) {
            #nav-spacer { height: 8px !important; }
        }
        @media (min-width: 640px) {
            #nav-spacer { height: 48px !important; }
        }
        /* メッセージエリアの位置調整 */
        /* React側のクラスが更新されなくても効くように、既存DOMを直接指定 */
        #react-admin-child-messages div[style*="radial-gradient"] {
            padding-top: 0 !important;
            margin-top: 10px !important;
        }
        /* スマホ: 入力BOXを少し上げる */
        @media (max-width: 639px) {
            #react-admin-child-messages > div > div:last-child {
                bottom: 24px !important;
            }
        }
        @media (min-width: 640px) {
            #react-admin-child-messages .overflow-y-auto.pr-1 {
                padding-top: 10px !important;
                margin-top: 42px !important;
                transform: none !important;
                height: calc(100vh - 320px) !important;
                overflow-y: auto !important;
                padding-right: -4 !important;
            }
        }
        /* PC: 入力BOXを画面下に固定 */
        @media (min-width: 640px) {
            #react-admin-child-messages > div > div:last-child {
                position: fixed !important;
                bottom: 12px !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 40 !important;
            }
        }
    </style>
    <div>
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div id="react-admin-child-messages" data-mounted="0"></div>
                <script id="admin-child-messages-props" type="application/json">
                    @json($adminProps ?? [])
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
