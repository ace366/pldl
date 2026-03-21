<x-app-layout>
    @php
        // route(..., false) だと本番のサブディレクトリ配備でベースパスが落ちるため、
        // 現在のリクエストルートを基準に保存・メディアアップロード先を組み立てる。
        $requestRoot = rtrim(request()->root(), '/');
        $noticeUpdateUrl = $requestRoot.route('admin.notices.update', [], false);
        $noticeMediaUploadUrl = $requestRoot.route('admin.notices.images.store', [], false);
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
                        本文（太字 / 色 / 下線 / 番号付き / 箇条書き / リンク / 画像 / 動画 / YouTube 対応）
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
                            <button type="button" id="editorVideoBtn"
                                    class="px-2 py-1 rounded border bg-white text-sm hover:bg-gray-100"
                                    title="動画を挿入">動画</button>
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
                            <input type="file" id="editorVideoFile" class="hidden" accept="video/*">
                        </div>

                        <div id="bodyEditor"
                             contenteditable="true"
                             class="min-h-[220px] w-full p-3 text-gray-800 focus:outline-none"></div>
                    </div>

                    <textarea id="bodyInput" name="body" class="hidden">{{ $initialBody }}</textarea>
                    @error('body')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror

                    <div class="mt-2 text-xs text-gray-500">
                        ※ Enterで改行。貼り付け時は書式が簡略化されます。リンクは文字とURLを登録できます。YouTubeはURLを入れると埋め込み再生されます。
                    </div>

                    <div class="mt-3 rounded-lg border border-dashed border-indigo-200 bg-indigo-50/40 p-3 text-sm text-gray-700">
                        <div class="font-semibold text-indigo-700 mb-2">プレビュー</div>
                        <div id="bodyPreview" class="notice-rich-body"></div>
                    </div>
                </div>

                <div id="editorLinkModal"
                     class="fixed inset-0 z-[90] hidden bg-slate-950/50 px-4 py-6">
                    <div class="mx-auto mt-16 w-full max-w-sm rounded-3xl bg-white p-4 shadow-2xl">
                        <div class="mb-4">
                            <div class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-500">Link</div>
                            <div class="mt-1 text-lg font-black text-slate-900">リンクを追加</div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="editorLinkTextInput" class="mb-1 block text-xs font-bold text-slate-500">表示テキスト</label>
                                <input id="editorLinkTextInput" type="text"
                                       class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label for="editorLinkUrlInput" class="mb-1 block text-xs font-bold text-slate-500">リンクURL</label>
                                <input id="editorLinkUrlInput" type="url" inputmode="url" placeholder="https://example.com"
                                       class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button type="button" id="editorLinkCancelBtn"
                                    class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                キャンセル
                            </button>
                            <button type="button" id="editorLinkApplyBtn"
                                    class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white hover:bg-indigo-700">
                                リンクを追加
                            </button>
                        </div>
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
            .notice-rich-body video {
                display: block;
                width: 100%;
                max-width: 100%;
                height: auto;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                background: #000;
            }
            #bodyEditor .notice-editor-media {
                display: block;
                margin: 0.6rem 0;
            }
            #bodyEditor .notice-editor-media img,
            #bodyEditor .notice-editor-media iframe,
            #bodyEditor .notice-editor-media video {
                pointer-events: none;
            }
            #bodyEditor .notice-editor-spacer {
                display: inline-block;
                width: 0;
                overflow: hidden;
                opacity: 0;
                vertical-align: middle;
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
            const linkModal = document.getElementById('editorLinkModal');
            const linkTextInput = document.getElementById('editorLinkTextInput');
            const linkUrlInput = document.getElementById('editorLinkUrlInput');
            const linkCancelBtn = document.getElementById('editorLinkCancelBtn');
            const linkApplyBtn = document.getElementById('editorLinkApplyBtn');
            const imageBtn = document.getElementById('editorImageBtn');
            const imageFile = document.getElementById('editorImageFile');
            const videoBtn = document.getElementById('editorVideoBtn');
            const videoFile = document.getElementById('editorVideoFile');
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

            let savedRange = null;

            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {
                // noop
            }

            const isMediaWrapper = (node) =>
                !!(node && node.nodeType === Node.ELEMENT_NODE && node.dataset.noticeMediaWrapper === '1');
            const isMediaSpacer = (node) =>
                !!(node && node.nodeType === Node.ELEMENT_NODE && node.dataset.noticeMediaSpacer === '1');
            const createMediaMarkup = (html) =>
                `<span class="notice-editor-media" data-notice-media-wrapper="1" contenteditable="false">${html}</span>` +
                `<span class="notice-editor-spacer" data-notice-media-spacer="1">\u200b</span>`;
            const cleanEditorHtml = () => {
                const clone = editor.cloneNode(true);

                clone.querySelectorAll('[data-notice-media-wrapper="1"]').forEach((wrapper) => {
                    const parent = wrapper.parentNode;
                    if (!parent) return;
                    while (wrapper.firstChild) {
                        parent.insertBefore(wrapper.firstChild, wrapper);
                    }
                    parent.removeChild(wrapper);
                });

                clone.querySelectorAll('[data-notice-media-spacer="1"]').forEach((spacer) => {
                    spacer.remove();
                });

                return clone.innerHTML.replace(/\u200b/g, '');
            };
            const sync = () => {
                const html = cleanEditorHtml();
                bodyInput.value = html;
                if (preview) preview.innerHTML = html;
            };

            const saveSelection = () => {
                const selection = window.getSelection();
                if (!selection || selection.rangeCount === 0) {
                    savedRange = null;
                    return;
                }
                savedRange = selection.getRangeAt(0).cloneRange();
            };
            const restoreSelection = () => {
                if (!savedRange) return false;
                const selection = window.getSelection();
                if (!selection) return false;
                selection.removeAllRanges();
                selection.addRange(savedRange);
                return true;
            };
            const placeCaretAtEndOfNode = (node) => {
                if (!node) return;
                const selection = window.getSelection();
                if (!selection) return;
                const range = document.createRange();
                if (node.nodeType === Node.TEXT_NODE) {
                    range.setStart(node, node.textContent?.length ?? 0);
                } else {
                    range.selectNodeContents(node);
                    range.collapse(false);
                }
                selection.removeAllRanges();
                selection.addRange(range);
            };
            const focusSpacer = (spacer) => {
                if (!isMediaSpacer(spacer)) return;
                if (!spacer.firstChild) {
                    spacer.textContent = '\u200b';
                }
                placeCaretAtEndOfNode(spacer.firstChild || spacer);
            };
            const normalizeEditorMedia = () => {
                const mediaNodes = Array.from(editor.querySelectorAll('img, iframe, video'));
                mediaNodes.forEach((media) => {
                    if (media.closest('[data-notice-media-wrapper="1"]')) {
                        const spacer = media.closest('[data-notice-media-wrapper="1"]')?.nextSibling;
                        if (spacer && isMediaSpacer(spacer) && !spacer.textContent) {
                            spacer.textContent = '\u200b';
                        }
                        return;
                    }

                    const wrapper = document.createElement('span');
                    wrapper.className = 'notice-editor-media';
                    wrapper.dataset.noticeMediaWrapper = '1';
                    wrapper.contentEditable = 'false';

                    const spacer = document.createElement('span');
                    spacer.className = 'notice-editor-spacer';
                    spacer.dataset.noticeMediaSpacer = '1';
                    spacer.textContent = '\u200b';

                    const parent = media.parentNode;
                    if (!parent) return;
                    parent.insertBefore(wrapper, media);
                    wrapper.appendChild(media);
                    parent.insertBefore(spacer, wrapper.nextSibling);
                });
            };
            const runCommand = (cmd, value = null) => {
                editor.focus();
                document.execCommand(cmd, false, value);
                normalizeEditorMedia();
                sync();
            };
            const runInsertHtml = (html) => {
                editor.focus();
                document.execCommand('insertHTML', false, html);
                normalizeEditorMedia();
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
            const mediaUploadUrl = @json($noticeMediaUploadUrl);
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
            const closeLinkModal = () => {
                linkModal?.classList.add('hidden');
            };
            const openLinkModal = (selectedText) => {
                if (!linkModal || !linkTextInput || !linkUrlInput) return;
                linkTextInput.value = selectedText || '';
                linkUrlInput.value = '';
                linkModal.classList.remove('hidden');
                setTimeout(() => {
                    if (selectedText) {
                        linkUrlInput.focus();
                    } else {
                        linkTextInput.focus();
                    }
                }, 0);
            };
            const insertLinkAtSelection = (text, href) => {
                editor.focus();
                if (!restoreSelection()) {
                    const safeHtml = `<a href="${escapeAttr(href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(text)}</a>`;
                    runInsertHtml(safeHtml);
                    return;
                }

                const selection = window.getSelection();
                if (!selection || selection.rangeCount === 0) {
                    return;
                }
                const range = selection.getRangeAt(0);
                range.deleteContents();

                const link = document.createElement('a');
                link.href = href;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = text;
                range.insertNode(link);

                const afterRange = document.createRange();
                afterRange.setStartAfter(link);
                afterRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(afterRange);
                savedRange = afterRange.cloneRange();
                sync();
            };
            const getBackwardMediaTarget = () => {
                const selection = window.getSelection();
                if (!selection || !selection.isCollapsed || selection.rangeCount === 0) return null;

                const anchorNode = selection.anchorNode;
                const anchorOffset = selection.anchorOffset;
                if (!anchorNode) return null;

                if (anchorNode.nodeType === Node.TEXT_NODE) {
                    const parent = anchorNode.parentNode;
                    if (isMediaSpacer(parent) && anchorOffset > 0) {
                        const wrapper = parent.previousSibling;
                        if (isMediaWrapper(wrapper)) {
                            return { wrapper, spacer: parent };
                        }
                    }
                }

                if (anchorNode.nodeType === Node.ELEMENT_NODE) {
                    const beforeNode = anchorNode.childNodes[anchorOffset - 1];
                    if (isMediaSpacer(beforeNode) && isMediaWrapper(beforeNode.previousSibling)) {
                        return { wrapper: beforeNode.previousSibling, spacer: beforeNode };
                    }
                }

                return null;
            };
            const removeBackwardMedia = () => {
                const target = getBackwardMediaTarget();
                if (!target) return false;

                const { wrapper, spacer } = target;
                const previousSibling = wrapper.previousSibling;
                const nextSibling = spacer.nextSibling;
                wrapper.remove();
                spacer.remove();
                sync();

                if (isMediaSpacer(nextSibling)) {
                    focusSpacer(nextSibling);
                } else if (nextSibling?.nodeType === Node.TEXT_NODE) {
                    placeCaretAtEndOfNode(nextSibling);
                } else if (previousSibling?.nodeType === Node.TEXT_NODE) {
                    placeCaretAtEndOfNode(previousSibling);
                } else {
                    placeCaretAtEndOfNode(editor);
                }
                return true;
            };
            const uploadMedia = async (file, fieldName, button) => {
                if (!file) return;

                const isVideo = fieldName === 'video';
                const formData = new FormData();
                formData.append(fieldName, file);
                if (csrf) {
                    formData.append('_token', csrf);
                }

                try {
                    button?.setAttribute('disabled', 'disabled');
                    const res = await fetch(mediaUploadUrl, {
                        method: 'POST',
                        headers: csrf ? { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const json = await res.json().catch(() => null);
                    if (!res.ok) {
                        let message = isVideo ? '動画のアップロードに失敗しました。' : '画像のアップロードに失敗しました。';
                        if (res.status === 413) {
                            message = isVideo ? '動画サイズが大きすぎます。50MB以下でお試しください。' : '画像サイズが大きすぎます。15MB以下でお試しください。';
                        } else if (json?.errors?.[fieldName]?.[0]) {
                            message = json.errors[fieldName][0];
                        } else if (json?.message) {
                            message = json.message;
                        }
                        throw new Error(message);
                    }
                    if (!json?.url) {
                        throw new Error(isVideo ? '動画URLの取得に失敗しました。' : '画像URLの取得に失敗しました。');
                    }

                    if (isVideo) {
                        runInsertHtml(
                            createMediaMarkup(
                                `<video src="${escapeAttr(json.url)}" controls playsinline preload="metadata"></video>`
                            )
                        );
                    } else {
                        runInsertHtml(
                            createMediaMarkup(
                                `<img src="${escapeAttr(json.url)}" alt="お知らせ画像">`
                            )
                        );
                    }
                } catch (e) {
                    alert(e?.message || (isVideo ? '動画のアップロードに失敗しました。' : '画像のアップロードに失敗しました。'));
                } finally {
                    button?.removeAttribute('disabled');
                }
            };

            if (linkBtn) {
                linkBtn.addEventListener('click', () => {
                    editor.focus();
                    saveSelection();
                    const selection = window.getSelection();
                    const selectedText = selection ? String(selection.toString() || '').trim() : '';
                    openLinkModal(selectedText);
                });
            }
            if (linkCancelBtn) {
                linkCancelBtn.addEventListener('click', closeLinkModal);
            }
            if (linkApplyBtn) {
                linkApplyBtn.addEventListener('click', () => {
                    const text = (linkTextInput?.value || '').trim();
                    const href = normalizeLinkUrl(linkUrlInput?.value || '');
                    if (!text) {
                        alert('表示テキストを入力してください。');
                        linkTextInput?.focus();
                        return;
                    }
                    if (!href) {
                        alert('リンクURLを入力してください。');
                        linkUrlInput?.focus();
                        return;
                    }

                    insertLinkAtSelection(text, href);
                    closeLinkModal();
                });
            }
            if (linkModal) {
                linkModal.addEventListener('click', (event) => {
                    if (event.target === linkModal) {
                        closeLinkModal();
                    }
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
                    await uploadMedia(file, 'image', imageBtn);
                    imageFile.value = '';
                });
            }
            if (videoBtn) {
                videoBtn.addEventListener('click', () => {
                    if (!videoFile) return;
                    videoFile.click();
                });
            }
            if (videoFile) {
                videoFile.addEventListener('change', async () => {
                    const file = videoFile.files?.[0];
                    await uploadMedia(file, 'video', videoBtn);
                    videoFile.value = '';
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
                        createMediaMarkup(
                            `<iframe src="${src}" title="YouTube video player" ` +
                            `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ` +
                            `allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>`
                        )
                    );
                });
            }

            if (fontDownBtn) {
                fontDownBtn.addEventListener('click', () => applyFontSizeStep(-1));
            }
            if (fontUpBtn) {
                fontUpBtn.addEventListener('click', () => applyFontSizeStep(1));
            }

            editor.addEventListener('beforeinput', (event) => {
                if (event.inputType === 'deleteContentBackward' && removeBackwardMedia()) {
                    event.preventDefault();
                }
            });
            editor.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && removeBackwardMedia()) {
                    event.preventDefault();
                }
            });
            editor.addEventListener('click', (event) => {
                const wrapper = event.target.closest?.('[data-notice-media-wrapper="1"]');
                if (!wrapper) return;
                event.preventDefault();
                focusSpacer(wrapper.nextSibling);
            });
            editor.addEventListener('input', sync);
            form.addEventListener('submit', sync);
            normalizeEditorMedia();
            sync();
        })();
    </script>
</x-app-layout>
