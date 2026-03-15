<x-app-layout>
    @php
        $linkify = function ($text) {
            $escaped = e($text ?? '');
            return preg_replace_callback('/(https?:\/\/[^\s<]+)/', function ($m) {
                $url = $m[1];
                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                return '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 underline break-all">' . $safeUrl . '</a>';
            }, $escaped);
        };
    @endphp
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">過去のメッセージ</h1>
                    <p class="text-sm text-gray-600">※「既読」にしても、ここからいつでも見返せます。</p>
                </div>

                <a href="{{ route('family.home') }}"
                   class="text-sm text-gray-600 hover:text-gray-900 underline">
                    戻る
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-5 space-y-3">
                    @forelse($messages as $m)
                        @php($isRead = isset($readSet[$m->id]))
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-800">
                                        {{ $m->title ?: '（タイトルなし）' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ optional($m->created_at)->format('Y-m-d H:i') }}
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if($isRead)
                                        <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                            既読
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('family.messages.read', $m) }}">
                                            @csrf
                                            <button class="px-3 py-1 rounded bg-indigo-600 text-black text-xs font-semibold hover:bg-indigo-700">
                                                既読にする
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 text-sm text-gray-800 whitespace-pre-wrap">{!! nl2br($linkify($m->body)) !!}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">メッセージはありません。</div>
                    @endforelse

                    <div class="mt-4">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
