<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">保護者チャット</h1>
                        <p class="mt-1 text-sm text-gray-600">保護者とのメッセージ一覧です。</p>
                    </div>
                </div>

                @php
                    $q = (string)($filters['q'] ?? '');
                    $unreadOnly = (bool)($filters['unread_only'] ?? false);
                    $status = (string)($filters['status'] ?? '');
                @endphp

                <form method="GET" action="{{ route('admin.chats.index') }}" class="mt-4 rounded-xl border border-gray-200 bg-sky-50 p-3 sm:p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="chat-q" class="block text-xs font-semibold text-gray-700">検索</label>
                            <input id="chat-q"
                                   type="text"
                                   name="q"
                                   value="{{ $q }}"
                                   placeholder="保護者名 / 児童名"
                                   class="mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label for="chat-status" class="block text-xs font-semibold text-gray-700">状態</label>
                            <select id="chat-status"
                                    name="status"
                                    class="mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">すべて</option>
                                <option value="open" @selected($status === 'open')>open</option>
                                <option value="closed" @selected($status === 'closed')>closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="unread_only" value="1" @checked($unreadOnly) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                            未読のみ
                        </label>
                        <button type="submit"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                            絞り込む
                        </button>
                        <a href="{{ route('admin.chats.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            クリア
                        </a>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse($threads as $thread)
                        @php
                            $guardian = $thread->guardian;
                            $latest = $thread->latestMessage;
                            $children = $guardian?->children ?? collect();
                            $childrenSummary = $children->take(2)->map(fn($c) => $c->full_name)->filter()->implode(' / ');
                            if ($children->count() > 2) {
                                $childrenSummary .= ' ほか'.($children->count() - 2).'名';
                            }
                        @endphp
                        <a href="{{ route('admin.chats.show', ['thread' => (int)$thread->id]) }}"
                           class="block rounded-xl border border-gray-200 bg-white p-4 hover:bg-sky-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h2 class="truncate text-base font-semibold text-gray-900">
                                            {{ $guardian?->full_name ?: ('保護者 #'.(int)$thread->guardian_id) }}
                                        </h2>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $thread->status === 'closed' ? 'bg-gray-200 text-gray-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $thread->status }}
                                        </span>
                                        @if((int)($thread->unread_count_staff ?? 0) > 0)
                                            <span class="inline-flex min-w-[22px] items-center justify-center rounded-full bg-rose-600 px-2 py-0.5 text-xs font-bold text-white">
                                                {{ (int)$thread->unread_count_staff }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 truncate text-sm text-gray-600">
                                        {{ $latest ? \Illuminate\Support\Str::limit($latest->body, 80) : 'まだメッセージはありません。' }}
                                    </p>
                                    @if($childrenSummary !== '')
                                        <p class="mt-1 truncate text-xs text-gray-500">児童: {{ $childrenSummary }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 text-xs text-gray-500">
                                    {{ optional($thread->last_message_at ?? $thread->updated_at)->format('Y-m-d H:i') }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-600">
                            まだチャットはありません。保護者からメッセージが届くと表示されます。
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $threads->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
