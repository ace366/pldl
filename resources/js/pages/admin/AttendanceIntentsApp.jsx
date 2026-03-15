import React, { useEffect, useMemo, useState } from 'react';

function cls(...a) {
  return a.filter(Boolean).join(' ');
}

async function jsonFetch(url, { method = 'GET', headers = {}, body } = {}) {
  const res = await fetch(url, {
    method,
    headers: { Accept: 'application/json', ...headers },
    body,
    credentials: 'same-origin',
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.message || '通信に失敗しました');
  return data;
}

// JST表示用： "H:i" / ISO / DB datetime などを受けて「日本時間 HH:mm」に整形
function formatJstTimeMaybe(value) {
  if (!value) return null;

  // すでに "HH:mm" 形式ならそのまま（APIがH:iで返す場合）
  if (/^\d{2}:\d{2}$/.test(String(value))) return String(value);

  const s = String(value);

  // "YYYY-MM-DD HH:mm:ss" を ISOっぽく変換して Date に食わせる
  // ※ 末尾Zなし＝ローカル扱いになるため、明示的にUTC扱いしたい場合はここを調整可能
  //    ただし「済にした時間を日本時間に」＝見た目JSTが優先なので、下でAsia/Tokyo表示に統一する
  let d;
  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s)) {
    // "2026-01-31 12:34:56" → "2026-01-31T12:34:56"
    d = new Date(s.replace(' ', 'T'));
  } else {
    d = new Date(s);
  }

  if (Number.isNaN(d.getTime())) return null;

  // Asia/Tokyo で HH:mm を返す
  const hhmm = new Intl.DateTimeFormat('ja-JP', {
    timeZone: 'Asia/Tokyo',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(d);

  return hhmm;
}

function Toast({ toast, onClose }) {
  if (!toast) return null;
  return (
    <div className="fixed top-3 right-3 z-50">
      <div
        className={cls(
          'rounded-xl border px-4 py-3 shadow-lg text-sm font-semibold',
          toast.type === 'error'
            ? 'bg-red-50 border-red-200 text-red-800'
            : 'bg-emerald-50 border-emerald-200 text-emerald-800'
        )}
      >
        <div className="flex items-start gap-3">
          <div className="mt-0.5">{toast.type === 'error' ? '⚠️' : '✅'}</div>
          <div className="leading-snug">{toast.message}</div>
          <button
            className="ml-2 text-gray-500 hover:text-gray-700"
            onClick={onClose}
            aria-label="close"
          >
            ✕
          </button>
        </div>
      </div>
    </div>
  );
}

function BaseAccordion({ base, isOpen, onToggle, stats, children }) {
  return (
    <div className="rounded-2xl border border-gray-200 overflow-hidden bg-white">
      <button
        type="button"
        onClick={onToggle}
        className="w-full flex items-center justify-between px-4 py-4 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 transition"
        aria-expanded={isOpen ? 'true' : 'false'}
      >
        <div className="text-left min-w-0">
          <div className="text-base font-extrabold text-gray-900 truncate">{base.name}</div>
          <div className="mt-1 text-xs text-gray-700 font-semibold flex gap-3">
            <span>参加：<b>{stats.total}</b></span>
            <span>送迎：<b className="text-indigo-700">{stats.pickup}</b></span>
            <span>車：<b className="text-emerald-700">{stats.cars}</b></span>
          </div>
        </div>
        <div className="text-xl flex-none">{isOpen ? '▲' : '▼'}</div>
      </button>

      {isOpen && <div className="p-3 sm:p-4">{children}</div>}
    </div>
  );
}

function SummaryChips({ total, pickup, cars }) {
  return (
    <div className="grid grid-cols-3 gap-2 mb-3">
      <div className="rounded-xl bg-gray-50 border border-gray-200 p-3">
        <div className="text-[11px] text-gray-600">参加予定</div>
        <div className="text-lg font-extrabold">{total}<span className="text-xs font-bold ml-1">人</span></div>
      </div>
      <div className="rounded-xl bg-indigo-50 border border-indigo-100 p-3">
        <div className="text-[11px] text-indigo-700">送迎対象</div>
        <div className="text-lg font-extrabold text-indigo-900">{pickup}<span className="text-xs font-bold ml-1">人</span></div>
      </div>
      <div className="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
        <div className="text-[11px] text-emerald-700">必要車両</div>
        <div className="text-lg font-extrabold text-emerald-900">{cars}<span className="text-xs font-bold ml-1">台</span></div>
      </div>
    </div>
  );
}

function ChildCard({ child, busy, onTogglePickup, onManual }) {
  // 表示トーン
  const rowTone = useMemo(() => {
    if (!child.arrived) return 'border-red-200 bg-red-50';
    if (child.arrived && child.pickup_required && !child.pickup_confirmed) return 'border-orange-200 bg-orange-50';
    return 'border-indigo-200 bg-indigo-50';
  }, [child.arrived, child.pickup_confirmed, child.pickup_required]);

  const statusLabel = child.arrived ? '出席済' : '未到着';
  const manualBadge = child.manual_status ? '手動' : '自動';

  const pickupDisabled = !child.pickup_required;

  const pickupLabel = child.pickup_confirmed ? '済' : '未';
  const pickupImg = child.pickup_confirmed ? '/images/ccar.png' : '/images/car.png';

  // ✅ JST 表示に統一（APIがHH:mmでもISOでもOK）
  const pickupTimeJst = formatJstTimeMaybe(child.pickup_confirmed_at);

  return (
    <div className={cls('rounded-2xl border p-3 sm:p-4', rowTone)}>
      {/* ✅ 1行固定の名前 */}
      <div className="flex items-center gap-2">
        <div className="flex-1 min-w-0">
          <div className="font-extrabold text-gray-900 truncate text-base">
            {child.name}
          </div>
          <div className="text-[11px] text-gray-600 truncate">
            {child.nameKana || child.child_name_kana || '—'}
          </div>
        </div>

        <span
          className={cls(
            'text-[11px] px-2 py-0.5 rounded-full border font-bold flex-none',
            child.manual_status
              ? 'bg-yellow-100 border-yellow-300 text-yellow-900'
              : 'bg-gray-100 border-gray-200 text-gray-700'
          )}
        >
          {manualBadge}
        </span>

        {child.pickup_required && (
          <span className="text-[11px] px-2 py-0.5 rounded-full bg-indigo-100 border border-indigo-200 text-indigo-800 font-bold flex-none">
            送迎
          </span>
        )}
      </div>

      {/* 状態行 */}
      <div className="mt-2 flex flex-wrap items-center gap-2">
        <span
          className={cls(
            'text-xs font-extrabold px-2 py-1 rounded-lg border',
            child.arrived
              ? 'bg-white/60 border-gray-200 text-gray-900'
              : 'bg-white border-red-200 text-red-800'
          )}
        >
          {statusLabel}
        </span>

        {/* ✅ 「済」にした時刻はJSTで表示 */}
        {child.pickup_required && child.pickup_confirmed && pickupTimeJst && (
          <span className="text-[11px] text-gray-600 font-semibold">
            済 {pickupTimeJst}
          </span>
        )}

        {child.manual_updated_at && (
          <span className="text-[11px] text-gray-600 font-semibold">
            最終更新 {child.manual_updated_at}
          </span>
        )}
      </div>

      {/* ✅ ボタン群は下に配置（縦長防止・操作性UP） */}
      <div className="mt-3 grid grid-cols-3 gap-2">
        <button
          type="button"
          disabled={busy}
          onClick={() => onManual(child.intent_id, 'arrived')}
          className={cls(
            'px-3 py-2 rounded-xl border text-xs font-extrabold transition',
            child.manual_status === 'arrived'
              ? 'bg-emerald-100 border-emerald-300 text-emerald-900'
              : 'bg-white/70 border-gray-200 text-gray-800 hover:bg-white'
          )}
        >
          出席
        </button>

        <button
          type="button"
          disabled={busy}
          onClick={() => onManual(child.intent_id, 'not_arrived')}
          className={cls(
            'px-3 py-2 rounded-xl border text-xs font-extrabold transition',
            child.manual_status === 'not_arrived'
              ? 'bg-red-100 border-red-300 text-red-900'
              : 'bg-white/70 border-gray-200 text-gray-800 hover:bg-white'
          )}
        >
          未到着
        </button>

        <button
          type="button"
          disabled={busy}
          onClick={() => onManual(child.intent_id, 'auto')}
          className={cls(
            'px-3 py-2 rounded-xl border text-xs font-extrabold transition',
            !child.manual_status
              ? 'bg-gray-100 border-gray-300 text-gray-900'
              : 'bg-white/70 border-gray-200 text-gray-800 hover:bg-white'
          )}
        >
          自動
        </button>
      </div>

      {/* ✅ 片手最優先の送迎チェック（横幅いっぱい・大ボタン） */}
      <div className="mt-3">
        <button
          type="button"
          disabled={busy || pickupDisabled}
          onClick={() => onTogglePickup(child.intent_id)}
          className={cls(
            'w-full rounded-2xl border-2 flex items-center justify-between gap-3 px-4 py-3 transition active:scale-[0.99]',
            pickupDisabled
              ? 'bg-gray-100 border-gray-200 text-gray-400'
              : child.pickup_confirmed
                ? 'bg-indigo-600 border-indigo-700 text-white shadow'
                : 'bg-white/70 border-orange-300 text-orange-900 hover:bg-white'
          )}
          aria-label="乗車チェック"
        >
          <div className="flex items-center gap-3">
            <img
              src={pickupImg}
              alt={pickupLabel}
              className={cls(
                'w-10 h-10 object-contain',
                child.pickup_confirmed ? '' : 'opacity-70',
                pickupDisabled ? 'opacity-30' : ''
              )}
            />
            <div className="text-left">
              <div className="text-sm font-extrabold">
                {pickupDisabled ? '送迎対象外' : `乗車 ${pickupLabel}`}
              </div>
              <div className="text-[11px] font-semibold opacity-90">
                {pickupDisabled
                  ? 'この子は送迎チェック不要です'
                  : child.pickup_confirmed
                    ? (pickupTimeJst ? `記録時刻（日本時間） ${pickupTimeJst}` : '記録時刻（日本時間）')
                    : 'タップで「済」にできます'}
              </div>
            </div>
          </div>

          <div className="text-sm font-extrabold flex-none">
            {pickupDisabled ? '—' : (child.pickup_confirmed ? '済' : '未')}
          </div>
        </button>
      </div>
    </div>
  );
}

export default function AttendanceIntentsApp({
  initialDate,
  apiSummary,
  apiTogglePickup,
  apiToggleManual,
  csrf,
}) {
  const [date, setDate] = useState(initialDate || '');
  const [loading, setLoading] = useState(true);
  const [summary, setSummary] = useState([]);
  const [openBaseIds, setOpenBaseIds] = useState(() => new Set()); // デフォルト閉
  const [toast, setToast] = useState(null);
  const [busyIds, setBusyIds] = useState(() => new Set());

  function showToast(message, type = 'success') {
    setToast({ message, type });
    window.clearTimeout(window.__toastTimer);
    window.__toastTimer = window.setTimeout(() => setToast(null), 1600);
  }

  function setBusy(intentId, on) {
    setBusyIds((prev) => {
      const next = new Set(prev);
      if (on) next.add(intentId);
      else next.delete(intentId);
      return next;
    });
  }

  // ✅ 並び替え：未到着 → 送迎対象 → 送迎未完了（到着済＆未）→ その他
  // ただし仕様上「送迎対象を上に」も強いので、
  // 優先順位は「未到着」最優先、その中で送迎対象が上、次に到着済は送迎対象が上、さらに未(未乗車)が上に来るようにする
  function sortChildren(children) {
    const arr = [...children];

    arr.sort((a, b) => {
      // 1) 未到着を上（falseが先）
      if (a.arrived !== b.arrived) return a.arrived ? 1 : -1;

      // 2) 送迎対象を上
      if (a.pickup_required !== b.pickup_required) return a.pickup_required ? -1 : 1;

      // 3) 送迎対象の中は「未(未乗車)」を上（falseが先）
      if (a.pickup_required && b.pickup_required && a.pickup_confirmed !== b.pickup_confirmed) {
        return a.pickup_confirmed ? 1 : -1;
      }

      // 4) 名前で安定ソート（同順位の揺れ防止）
      return String(a.name).localeCompare(String(b.name), 'ja');
    });

    return arr;
  }

  async function load(d) {
    setLoading(true);
    try {
      const qs = new URLSearchParams({ date: d }).toString();
      const data = await jsonFetch(`${apiSummary}?${qs}`);

      const raw = data.summary || [];
      // ✅ 拠点ごとに children を並び替えた新配列へ
      const normalized = raw.map((baseBlock) => ({
        ...baseBlock,
        children: sortChildren(baseBlock.children || []),
      }));

      setSummary(normalized);
    } catch (e) {
      showToast(e.message || '読み込みに失敗しました', 'error');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (!date) return;
    load(date);

    // URL同期
    const url = new URL(window.location.href);
    url.searchParams.set('date', date);
    window.history.replaceState({}, '', url.toString());
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [date]);

  function toggleBase(baseIdKey) {
    setOpenBaseIds((prev) => {
      const next = new Set(prev);
      if (next.has(baseIdKey)) next.delete(baseIdKey);
      else next.add(baseIdKey);
      return next;
    });
  }

  async function onTogglePickup(intentId) {
    if (busyIds.has(intentId)) return;
    setBusy(intentId, true);
    try {
      await jsonFetch(apiTogglePickup, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ intent_id: intentId }),
      });
      showToast('乗車状態を更新しました（日本時間で記録表示）');
      await load(date);
    } catch (e) {
      showToast(e.message || '更新に失敗しました', 'error');
    } finally {
      setBusy(intentId, false);
    }
  }

  async function onManual(intentId, status) {
    if (busyIds.has(intentId)) return;
    setBusy(intentId, true);
    try {
      await jsonFetch(apiToggleManual, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ intent_id: intentId, manual_status: status }),
      });
      showToast('状態を更新しました');
      await load(date);
    } catch (e) {
      showToast(e.message || '更新に失敗しました', 'error');
    } finally {
      setBusy(intentId, false);
    }
  }

  // 念のため初期日付補完
  useEffect(() => {
    if (date) return;
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    setDate(`${yyyy}-${mm}-${dd}`);
  }, [date]);

  return (
    <div className="space-y-4">
      <Toast toast={toast} onClose={() => setToast(null)} />

      {/* 日付 */}
      <div className="flex items-center gap-2">
        <input
          type="date"
          value={date}
          onChange={(e) => setDate(e.target.value)}
          className="border rounded-xl px-3 py-2 w-[160px]"
        />
        <button
          type="button"
          onClick={() => load(date)}
          className="px-4 py-2 rounded-xl bg-indigo-600 text-white font-extrabold shadow hover:bg-indigo-700 active:scale-[0.99]"
        >
          表示
        </button>
        {loading && <div className="text-sm text-gray-500 font-semibold ml-2">読み込み中…</div>}
      </div>

      {/* 拠点ごと（デフォルト閉） */}
      <div className="space-y-3">
        {summary.map((b) => {
          const baseId = b.base?.id; // nullあり
          const key = baseId === null ? 'null' : String(baseId);
          const isOpen = openBaseIds.has(key);

          return (
            <BaseAccordion
              key={key}
              base={b.base}
              isOpen={isOpen}
              onToggle={() => toggleBase(key)}
              stats={{ total: b.total, pickup: b.pickup, cars: b.cars }}
            >
              <SummaryChips total={b.total} pickup={b.pickup} cars={b.cars} />

              <div className="space-y-3">
                {(b.children || []).length === 0 ? (
                  <div className="text-center text-gray-500 py-6 font-semibold">
                    この拠点の参加予定はありません
                  </div>
                ) : (
                  (b.children || []).map((c) => (
                    <ChildCard
                      key={c.intent_id}
                      child={c}
                      busy={busyIds.has(c.intent_id)}
                      onTogglePickup={onTogglePickup}
                      onManual={onManual}
                    />
                  ))
                )}
              </div>
            </BaseAccordion>
          );
        })}
      </div>
    </div>
  );
}
