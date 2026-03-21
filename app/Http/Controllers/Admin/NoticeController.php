<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NoticeController extends Controller
{
    /**
     * 編集画面
     */
    public function edit()
    {
        // 基本は1件運用（なければnull）
        $notice = Notice::first();

        return view('admin.notices.edit', compact('notice'));
    }

    /**
     * 保存処理
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'title'     => 'required|string|max:100',
            'body'      => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);
        $data['body'] = $this->sanitizeBodyHtml((string)$data['body']);

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($data['body'])));
        if ($plain === '') {
            return back()
                ->withErrors(['body' => '本文を入力してください。'])
                ->withInput();
        }

        Notice::updateOrCreate(
            ['id' => 1], // 1件固定運用
            $data + ['published_at' => now()]
        );

        return redirect()->route('dashboard')->with('success', 'お知らせを更新しました');
    }

    public function uploadImage(Request $request)
    {
        $field = $request->hasFile('video') ? 'video' : 'image';
        $isVideo = $field === 'video';
        $validated = $request->validate([
            $field => [
                'required',
                'file',
                $isVideo ? 'max:51200' : 'max:15360',
                $isVideo
                    ? 'mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/3gpp2,video/x-m4v'
                    : 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,image/avif',
            ],
        ], [
            $field.'.required' => $isVideo ? '動画ファイルを選択してください。' : '画像ファイルを選択してください。',
            $field.'.file' => $isVideo ? '動画ファイルを選択してください。' : '画像ファイルを選択してください。',
            $field.'.max' => $isVideo ? '動画サイズは50MB以下にしてください。' : '画像サイズは15MB以下にしてください。',
            $field.'.mimetypes' => $isVideo
                ? 'mp4 / mov / webm / 3gp 系の動画を選択してください。'
                : 'jpg / png / gif / webp / heic / avif の画像を選択してください。',
        ]);

        $file = $validated[$field];
        $defaultExt = $isVideo ? 'mp4' : 'jpg';
        $ext = strtolower((string)($file->getClientOriginalExtension() ?: $file->extension() ?: $defaultExt));
        $name = now()->format('YmdHis').'-'.Str::random(10).'.'.$ext;
        $relativeDir = $isVideo ? 'uploads/notice-videos' : 'uploads/notice-images';
        $absoluteDir = public_path($relativeDir);

        if (!is_dir($absoluteDir)) {
            @mkdir($absoluteDir, 0755, true);
        }
        if (!is_dir($absoluteDir) || !is_writable($absoluteDir)) {
            return response()->json([
                'message' => 'アップロード先フォルダに書き込みできません。管理者にお問い合わせください。',
            ], 500);
        }

        try {
            $file->move($absoluteDir, $name);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => $isVideo
                    ? '動画の保存に失敗しました。時間をおいて再度お試しください。'
                    : '画像の保存に失敗しました。時間をおいて再度お試しください。',
            ], 500);
        }

        return response()->json([
            'url' => rtrim($request->root(), '/').'/'.$relativeDir.'/'.$name,
            'type' => $isVideo ? 'video' : 'image',
        ]);
    }

    private function sanitizeBodyHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="notice-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('notice-root');
        if (!$root) {
            return '';
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'u', 'span', 'ol', 'ul', 'li', 'a', 'font', 'div', 'img', 'iframe', 'video'];

        $sanitizeNode = function (\DOMNode $node) use (&$sanitizeNode, $allowedTags): void {
            for ($child = $node->firstChild; $child !== null; ) {
                $next = $child->nextSibling;
                $sanitizeNode($child);
                $child = $next;
            }

            if (!$node instanceof \DOMElement) {
                return;
            }

            $tag = strtolower($node->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                $parent = $node->parentNode;
                if ($parent) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                }
                return;
            }

            $allowedAttrs = match ($tag) {
                'a' => ['href', 'target', 'rel'],
                'span' => ['style', 'color'],
                'font' => ['style', 'color', 'size'],
                'img' => ['src', 'alt', 'loading', 'decoding', 'referrerpolicy'],
                'iframe' => ['src', 'title', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy'],
                'video' => ['src', 'controls', 'playsinline', 'preload', 'referrerpolicy'],
                default => [],
            };

            for ($i = $node->attributes->length - 1; $i >= 0; $i--) {
                $attr = $node->attributes->item($i);
                if (!$attr) {
                    continue;
                }
                if (!in_array(strtolower($attr->name), $allowedAttrs, true)) {
                    $node->removeAttribute($attr->name);
                }
            }

            if ($tag === 'a') {
                $href = trim((string)$node->getAttribute('href'));
                $isSafeHref = preg_match('/^(https?:\/\/|mailto:|tel:)/i', $href) === 1;
                if (!$isSafeHref) {
                    $node->removeAttribute('href');
                    $node->removeAttribute('target');
                    $node->removeAttribute('rel');
                    return;
                }
                // お知らせ内リンクは常に別タブで開く
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }

            if ($tag === 'span' || $tag === 'font') {
                $rawStyle = (string)$node->getAttribute('style');
                $rawColor = null;
                $rawFontSize = null;
                if ($node->hasAttribute('color')) {
                    $rawColor = $node->getAttribute('color');
                } elseif ($node->hasAttribute('style')) {
                    if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $rawStyle, $m) === 1) {
                        $rawColor = trim((string)$m[1]);
                    }
                }
                if ($tag === 'font' && $node->hasAttribute('size')) {
                    $rawFontSize = (string)$node->getAttribute('size');
                } elseif (preg_match('/(?:^|;)\s*font-size\s*:\s*([^;]+)/i', $rawStyle, $m) === 1) {
                    $rawFontSize = trim((string)$m[1]);
                }

                $isBold = preg_match('/(?:^|;)\s*font-weight\s*:\s*(bold|[7-9]00)\b/i', $rawStyle) === 1;
                $isUnderline = preg_match('/(?:^|;)\s*text-decoration(?:-line)?\s*:\s*[^;]*underline/i', $rawStyle) === 1;
                $safeColor = $this->normalizeColor($rawColor);
                $safeFontSize = $this->normalizeFontSize($rawFontSize);
                $node->removeAttribute('style');
                $node->removeAttribute('color');
                $node->removeAttribute('size');

                $styleParts = [];
                if ($safeColor !== null) {
                    $styleParts[] = 'color: '.$safeColor;
                }
                if ($safeFontSize !== null) {
                    $styleParts[] = 'font-size: '.$safeFontSize;
                }
                if ($isBold) {
                    $styleParts[] = 'font-weight: bold';
                }
                if ($isUnderline) {
                    $styleParts[] = 'text-decoration: underline';
                }
                if (!empty($styleParts)) {
                    $node->setAttribute('style', implode('; ', $styleParts).';');
                }
            }

            if ($tag === 'img') {
                $src = trim((string)$node->getAttribute('src'));
                if (!$this->isSafeMediaSrc($src)) {
                    $node->parentNode?->removeChild($node);
                    return;
                }

                $alt = trim((string)$node->getAttribute('alt'));
                $node->setAttribute('src', $src);
                $node->setAttribute('alt', $alt);
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('decoding', 'async');
                $node->setAttribute('referrerpolicy', 'no-referrer');
            }

            if ($tag === 'video') {
                $src = trim((string)$node->getAttribute('src'));
                if (!$this->isSafeMediaSrc($src)) {
                    $node->parentNode?->removeChild($node);
                    return;
                }

                $node->setAttribute('src', $src);
                $node->setAttribute('controls', 'controls');
                $node->setAttribute('playsinline', 'playsinline');
                $node->setAttribute('preload', 'metadata');
                $node->setAttribute('referrerpolicy', 'no-referrer');
            }

            if ($tag === 'iframe') {
                $src = trim((string)$node->getAttribute('src'));
                $safeSrc = $this->normalizeYoutubeEmbedUrl($src);
                if ($safeSrc === null) {
                    $node->parentNode?->removeChild($node);
                    return;
                }

                $title = trim((string)$node->getAttribute('title'));
                $node->setAttribute('src', $safeSrc);
                $node->setAttribute('title', $title !== '' ? $title : 'YouTube video player');
                $node->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                $node->setAttribute('allowfullscreen', 'allowfullscreen');
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            }
        };

        $sanitizeNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private function normalizeColor(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) === 1) {
            return strtolower($value);
        }

        if (preg_match('/^rgb\(\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*\)$/i', $value) === 1) {
            return strtolower($value);
        }

        if (preg_match('/^[a-zA-Z]{3,20}$/', $value) === 1) {
            return strtolower($value);
        }

        return null;
    }

    private function normalizeFontSize(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        // execCommand('fontSize') の 1〜7 を px に変換
        if (preg_match('/^[1-7]$/', $value) === 1) {
            $map = [
                '1' => '12px',
                '2' => '14px',
                '3' => '16px',
                '4' => '18px',
                '5' => '24px',
                '6' => '32px',
                '7' => '40px',
            ];
            return $map[$value] ?? null;
        }

        if (preg_match('/^([1-9]\d?)px$/i', $value, $m) === 1) {
            $px = (int)$m[1];
            if ($px >= 10 && $px <= 48) {
                return $px.'px';
            }
        }

        if (preg_match('/^([1-9]\d?)%$/', $value, $m) === 1) {
            $pct = (int)$m[1];
            if ($pct >= 60 && $pct <= 300) {
                return $pct.'%';
            }
        }

        if (preg_match('/^([0-2](?:\.\d+)?)rem$/i', $value, $m) === 1) {
            $rem = (float)$m[1];
            if ($rem >= 0.625 && $rem <= 3.0) {
                return rtrim(rtrim((string)$rem, '0'), '.').'rem';
            }
        }

        return null;
    }

    private function isSafeMediaSrc(string $src): bool
    {
        if ($src === '') {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $src) === 1) {
            return true;
        }

        // 同一オリジン配信（/storage/... など）を許可
        if (str_starts_with($src, '/')) {
            return true;
        }

        return false;
    }

    private function normalizeYoutubeEmbedUrl(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        if (preg_match(
            '/^https:\/\/www\.youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{11})(?:[?&][^#\s]*)?$/i',
            $src,
            $m
        ) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return null;
    }
}
