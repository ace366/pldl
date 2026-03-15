<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.chats.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">← 一覧へ戻る</a>
                        <h1 class="mt-1 text-lg sm:text-xl font-bold text-gray-900">
                            {{ $thread->guardian?->full_name ?: ('保護者 #'.(int)$thread->guardian_id) }}
                        </h1>
                        @php
                            $childrenSummary = ($thread->guardian?->children ?? collect())
                                ->map(function ($child) {
                                    $name = trim((string) ($child->full_name ?? $child->name ?? ''));
                                    if ($name === '') {
                                        $name = '児童 #'.(int) $child->id;
                                    }

                                    $code = trim((string) ($child->child_code ?? ''));
                                    return $code !== ''
                                        ? $name.'（ID: '.$code.'）'
                                        : $name.'（ID未設定）';
                                })
                                ->implode(' / ');
                        @endphp
                        @if($childrenSummary !== '')
                            <p class="mt-1 text-sm text-gray-600">児童: {{ $childrenSummary }}</p>
                        @endif
                        <p class="mt-1 text-sm text-gray-600">
                            @if(!empty($thread->guardian?->line_user_id))
                                LINE連携済み
                            @else
                                LINE未連携（返信はDB保存のみ）
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">
                            最終更新: {{ optional($thread->last_message_at ?? $thread->updated_at)->format('Y-m-d H:i') }}
                        </div>
                        <form method="POST" action="{{ route('admin.chats.status.update', ['thread' => (int)$thread->id]) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $thread->status === 'open' ? 'closed' : 'open' }}">
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-semibold {{ $thread->status === 'open' ? 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                                {{ $thread->status === 'open' ? 'このチャットを閉じる' : 'このチャットを再開する' }}
                            </button>
                        </form>
                    </div>
                </div>

                @if (session('success'))
                    <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('line_link_error'))
                    <div class="mt-4 rounded-md bg-rose-50 p-3 text-sm text-rose-700">
                        {{ session('line_link_error') }}
                    </div>
                @endif

                <div id="chat-messages"
                     data-fetch-url="{{ route('admin.chats.messages', ['thread' => (int)$thread->id]) }}"
                     data-last-id="{{ (int)($messages->last()->id ?? 0) }}"
                     class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-3 sm:p-4 h-[55vh] overflow-y-auto">
                    @forelse($messages as $message)
                        @php
                            $isStaff = $message->sender_type === 'staff';
                            $isSystem = $message->sender_type === 'system';
                        @endphp
                        <div class="mb-3 {{ $isStaff ? 'text-right' : ($isSystem ? 'text-center' : 'text-left') }}">
                            <div class="inline-block max-w-[85%] rounded-2xl px-4 py-2 text-sm leading-relaxed {{ $isStaff ? 'bg-sky-600 text-white' : ($isSystem ? 'bg-amber-100 text-amber-900' : 'bg-white text-gray-900 border border-gray-200') }}">
                                {{ $message->body }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                {{ optional($message->created_at)->format('Y-m-d H:i') }}
                                @if($isStaff && !empty($message->sender?->name))
                                    / {{ $message->sender->name }}
                                @endif
                                @if($isStaff)
                                    / {{ $message->delivery_status }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-center text-sm text-gray-600">
                            まだメッセージはありません。
                        </div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.chats.reply', ['thread' => (int)$thread->id]) }}" class="mt-4">
                    @csrf
                    <label for="reply-body" class="block text-sm font-semibold text-gray-700">返信内容</label>
                    <textarea id="reply-body"
                              name="body"
                              rows="4"
                              required
                              maxlength="5000"
                              class="mt-2 block w-full rounded-xl border-gray-300 focus:border-sky-500 focus:ring-sky-500"
                              placeholder="保護者への返信を入力してください。">{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror

                    <div class="mt-3 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-7 py-3 text-base font-semibold text-white hover:bg-sky-700">
                            返信を送信
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('chat-messages');
            if (!container) return;

            const fetchUrl = container.dataset.fetchUrl;
            let lastId = Number(container.dataset.lastId || 0);

            const scrollToBottom = () => {
                container.scrollTop = container.scrollHeight;
            };

            const appendMessage = (message) => {
                const row = document.createElement('div');
                const senderType = String(message.sender_type || '');
                const isStaff = senderType === 'staff';
                const isSystem = senderType === 'system';

                row.className = 'mb-3 ' + (isStaff ? 'text-right' : (isSystem ? 'text-center' : 'text-left'));

                const bubble = document.createElement('div');
                bubble.className = 'inline-block max-w-[85%] rounded-2xl px-4 py-2 text-sm leading-relaxed ' + (
                    isStaff
                        ? 'bg-sky-600 text-white'
                        : (isSystem ? 'bg-amber-100 text-amber-900' : 'bg-white text-gray-900 border border-gray-200')
                );
                bubble.textContent = String(message.body || '');

                const meta = document.createElement('div');
                meta.className = 'mt-1 text-[11px] text-gray-500';
                const parts = [String(message.sent_at || '')];
                if (isStaff && String(message.sender_name || '') !== '') {
                    parts.push(String(message.sender_name || ''));
                }
                if (isStaff) {
                    parts.push(String(message.delivery_status || ''));
                }
                meta.textContent = parts.filter(Boolean).join(' / ');

                row.appendChild(bubble);
                row.appendChild(meta);
                container.appendChild(row);
            };

            const poll = async () => {
                try {
                    const response = await fetch(fetchUrl + '?after_id=' + encodeURIComponent(String(lastId)), {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) return;
                    const data = await response.json();
                    const list = Array.isArray(data.messages) ? data.messages : [];
                    if (list.length === 0) return;

                    list.forEach((message) => {
                        appendMessage(message);
                        lastId = Math.max(lastId, Number(message.id || 0));
                    });
                    container.dataset.lastId = String(lastId);
                    scrollToBottom();
                } catch (error) {
                    // silent
                }
            };

            scrollToBottom();
            window.setInterval(poll, 5000);
        })();
    </script>
</x-app-layout>
