<x-app-layout>
    @php
        // route(..., false) だと本番のサブディレクトリ配備でベースパスが落ちるため、
        // 現在のリクエストルートを基準に保存・画像アップロード先を組み立てる。
        $requestRoot = rtrim(request()->root(), '/');
        $noticeUpdateUrl = $requestRoot.route('admin.notices.update', [], false);
        $noticeImageUploadUrl = $requestRoot.route('admin.notices.images.store', [], false);
    @endphp

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4">

            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                ✏️ メインお知らせ編集（管理者）
            </h1>

            @if (session('success'))
                <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <form id="noticeForm" method="POST" action="{{ $noticeUpdateUrl }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- タイトル --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        タイトル
                    </label>
                    <input type="text" name="title"
                           value="{{ old('title', $notice->title ?? '') }}"
                           class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- 本文 --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        本文（太字 / 色 / 下線 / 番号付き / 箇条書き / リンク / 画像 / YouTube 対応）
                    </label>
                    @php($initialBody = old('body', $notice->body ?? ''))

                    <div class="rounded-lg border border-gray-300 overflow-hidden">
                        <div class="flex flex-wrap items-center gap-2 border-b bg-gray-50 px-3 py-2">
                            <button type="button" data-editor-cmd="bold"
                                    class="px-2 py-1 rounded border bg-white text-sm font-bold hover:bg-gray-100"
                                    title="太字">B</button>
                            <button type="button" data-editor-cmd="underline"
                                    class="px-2 py-1 rounded border bg-white text-sm underline hover:bg-gray-100"
                                    title="下線">U</button>
                            <button type="button" data-editor-cmd="insertOrderedList"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="番号付きリスト">1.2.3.</button>
                            <button type="button" data-editor-cmd="insertUnorderedList"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="箇条書き">・</button>
                            <button type="button" id="editorLinkBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="リンクを挿入">リンク</button>
                            <button type="button" id="editorImageBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="画像を挿入">画像</button>
                            <button type="button" id="editorYoutubeBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="YouTubeを埋め込む">YouTube</button>
                            <button type="button" id="editorFontDownBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="文字を小さく">A-</button>
                            <button type="button" id="editorFontUpBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="文字を大きく">A+</button>

                            <div class="flex items-center gap-2 ml-2">
                                <label for="editorColor" class="text-xs text-gray-600">文字色</label>
                                <input id="editorColor" type="color" value="#111827"
                                       class="h-8 w-10 rounded border border-gray-300 bg-white p-1 cursor-pointer">
                            </div>
                            <input type="file" id="editorImageFile" class="hidden" accept="image/*">
                        </div>

                        <div id="bodyEditor"
                             contenteditable="true"
                             class="min-h-[220px] w-full p-3 text-gray-800 focus:outline-none"></div>
                    </div>

                    <textarea id="bodyInput" name="body" class="hidden">{{ $initialBody }}</textarea>
                    @error('body')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror

                    <div class="mt-2 text-xs text-gray-500">
                        ※ Enterで改行。貼り付け時は書式が簡略化されます。YouTubeはURLを入れると埋め込み再生されます。
                    </div>

                    <div class="mt-3 rounded-lg border border-dashed border-indigo-200 bg-indigo-50/40 p-3 text-sm text-gray-700">
                        <div class="font-semibold text-indigo-700 mb-2">プレビュー</div>
                        <div id="bodyPreview" class="notice-rich-body"></div>
                    </div>
                </div>

                {{-- 表示ON/OFF --}}
                <label class="inline-flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $notice->is_active ?? true))
                           class="w-5 h-5 text-indigo-600 rounded">
                    <span class="font-semibold text-gray-700">
                        表示する
                    </span>
                </label>

                {{-- 保存 --}}
                <div class="pt-4">
                    <button type="submit"
                            class="px-6 py-3 rounded-full bg-indigo-600 text-white font-semibold
                                   hover:bg-indigo-700 transition shadow">
                        💾 保存する
                    </button>
                </div>
            </form>

        </div>
    </div>

    @once
        <style>
            .notice-rich-body p { margin: 0.4rem 0; }
            .notice-rich-body ol { margin: 0.4rem 0; padding-left: 1.4rem; list-style: decimal; }
            .notice-rich-body ul { margin: 0.4rem 0; padding-left: 0; list-style: none; }
            .notice-rich-body ul li { position: relative; padding-left: 1.2rem; }
            .notice-rich-body ul li::before { content: "・"; position: absolute; left: 0; }
            .notice-rich-body a { color: #4f46e5; text-decoration: underline; }
            .notice-rich-body img {
                display: block;
                max-width: 100%;
                width: 100%;
                height: auto;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                object-fit: contain;
            }
            .notice-rich-body iframe {
                display: block;
                width: 100%;
                max-width: 100%;
                aspect-ratio: 16 / 9;
                height: auto;
                border: 0;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                background: #000;
            }
        </style>
    @endonce

    <script>
        (function () {
            const form = document.getElementById('noticeForm');
            const editor = document.getElementById('bodyEditor');
            const bodyInput = document.getElementById('bodyInput');
            const preview = document.getElementById('bodyPreview');
            const colorPicker = document.getElementById('editorColor');
            const linkBtn = document.getElementById('editorLinkBtn');
            const imageBtn = document.getElementById('editorImageBtn');
            const imageFile = document.getElementById('editorImageFile');
            const youtubeBtn = document.getElementById('editorYoutubeBtn');
            const fontDownBtn = document.getElementById('editorFontDownBtn');
            const fontUpBtn = document.getElementById('editorFontUpBtn');
            if (!form || !editor || !bodyInput) return;

            const hasHtmlTag = (s) => /<\s*\/?\s*[a-z][^>]*>/i.test(s);
            const escapeHtml = (s) => s
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const initialRaw = bodyInput.value || '';
            editor.innerHTML = hasHtmlTag(initialRaw)
                ? initialRaw
                : escapeHtml(initialRaw).replace(/\n/g, '<br>');

            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {
                // noop
            }

            const sync = () => {
                bodyInput.value = editor.innerHTML;
                if (preview) preview.innerHTML = editor.innerHTML;
            };

            const runCommand = (cmd, value = null) => {
                editor.focus();
                document.execCommand(cmd, false, value);
                sync();
            };
            const runInsertHtml = (html) => {
                editor.focus();
                document.execCommand('insertHTML', false, html);
                sync();
            };
            const FONT_SIZE_MIN = 1;
            const FONT_SIZE_MAX = 7;
            const getCurrentFontSizeLevel = () => {
                const current = parseInt(String(document.queryCommandValue('fontSize') || ''), 10);
                if (Number.isInteger(current) && current >= FONT_SIZE_MIN && current <= FONT_SIZE_MAX) {
                    return current;
                }
                return 3;
            };
            const applyFontSizeStep = (delta) => {
                const next = Math.max(FONT_SIZE_MIN, Math.min(FONT_SIZE_MAX, getCurrentFontSizeLevel() + delta));
                runCommand('fontSize', String(next));
            };

            document.querySelectorAll('[data-editor-cmd]').forEach((btn) => {
                btn.addEventListener('click', () => runCommand(btn.dataset.editorCmd));
            });

            if (colorPicker) {
                colorPicker.addEventListener('input', () => runCommand('foreColor', colorPicker.value));
            }

            const normalizeLinkUrl = (raw) => {
                const url = (raw || '').trim();
                if (!url) return '';
                if (/^(https?:\/\/|mailto:|tel:)/i.test(url)) return url;
                return `https://${url}`;
            };
            const escapeAttr = (s) => String(s || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const csrf = form.querySelector('input[name="_token"]')?.value || '';
            const imageUploadUrl = @json($noticeImageUploadUrl);
            const extractYouTubeId = (raw) => {
                const input = (raw || '').trim();
                if (!input) return '';
                if (/^[A-Za-z0-9_-]{11}$/.test(input)) return input;

                let url;
                try {
                    url = new URL(/^https?:\/\//i.test(input) ? input : `https://${input}`);
                } catch (e) {
                    return '';
                }

                const host = url.hostname.toLowerCase();
                const path = url.pathname;

                if (host === 'youtu.be') {
                    const id = path.replace(/^\/+/, '').split('/')[0] || '';
                    return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
                }

                if (host.endsWith('youtube.com') || host.endsWith('youtube-nocookie.com')) {
                    if (path === '/watch') {
                        const id = url.searchParams.get('v') || '';
                        return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
                    }
                    const m = path.match(/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})/);
                    return m ? m[1] : '';
                }

                return '';
            };

            if (linkBtn) {
                linkBtn.addEventListener('click', () => {
                    editor.focus();
                    const selection = window.getSelection();
                    if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                        alert('リンクにしたい文字を選択してから押してください。');
                        return;
                    }

                    const raw = prompt('リンク先URLを入力してください（例：https://example.com）');
                    if (raw === null) return;

                    const href = normalizeLinkUrl(raw);
                    if (!href) return;

                    runCommand('createLink', href);
                });
            }

            if (imageBtn) {
                imageBtn.addEventListener('click', () => {
                    if (!imageFile) return;
                    imageFile.click();
                });
            }

            if (imageFile) {
                imageFile.addEventListener('change', async () => {
                    const file = imageFile.files?.[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image', file);
                    if (csrf) {
                        formData.append('_token', csrf);
                    }

                    try {
                        imageBtn?.setAttribute('disabled', 'disabled');
                        const res = await fetch(imageUploadUrl, {
                            method: 'POST',
                            headers: csrf ? { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
                            body: formData,
                            credentials: 'same-origin',
                        });

                        const json = await res.json().catch(() => null);
                        if (!res.ok) {
                            let message = '画像のアップロードに失敗しました。';
                            if (res.status === 413) {
                                message = '画像サイズが大きすぎます。15MB以下でお試しください。';
                            } else if (json?.errors?.image?.[0]) {
                                message = json.errors.image[0];
                            } else if (json?.message) {
                                message = json.message;
                            }
                            throw new Error(message);
                        }
                        if (!json?.url) {
                            throw new Error('画像URLの取得に失敗しました。');
                        }

                        runInsertHtml(`<img src="${escapeAttr(json.url)}" alt="お知らせ画像">`);
                    } catch (e) {
                        alert(e?.message || '画像のアップロードに失敗しました。');
                    } finally {
                        imageBtn?.removeAttribute('disabled');
                        imageFile.value = '';
                    }
                });
            }

            if (youtubeBtn) {
                youtubeBtn.addEventListener('click', () => {
                    const raw = prompt('YouTube URL（または動画ID）を入力してください');
                    if (raw === null) return;
                    const videoId = extractYouTubeId(raw);
                    if (!videoId) {
                        alert('YouTubeのURL形式が正しくありません。');
                        return;
                    }
                    const src = `https://www.youtube-nocookie.com/embed/${videoId}`;
                    runInsertHtml(
                        `<iframe src="${src}" title="YouTube video player" ` +
                        `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ` +
                        `allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>`
                    );
                });
            }

            if (fontDownBtn) {
                fontDownBtn.addEventListener('click', () => applyFontSizeStep(-1));
            }
            if (fontUpBtn) {
                fontUpBtn.addEventListener('click', () => applyFontSizeStep(1));
            }

            editor.addEventListener('input', sync);
            form.addEventListener('submit', sync);
            sync();
        })();
    </script>
</x-app-layout>
