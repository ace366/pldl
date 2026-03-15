import React, { useEffect, useMemo, useRef, useState } from 'react';

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

export default function AdminChildMessagesPage({
  child,
  messages = [],
  csrf,
  parentAvatar,
  adminAvatar,
  sendUrl,
  canSend = true,
  readStatusUrl,
  fetchMessagesUrl,
}) {
  const [items, setItems] = useState(messages);
  const [text, setText] = useState('');
  const markedRef = useRef(new Set());
  const composerRef = useRef(null);
  const initialLastServerId = useMemo(
    () => messages.reduce((max, message) => Math.max(max, getServerId(message.id)), 0),
    [messages]
  );
  const lastServerIdRef = useRef(initialLastServerId);

  const unreadFamily = useMemo(
    () => items.filter((m) => m.from === 'family' && !m.isRead),
    [items]
  );

  useEffect(() => {
    resizeComposer(composerRef.current);
  }, [text]);

  useEffect(() => {
    if (!unreadFamily.length) return;

    unreadFamily.forEach((m) => {
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
  }, [unreadFamily, csrf]);

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
            if (msg.from !== 'admin') return msg;
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
    if (!canSend || !sendUrl) return;
    const body = text.trim();
    if (!body) return;

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
        from: 'admin',
      },
    ]);

    try {
      const response = await fetch(sendUrl, {
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
    <div
      className="bg-[#eaf6ee] min-h-[100svh] flex flex-col overflow-hidden"
      style={{
        background: '#eaf6ee',
        minHeight: '100svh',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden',
        paddingTop: 0,
        marginTop: 0,
      }}
    >
      <div
        className="sticky top-0 z-30 border-b border-emerald-200 bg-[#c8f0d2]"
        style={{
          position: 'sticky',
          top: 0,
          zIndex: 30,
          borderBottom: '1px solid #b7e4c7',
          background: '#c8f0d2',
          marginTop: '-10px',
        }}
      >
        <div className="px-2 py-0 sm:px-4" style={{ paddingTop: 0, paddingBottom: 0 }}>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-base font-semibold text-emerald-900">
                {child?.name}さんとのやり取り
              </div>
              <div className="mt-1 text-xs text-emerald-900/70">
                ログインID {child?.code}
              </div>
              <div className="text-xs text-emerald-900/70">
                {child?.school} / {child?.grade}年 / {child?.base}
              </div>
            </div>

            <div className="text-xs text-emerald-900/70">
              返信があるとすぐに既読が付きます
            </div>
          </div>
        </div>
      </div>

      <div
        className="px-4 pt-0 pb-2 sm:px-6 flex-1"
        style={{
          backgroundImage:
            'radial-gradient(circle at 12px 12px, rgba(255,255,255,0.6) 2px, transparent 2.4px)',
          backgroundSize: '28px 28px',
          paddingTop: 0,
          marginTop: 0,
          flex: 1,
        }}
      >
        <div className="mx-auto max-w-3xl">
          <div className="h-full overflow-y-auto pr-1 pb-36 pt-2 sm:pb-44 sm:pt-2 message-scroll">
            <div className="space-y-4">
              {items.length === 0 && (
                <div className="rounded-2xl bg-white/80 px-4 py-5 text-center text-sm text-emerald-900/70 shadow-sm">
                  メッセージがまだありません。
                </div>
              )}

              {items.map((m) => {
                const isFamily = m.from === 'family';
                return (
                  <div
                    key={m.id}
                    className={`flex items-start gap-3 ${isFamily ? '' : 'flex-row-reverse'}`}
                  >
                    <img
                      src={isFamily ? parentAvatar : adminAvatar}
                      alt={isFamily ? '保護者' : '管理者'}
                      className="h-10 w-10 rounded-full border border-emerald-200 object-cover shadow-sm"
                    />
                    <div className="max-w-[78%]">
                      <div
                        className={`rounded-2xl px-4 py-3 text-sm text-gray-800 shadow-md ${
                          isFamily ? 'rounded-tl-none bg-white' : 'rounded-tr-none bg-emerald-100'
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
                          isFamily ? '' : 'justify-end'
                        }`}
                      >
                        <span>{m.sentAt}</span>
                        {m.from === 'admin' && m.isRead && (
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

      {canSend ? (
        <div
          className="fixed bottom-4 left-0 right-0 z-40 border-t border-emerald-200 bg-[#f7fffa] px-3 py-2 sm:px-6 sm:py-4"
          style={{
            position: 'fixed',
            bottom: 4,
            left: 0,
            right: 0,
            zIndex: 40,
            borderTop: '1px solid #b7e4c7',
            background: '#f7fffa',
            paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))',
          }}
        >
          <div className="mx-auto flex max-w-3xl items-end gap-2 sm:gap-3">
            <textarea
              ref={composerRef}
              rows={1}
              value={text}
              onChange={handleTextChange}
              className="h-11 min-w-0 flex-1 resize-none rounded-2xl border border-emerald-200 bg-white px-4 py-2.5 text-base leading-6 text-gray-800 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 sm:text-sm sm:leading-5"
              placeholder="保護者へ返信する"
            />
            <button
              type="button"
              onClick={handleSend}
              disabled={!text.trim()}
              className="shrink-0 whitespace-nowrap rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:bg-emerald-300"
            >
              送信
            </button>
          </div>
        </div>
      ) : (
        <div
          className="fixed bottom-4 left-0 right-0 z-40 border-t border-emerald-200 bg-[#f7fffa] px-3 py-2 sm:px-6 sm:py-4"
          style={{
            position: 'fixed',
            bottom: 4,
            left: 0,
            right: 0,
            zIndex: 40,
            borderTop: '1px solid #b7e4c7',
            background: '#f7fffa',
            paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))',
          }}
        >
          <div className="mx-auto flex max-w-3xl items-center gap-3 text-xs text-emerald-900/70">
            ※ 閲覧専用です（送信は管理者のみ）
          </div>
        </div>
      )}
    </div>
  );
}
