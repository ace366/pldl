import React, { useEffect, useMemo, useRef, useState } from 'react';

const badgeId = 'family-message-badge';
const composerMinHeight = 44;
const composerMaxHeight = 160;
const POLL_INTERVAL_MS = 2000;

function resizeComposer(el) {
  if (!el) return;
  el.style.height = 'auto';
  const next = Math.min(Math.max(el.scrollHeight, composerMinHeight), composerMaxHeight);
  el.style.height = `${next}px`;
  el.style.overflowY = el.scrollHeight > composerMaxHeight ? 'auto' : 'hidden';
}

function updateBadge(count) {
  const badge = document.getElementById(badgeId);
  if (!badge) return;

  if (count > 0) {
    badge.textContent = '1';
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
  badge.dataset.count = String(count);
}

function renderMessageBody(text) {
  if (!text) return null;
  const urlRe = /(https?:\/\/[^\s]+)/g;
  const isUrl = (value) => /^https?:\/\/[^\s]+$/.test(value);
  const lines = String(text).split('\n');

  return lines.map((line, lineIdx) => {
    const parts = line.split(urlRe);
    return (
      <React.Fragment key={`line-${lineIdx}`}>
        {parts.map((part, idx) => {
          if (isUrl(part)) {
            return (
              <a
                key={`link-${lineIdx}-${idx}`}
                href={part}
                target="_blank"
                rel="noopener noreferrer"
                className="text-blue-600 underline break-all"
              >
                {part}
              </a>
            );
          }
          return <React.Fragment key={`text-${lineIdx}-${idx}`}>{part}</React.Fragment>;
        })}
        {lineIdx < lines.length - 1 && <br />}
      </React.Fragment>
    );
  });
}

function getServerId(id) {
  const value = Number(id);
  if (!Number.isFinite(value) || value <= 0) return 0;
  return value;
}

function mergeMessages(prev, incoming) {
  if (!Array.isArray(incoming) || incoming.length === 0) return prev;

  const indexById = new Map(prev.map((msg, index) => [String(msg.id), index]));
  const merged = [...prev];

  incoming.forEach((msg) => {
    const key = String(msg.id);
    const existingIndex = indexById.get(key);
    if (existingIndex === undefined) {
      indexById.set(key, merged.length);
      merged.push(msg);
      return;
    }
    merged[existingIndex] = { ...merged[existingIndex], ...msg };
  });

  return merged;
}

export default function FamilyHomePage({
  child,
  siblings = [],
  messages = [],
  csrf,
  parentAvatar,
  adminAvatar,
  replyUrl,
  readStatusUrl,
  fetchMessagesUrl,
  line = null,
}) {
  const defaultWelcomeMessage =
    `${child?.name || ''}こんにちは！\n` +
    'メニューの使い方をご案内します。\n' +
    '1. 📣 おしらせ：全員に一斉にお知らせしたい内容を表示します。定期的に確認してください。\n' +
    '2. 🔳 マイQR：お子様の出席に使用します。まだ携帯電話がない場合はこちらでカードをご用意します。\n' +
    '3. 📅 送迎：送迎を希望される場合はこちらから登録をお願いします。\n' +
    '4. 💬 メッセージ：LINE同様こちらからは個別のメッセージ交換ができます。\n' +
    '最後にQRコードのカード作成を依頼しますか？返信をお願いします。';
  const initialLastServerId = useMemo(
    () => messages.reduce((max, message) => Math.max(max, getServerId(message.id)), 0),
    [messages]
  );
  const [items, setItems] = useState(messages);
  const unreadCount = useMemo(
    () => items.filter((message) => !message.isRead && message.from === 'admin').length,
    [items]
  );
  const [text, setText] = useState('');
  const markedRef = useRef(new Set());
  const composerRef = useRef(null);
  const lastServerIdRef = useRef(initialLastServerId);

  useEffect(() => {
    updateBadge(unreadCount);
  }, [unreadCount]);

  useEffect(() => {
    resizeComposer(composerRef.current);
  }, [text]);

  useEffect(() => {
    if (!items.length) {
      updateBadge(0);
      return;
    }

    const unread = items.filter((m) => !m.isRead && m.from !== 'family');
    if (!unread.length) {
      updateBadge(0);
      return;
    }

    unread.forEach((m) => {
      if (markedRef.current.has(m.id)) return;
      markedRef.current.add(m.id);

      fetch(m.readUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      })
        .catch(() => {})
        .finally(() => {
          setItems((prev) =>
            prev.map((msg) =>
              msg.id === m.id ? { ...msg, isRead: true } : msg
            )
          );
        });
    });

    return () => {};
  }, [items, csrf]);

  useEffect(() => {
    if (!fetchMessagesUrl) return;
    let timer = null;
    let stopped = false;

    const tick = async () => {
      if (stopped) return;
      try {
        const afterId = lastServerIdRef.current;
        const response = await fetch(
          `${fetchMessagesUrl}?after_id=${encodeURIComponent(String(afterId))}`,
          {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
          }
        );
        if (!response.ok) return;

        const data = await response.json();
        const list = Array.isArray(data?.messages) ? data.messages : [];
        if (!list.length) return;

        setItems((prev) => mergeMessages(prev, list));
        list.forEach((message) => {
          lastServerIdRef.current = Math.max(lastServerIdRef.current, getServerId(message.id));
        });
      } catch (e) {
        // no-op
      }
    };

    tick();
    timer = setInterval(tick, POLL_INTERVAL_MS);

    return () => {
      stopped = true;
      if (timer) clearInterval(timer);
    };
  }, [fetchMessagesUrl]);

  useEffect(() => {
    if (!readStatusUrl) return;
    let timer = null;
    let stopped = false;

    const tick = async () => {
      if (stopped) return;
      try {
        const res = await fetch(readStatusUrl, {
          method: 'GET',
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });
        if (!res.ok) return;
        const data = await res.json();
        const readIds = Array.isArray(data?.readIds) ? data.readIds : [];
        const readSet = new Set(readIds.map((id) => String(id)));
        setItems((prev) =>
          prev.map((msg) => {
            if (msg.from !== 'family') return msg;
            if (!msg.id || String(msg.id).startsWith('temp-')) return msg;
            const isRead = readSet.has(String(msg.id));
            return msg.isRead === isRead ? msg : { ...msg, isRead };
          })
        );
      } catch (e) {
        // no-op
      }
    };

    tick();
    timer = setInterval(tick, POLL_INTERVAL_MS);

    return () => {
      stopped = true;
      if (timer) clearInterval(timer);
    };
  }, [readStatusUrl]);

  const handleSend = async () => {
    const body = text.trim();
    if (!body || !replyUrl) return;

    const now = new Date();
    const tempId = `temp-${now.getTime()}`;
    const sentAt = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(
      now.getDate()
    ).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(
      now.getMinutes()
    ).padStart(2, '0')}`;

    setText('');
    setItems((prev) => [
      ...prev,
      {
        id: tempId,
        title: null,
        body,
        sentAt,
        isRead: false,
        readUrl: '',
        from: 'family',
      },
    ]);

    try {
      const response = await fetch(replyUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ body }),
      });

      if (!response.ok) {
        throw new Error('failed to send message');
      }

      const data = await response.json().catch(() => ({}));
      const savedMessage = data?.message;
      if (savedMessage?.id) {
        lastServerIdRef.current = Math.max(lastServerIdRef.current, getServerId(savedMessage.id));
        setItems((prev) => {
          const withoutTemp = prev.filter((msg) => msg.id !== tempId);
          if (withoutTemp.some((msg) => String(msg.id) === String(savedMessage.id))) {
            return withoutTemp;
          }
          return [...withoutTemp, savedMessage];
        });
      }
    } catch (e) {
      setItems((prev) => prev.filter((msg) => msg.id !== tempId));
      setText(body);
    }
  };

  const handleTextChange = (event) => {
    setText(event.target.value);
    resizeComposer(event.target);
  };

  return (
    <div className="bg-[#eaf6ee]">
      <div className="sticky top-12 z-30 border-b border-emerald-200 bg-[#c8f0d2]">
        <div className="px-4 py-4 sm:px-6">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-base font-semibold text-emerald-900">
                {child?.name}さんの専用ページ
              </div>
              <div className="mt-1 text-xs text-emerald-900/70">
                ログインID {child?.code}
              </div>
              <div className="text-xs text-emerald-900/70">
                {child?.school} / {child?.grade}年 / {child?.base}
              </div>
            </div>

            <div className="flex items-center gap-3 text-xs text-emerald-900/70">
              <div className="flex items-center gap-2">
                <img
                  src={adminAvatar}
                  alt="管理者"
                  className="h-9 w-9 rounded-full border border-emerald-200 object-cover shadow-sm"
                />
                <span>管理者</span>
              </div>
              <div className="flex items-center gap-2">
                <img
                  src={parentAvatar}
                  alt="保護者"
                  className="h-9 w-9 rounded-full border border-emerald-200 object-cover shadow-sm"
                />
                <span>保護者（この児童）</span>
              </div>
            </div>
          </div>

          {siblings.length > 1 && (
            <div className="mt-3 overflow-x-auto pb-1">
              <div className="inline-flex min-w-max gap-2">
                {siblings.map((s) => (
                  <a
                    key={s.id}
                    href={s.homeUrl}
                    className={`flex items-center gap-2 rounded-2xl border px-2.5 py-2 shadow-sm transition ${
                      s.isActive
                        ? 'border-emerald-400 bg-white text-emerald-900'
                        : 'border-emerald-100 bg-emerald-50/70 text-emerald-900/80 hover:bg-white'
                    }`}
                  >
                    <img
                      src={s.avatarUrl || parentAvatar}
                      alt={`${s.name}の保護者アイコン`}
                      className={`h-9 w-9 rounded-full border object-cover ${
                        s.isActive ? 'border-emerald-400' : 'border-emerald-200'
                      }`}
                    />
                    <div className="text-left leading-tight">
                      <div className="text-xs font-semibold">{s.name}</div>
                      <div className="text-[10px] opacity-80">ID {s.code} / {s.grade}年</div>
                    </div>
                  </a>
                ))}
              </div>
            </div>
          )}

          <div className="mt-3 flex flex-wrap items-center gap-2">
            {line?.isLinked ? (
              <a
                href={line?.settingsUrl || '#'}
                className="inline-flex items-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
              >
                LINE連携済み（設定）
              </a>
            ) : (
              <>
                <a
                  href={line?.connectUrl || '#'}
                  className="inline-flex items-center rounded-xl bg-[#06C755] px-6 py-3 text-base font-bold text-white shadow-sm hover:brightness-95"
                >
                  LINE連携する
                </a>
                <a
                  href={line?.settingsUrl || '#'}
                  className="inline-flex items-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50"
                >
                  コード連携はこちら
                </a>
              </>
            )}
          </div>
        </div>
      </div>

      <div
        className="px-4 py-5 sm:px-6"
        style={{
          backgroundImage:
            'radial-gradient(circle at 12px 12px, rgba(255,255,255,0.6) 2px, transparent 2.4px)',
          backgroundSize: '28px 28px',
        }}
      >
        <div className="mx-auto max-w-3xl">
          <div
            className="h-[calc(100vh-360px)] overflow-y-auto pr-1 pb-28 pt-8 sm:h-[calc(100vh-360px)] sm:pb-24 sm:pt-0"
            style={{ paddingTop: 24, marginTop: 8 }}
          >
            <div className="space-y-4">
              {items.length === 0 && (
                <div className="flex items-start gap-3">
                  <img
                    src={adminAvatar}
                    alt="管理者"
                    className="h-10 w-10 rounded-full border border-emerald-200 object-cover shadow-sm"
                  />
                  <div className="max-w-[78%]">
                    <div className="rounded-2xl rounded-tl-none bg-white px-4 py-3 text-sm text-gray-800 shadow-md">
                      <div className="whitespace-pre-wrap leading-relaxed">
                        {defaultWelcomeMessage}
                      </div>
                    </div>
                  </div>
                </div>
              )}

          {items.map((m) => {
            const isFamily = m.from === 'family';
            return (
              <div
                key={m.id}
                className={`flex items-start gap-3 ${isFamily ? 'flex-row-reverse' : ''}`}
              >
                <img
                  src={isFamily ? parentAvatar : adminAvatar}
                  alt={isFamily ? '保護者' : '管理者'}
                  className="h-10 w-10 rounded-full border border-emerald-200 object-cover shadow-sm"
                />
                <div className="max-w-[78%]">
                  <div
                    className={`rounded-2xl px-4 py-3 text-sm text-gray-800 shadow-md ${
                      isFamily ? 'rounded-tr-none bg-emerald-100' : 'rounded-tl-none bg-white'
                    }`}
                  >
                    {m.title && (
                      <div className="mb-1 text-xs font-semibold text-emerald-900">
                        {m.title}
                      </div>
                    )}
                    <div className="whitespace-pre-wrap leading-relaxed">
                      {renderMessageBody(m.body)}
                    </div>
                  </div>
                  <div
                    className={`mt-1 flex items-center gap-2 text-[10px] text-gray-500 ${
                      isFamily ? 'justify-end' : ''
                    }`}
                  >
                    <span>{m.sentAt}</span>
                    {isFamily && m.isRead && (
                      <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">
                        既読
                      </span>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
            </div>
          </div>
        </div>
      </div>

      <div
        className="fixed bottom-16 left-0 right-0 border-t border-emerald-200 bg-[#f7fffa] px-3 py-2 sm:static sm:px-6 sm:py-4"
        style={{ paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))' }}
      >
        <div className="mx-auto flex max-w-3xl items-end gap-2 sm:gap-3">
          <textarea
            ref={composerRef}
            rows={1}
            value={text}
            onChange={handleTextChange}
            className="h-11 min-w-0 flex-1 resize-none rounded-2xl border border-emerald-200 bg-white px-4 py-2.5 text-base leading-6 text-gray-800 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 sm:text-sm sm:leading-5"
            placeholder="管理者へ返信する"
          />
          <button
            type="button"
            onClick={handleSend}
            disabled={!text.trim()}
            className="shrink-0 whitespace-nowrap rounded-full bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:bg-emerald-300"
          >
            送信
          </button>
        </div>
      </div>
    </div>
  );
}
