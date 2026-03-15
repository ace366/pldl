// resources/js/pages/family/AvailabilityPage.jsx
import React, { useMemo, useRef, useState } from "react";

const cancelReasonOptions = [
  { value: "illness", label: "かぜ・けがで欠席" },
  { value: "family", label: "家族の都合で欠席" },
  { value: "lesson", label: "習い事があるため欠席" },
  { value: "other", label: "その他" },
];

export default function AvailabilityPage(props) {
  const child = props?.child;
  const siblings = Array.isArray(props?.siblings) ? props.siblings : [];
  const cal = props?.calendar;
  const routes = props?.routes;
  const csrf = props?.csrf;
  const assets = props?.assets || {};

  // ✅ 画像URL（Laravelのasset()を優先）
  const attendanceIcon =
    assets.attendanceIcon ||
    (typeof window !== "undefined" ? `${window.location.origin}/images/attendance.png` : "/images/attendance.png");

  if (!child || !cal || !routes || !csrf) {
    return (
      <div className="p-4 text-sm text-red-600 font-semibold">
        表示に必要なデータが不足しています。ページを再読み込みしてください。
      </div>
    );
  }

  const [selectedSet, setSelectedSet] = useState(() => new Set(cal?.selectedDates || []));
  const [toast, setToast] = useState({ show: false, msg: "" });
  const [cancelModal, setCancelModal] = useState({ open: false, ymd: "" });
  const [cancelReason, setCancelReason] = useState("");
  const [cancelReasonOther, setCancelReasonOther] = useState("");
  const [cancelSubmitting, setCancelSubmitting] = useState(false);

  // bulk
  const [bulkAction, setBulkAction] = useState("on"); // on/off
  const [bulkType, setBulkType] = useState("grid_weekdays"); // grid_weekdays / month_weekday
  const [bulkWeekday, setBulkWeekday] = useState("1"); // 0..6

  const weekdays = useMemo(() => cal?.weekdays || ["日","月","火","水","木","金","土"], [cal]);
  const gridStart = cal?.gridStart;
  const gridEnd = cal?.gridEnd;

  const monthStart = cal?.monthStart;
  const monthEnd = cal?.monthEnd;

  const today = cal?.today;

  const toastTimerRef = useRef(null);

  function showToast(msg) {
    setToast({ show: true, msg });
    if (toastTimerRef.current) window.clearTimeout(toastTimerRef.current);
    toastTimerRef.current = window.setTimeout(() => {
      setToast({ show: false, msg: "" });
      toastTimerRef.current = null;
    }, 1400);
  }

  function ymdToDate(s) {
    const [y, m, d] = String(s).split("-").map((v) => parseInt(v, 10));
    return new Date(y, (m || 1) - 1, d || 1);
  }

  function isPast(ymd) {
    const d = ymdToDate(ymd);
    const t = ymdToDate(today);
    d.setHours(0, 0, 0, 0);
    t.setHours(0, 0, 0, 0);
    return d < t;
  }

  async function apiPost(url, payload) {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf,
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) {
      const msg = data?.message || "保存に失敗しました";
      throw new Error(msg);
    }
    return data;
  }

  async function toggleDate(ymd, extraPayload = {}) {
    try {
      const data = await apiPost(routes.toggle, { date: ymd, ...extraPayload });
      setSelectedSet((prev) => {
        const next = new Set(prev);
        if (data.status === "on") next.add(ymd);
        else next.delete(ymd);
        return next;
      });
      showToast(data.status === "on" ? "参加にしました！" : "参加を解除しました");
      return true;
    } catch (e) {
      showToast(e.message || "通信エラーです");
      return false;
    }
  }

  function openCancelModal(ymd) {
    setCancelReason("");
    setCancelReasonOther("");
    setCancelModal({ open: true, ymd });
  }

  function closeCancelModal() {
    if (cancelSubmitting) return;
    setCancelModal({ open: false, ymd: "" });
  }

  async function handleDayClick(day) {
    if (day?.past) return;
    if (day?.isToday && day?.selected) {
      openCancelModal(day.ymd);
      return;
    }
    await toggleDate(day.ymd);
  }

  const canSubmitCancel =
    cancelReason !== "" && (cancelReason !== "other" || cancelReasonOther.trim() !== "");

  async function submitCancelReason() {
    if (!cancelModal.open || !cancelModal.ymd || !canSubmitCancel || cancelSubmitting) return;

    setCancelSubmitting(true);
    const ok = await toggleDate(cancelModal.ymd, {
      cancel_reason: cancelReason,
      cancel_reason_other: cancelReason === "other" ? cancelReasonOther.trim() : "",
    });
    setCancelSubmitting(false);

    if (ok) {
      setCancelModal({ open: false, ymd: "" });
      showToast("本日の送迎を解除しました。理由を管理者へ送信しました。");
    }
  }

  function applyBulkLocal({ start, end, weekdaysArr, action }) {
    const wSet = new Set((weekdaysArr || []).map(String));
    const startD = ymdToDate(start);
    const endD = ymdToDate(end);
    startD.setHours(0, 0, 0, 0);
    endD.setHours(0, 0, 0, 0);

    setSelectedSet((prev) => {
      const next = new Set(prev);
      for (let i = 0; i < 28; i++) {
        const dt = ymdToDate(gridStart);
        dt.setDate(dt.getDate() + i);
        dt.setHours(0, 0, 0, 0);

        if (dt < startD || dt > endD) continue;

        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, "0");
        const d = String(dt.getDate()).padStart(2, "0");
        const ymd = `${y}-${m}-${d}`;

        if (isPast(ymd)) continue;

        const wd = String(dt.getDay()); // 0..6
        if (!wSet.has(wd)) continue;

        if (action === "on") next.add(ymd);
        else next.delete(ymd);
      }
      return next;
    });
  }

  async function bulkApply() {
    const action = bulkAction;
    const typeVal = bulkType;

    try {
      showToast("まとめて保存中...");

      if (typeVal === "grid_weekdays") {
        const weekdaysArr = [1, 2, 3, 4, 5];
        const data = await apiPost(routes.bulk, {
          mode: "grid",
          start: gridStart,
          end: gridEnd,
          weekdays: weekdaysArr,
          action,
        });

        applyBulkLocal({ start: gridStart, end: gridEnd, weekdaysArr, action });
        showToast(`平日を一括${action === "on" ? "ON" : "OFF"}しました（${data.count}件）`);
        return;
      }

      const weekday = parseInt(bulkWeekday, 10);
      const data = await apiPost(routes.bulk, {
        mode: "month",
        start: monthStart,
        end: monthEnd,
        weekdays: [weekday],
        action,
      });

      applyBulkLocal({ start: gridStart, end: gridEnd, weekdaysArr: [weekday], action });
      showToast(`曜日を一括${action === "on" ? "ON" : "OFF"}しました（${data.count}件）`);
    } catch (e) {
      showToast(e.message || "エラー");
    }
  }

  const isMonth = bulkType === "month_weekday";

  const days = useMemo(() => {
    const arr = [];
    const base = ymdToDate(gridStart);
    base.setHours(0, 0, 0, 0);

    for (let i = 0; i < 28; i++) {
      const d = new Date(base);
      d.setDate(d.getDate() + i);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, "0");
      const dd = String(d.getDate()).padStart(2, "0");
      const ymd = `${y}-${m}-${dd}`;

      const dow = d.getDay();
      const isToday = ymd === today;
      const past = isPast(ymd);
      const selected = selectedSet.has(ymd);

      arr.push({ ymd, d, dow, isToday, past, selected });
    }
    return arr;
  }, [gridStart, today, selectedSet]);

  return (
    <>
      <style>{css}</style>

      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-lg font-semibold text-gray-800">参加できる日をえらぼう</h1>
          <p className="text-sm text-gray-600 mt-1">
            {child?.full_name}（ID {child?.child_code}）
          </p>
          <p className="text-xs text-gray-500 mt-1">
            日付を押すと「参加する／しない」が切り替わります（過去は選べません）。
          </p>
          <p className="text-xs text-gray-500 mt-1">
            本日の送迎を解除する場合のみ、理由を確認します。
          </p>
        </div>

      </div>

      {siblings.length > 1 && (
        <div className="mt-4 overflow-x-auto">
          <div className="inline-flex items-center gap-2 min-w-max">
            {siblings.map((s) => (
              <a
                key={s.id}
                href={s.availabilityUrl}
                className={`px-3 py-1.5 rounded-full text-xs font-semibold border ${
                  s.isActive
                    ? "bg-indigo-100 border-indigo-300 text-indigo-800"
                    : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                }`}
              >
                {s.name}（{s.grade}年）
              </a>
            ))}
          </div>
        </div>
      )}

      <div className="mt-6 calWrap">
        <div className="calHead">
          <div className="calTitle">
            <div className="calBadge">📅</div>
            <div>
              <div style={{ fontWeight: 900, fontSize: 15, color: "#0f172a" }}>4週間カレンダー</div>
              <div className="calRange">{cal?.rangeLabel}</div>
            </div>
          </div>

          <div className="calBtns">
            <a className="calBtn" href={routes?.home}>メッセージへ</a>
            <a className="calBtn" href={siblings.find((s) => s.isActive)?.qrUrl || "#"}>この児童のQR</a>
            <a className="calBtn" href={routes?.indexPrev}>← 前の4週間</a>
            <a className="calBtn" href={routes?.indexNow}>今週から</a>
            <a className="calBtn" href={routes?.indexNext}>次の4週間 →</a>
          </div>
        </div>

        <div className="calScrollWrap">
          <div className="calScrollInner">
            <div className="dowRow">
              {weekdays.map((w, i) => (
                <div key={i} className={i === 0 ? "dowSun" : i === 6 ? "dowSat" : ""}>{w}</div>
              ))}
            </div>

            <div className="calGrid">
              {days.map((x) => (
                <button
                  key={x.ymd}
                  type="button"
                  className={`dayCell ${x.selected ? "selected" : ""}`}
                  disabled={x.past}
                  onClick={() => handleDayClick(x)}
                >
                  <div className="dayTop">
                    <div className="dayDate">
                      {`${x.d.getMonth() + 1}/${x.d.getDate()}`}
                      <span className="dowText">（{weekdays[x.dow]}）</span>
                    </div>
                    {x.isToday && <div className="todayTag">今日</div>}
                  </div>

                  <div className="dayIconBox">
                    {x.selected ? (
                      <div className="dayIconWrap">
                        {/* ✅ ここが修正点：Laravel asset() で渡したURLを使う */}
                        <img src={attendanceIcon} alt="参加予定" className="dayIcon" />
                      </div>
                    ) : (
                      <span style={{ width: 52, height: 52, display: "block" }} />
                    )}
                  </div>

                  <div className="dayBottom">{x.selected ? "参加" : <>&nbsp;</>}</div>
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="bulkPanel">
          <div className="bulkPanelTitle">🧩 一括操作</div>
          <div className="bulkPanelDesc">未来（今日以降）のみ対象。過去は変更しません。</div>

          <div className="bulkFormRow">
            <div>
              <label className="bulkSelectLabel" htmlFor="bulkAction">操作</label>
              <select id="bulkAction" className="bulkSelect" value={bulkAction} onChange={(e) => setBulkAction(e.target.value)}>
                <option value="on">一括ON</option>
                <option value="off">一括OFF</option>
              </select>
            </div>

            <div>
              <label className="bulkSelectLabel" htmlFor="bulkType">対象</label>
              <select id="bulkType" className="bulkSelect" value={bulkType} onChange={(e) => setBulkType(e.target.value)}>
                <option value="grid_weekdays">この4週間：平日（月〜金）</option>
                <option value="month_weekday">{cal?.monthLabel}：曜日を選んで全て</option>
              </select>
            </div>

            <div>
              <label className="bulkSelectLabel" htmlFor="bulkWeekday">曜日</label>
              <select
                id="bulkWeekday"
                className="bulkSelect"
                value={bulkWeekday}
                disabled={!isMonth}
                onChange={(e) => setBulkWeekday(e.target.value)}
              >
                <option value="1">月曜</option>
                <option value="2">火曜</option>
                <option value="3">水曜</option>
                <option value="4">木曜</option>
                <option value="5">金曜</option>
                <option value="6">土曜</option>
                <option value="0">日曜</option>
              </select>
            </div>

            <div id="bulkApplyWrap">
              <label className="bulkSelectLabel" style={{ opacity: 0 }}>更新</label>
              <button type="button" className={`bulkApplyBtn ${bulkAction === "off" ? "off" : ""}`} onClick={bulkApply}>
                更新
              </button>
            </div>
          </div>
        </div>
      </div>

      {cancelModal.open && (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center">
          <div className="w-full max-w-md rounded-2xl bg-white p-4 shadow-2xl">
            <h2 className="text-base font-bold text-slate-900">本日の送迎を解除しますか？</h2>
            <p className="mt-1 text-xs text-slate-600">
              解除理由は管理者へメッセージとして自動送信されます。
            </p>

            <div className="mt-3 space-y-2">
              {cancelReasonOptions.map((opt) => (
                <label
                  key={opt.value}
                  className={`flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm ${
                    cancelReason === opt.value ? "border-indigo-400 bg-indigo-50" : "border-slate-200 bg-white"
                  }`}
                >
                  <input
                    type="radio"
                    name="cancel_reason"
                    value={opt.value}
                    checked={cancelReason === opt.value}
                    onChange={(e) => setCancelReason(e.target.value)}
                  />
                  <span className="font-medium text-slate-800">{opt.label}</span>
                </label>
              ))}
            </div>

            {cancelReason === "other" && (
              <div className="mt-3">
                <label className="mb-1 block text-xs font-semibold text-slate-700" htmlFor="cancelReasonOther">
                  その他の理由
                </label>
                <textarea
                  id="cancelReasonOther"
                  className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none"
                  rows={3}
                  maxLength={255}
                  value={cancelReasonOther}
                  onChange={(e) => setCancelReasonOther(e.target.value)}
                  placeholder="理由を入力してください"
                />
              </div>
            )}

            <div className="mt-4 flex items-center gap-2">
              <button
                type="button"
                className="flex-1 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                onClick={closeCancelModal}
                disabled={cancelSubmitting}
              >
                戻る
              </button>
              <button
                type="button"
                className="flex-1 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                onClick={submitCancelReason}
                disabled={!canSubmitCancel || cancelSubmitting}
              >
                {cancelSubmitting ? "送信中..." : "解除する"}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className={`fixed top-20 right-4 ${toast.show ? "" : "hidden"} rounded-2xl bg-gray-900 text-white px-4 py-3 text-sm shadow-lg`}>
        {toast.msg}
      </div>
    </>
  );
}

const css = `
.calWrap {
  border-radius: 24px;
  border: 1px solid rgba(99,102,241,.18);
  padding: 18px;
  background: linear-gradient(135deg, #eef2ff 0%, #eff6ff 45%, #fdf2f8 100%);
  display:flex;
  flex-direction:column;
  gap:12px;
}
.calHead {
  display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
}
.calTitle { display:flex; gap:10px; align-items:center; }
.calBadge {
  width: 40px; height: 40px;
  border-radius: 16px;
  background:#fff;
  display:flex; align-items:center; justify-content:center;
  box-shadow: 0 2px 10px rgba(15,23,42,.08);
  font-size:18px;
}
.calRange { font-size:12px; color:#475569; margin-top:2px; }
.calBtns { display:flex; gap:8px; flex-wrap:wrap; }
.calBtn {
  background:#fff; border:0; border-radius:999px;
  padding:10px 14px;
  font-weight:800; font-size:13px;
  color:#334155;
  box-shadow:0 2px 10px rgba(15,23,42,.08);
  cursor:pointer;
  text-decoration:none;
  display:inline-flex; align-items:center; justify-content:center;
}
.calBtn:hover { box-shadow:0 6px 18px rgba(15,23,42,.12); }

.dowRow {
  display:grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap:8px;
  margin-top:14px;
  text-align:center;
  font-weight:900;
  font-size:12px;
  color:#475569;
}
.dowSun { color:#db2777; }
.dowSat { color:#4f46e5; }

.calGrid {
  display:grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap:8px;
  margin-top:8px;
}

.dayCell {
  border-radius:18px;
  border:1px solid rgba(148,163,184,.35);
  background: rgba(255,255,255,.85);
  padding:10px;
  aspect-ratio: 1 / 1;
  cursor:pointer;
  user-select:none;
  position:relative;
  transition: transform .08s ease, box-shadow .12s ease, background .12s ease;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  text-align:center;
  min-width:0;
  overflow:hidden;
}
.dayCell:hover {
  transform: translateY(-1px);
  box-shadow:0 8px 20px rgba(15,23,42,.12);
  background: rgba(255,255,255,.95);
}
.dayCell:disabled {
  opacity:.45;
  cursor:not-allowed;
  box-shadow:none;
  transform:none;
  background: rgba(255,255,255,.6);
}
.dayTop { display:flex; align-items:center; justify-content:space-between; gap:6px; min-width:0; }
.dayDate { font-weight:900; font-size:14px; color:#0f172a; min-width:0; }
.todayTag {
  font-weight:900;
  font-size:10px;
  padding:2px 8px;
  border-radius:999px;
  background:#fef9c3;
  color:#854d0e;
  flex:none;
}

.dayIconBox { min-height:58px; display:flex; align-items:center; justify-content:center; }
.dayIconWrap { border-radius:18px; padding:8px; background: rgba(79,70,229,.12); }
.dayIcon { width:52px; height:52px; object-fit:contain; display:block; }

.dayBottom { font-weight:900; font-size:12px; color:#334155; letter-spacing:.02em; }

.selected {
  background:#4f46e5 !important;
  border-color:#4f46e5 !important;
  color:#fff !important;
  box-shadow:0 10px 26px rgba(79,70,229,.28) !important;
}
.selected .dayDate { color:#fff !important; }
.selected .dayBottom { color: rgba(255,255,255,.95) !important; }
.selected .dayIconWrap { background: rgba(255,255,255,.18) !important; }
.selected .todayTag { background: rgba(255,255,255,.18) !important; color:#fff !important; }

/* 横スクロールは禁止 */
.calScrollWrap { overflow-x: clip; padding-bottom:0; }
.calScrollInner { width:100%; min-width:0; }

/* =========================
   全端末：プルダウン + 更新（統一）
========================= */
.bulkPanel {
  margin-top:6px;
  border-radius:16px;
  border:1px solid rgba(15,23,42,.08);
  background: rgba(255,255,255,.92);
  padding:10px;
  box-shadow:0 2px 10px rgba(15,23,42,.06);
}
.bulkPanelTitle {
  font-weight:900;
  font-size:12px;
  color:#0f172a;
  display:flex;
  align-items:center;
  gap:6px;
}
.bulkPanelDesc {
  font-size:10px;
  color:#64748b;
  font-weight:700;
  margin-top:4px;
}
.bulkFormRow {
  display:grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap:8px;
  margin-top:8px;
  align-items:end;
}
.bulkSelectLabel {
  display:block;
  font-size:10px;
  color:#475569;
  font-weight:800;
  margin-bottom:4px;
}
.bulkSelect {
  width:100%;
  border-radius:12px;
  border:1px solid rgba(148,163,184,.45);
  padding:10px 10px;
  background:#fff;
  font-size:12px;
  font-weight:800;
  color:#0f172a;
  outline:none;
}
.bulkApplyBtn {
  width:100%;
  border:0;
  border-radius:12px;
  padding:11px 12px;
  font-size:12px;
  font-weight:900;
  cursor:pointer;
  background:#0ea5e9;
  color:#fff;
  box-shadow:0 2px 10px rgba(14,165,233,.22);
}
.bulkApplyBtn.off {
  background:#ef4444;
  box-shadow:0 2px 10px rgba(239,68,68,.20);
}
.bulkApplyBtn:disabled {
  opacity:.45;
  cursor:not-allowed;
  box-shadow:none;
}

@media (max-width: 640px) {
  .calWrap { padding:10px; border-radius:18px; }
  .calBadge { width:34px; height:34px; border-radius:14px; font-size:16px; }
  .calRange { font-size:11px; }
  .calBtn { padding:8px 10px; font-size:12px; }

  .calScrollWrap { overflow-x: hidden !important; }
  .calScrollInner { width:100% !important; min-width:0 !important; }

  .dowRow,
  .calGrid { grid-template-columns: repeat(7, minmax(0, 1fr)); gap:4px; width:100%; max-width:100%; }
  .dowRow { margin-top:10px; font-size:10px; }

  .dayCell {
    padding:4px;
    border-radius:10px;
    aspect-ratio:1 / 1;
    min-height:52px;
  }
  .dayDate { font-size:10px; line-height:1.0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .dayDate .dowText { display:none; }
  .todayTag { font-size:8px; padding:1px 5px; }
  .dayIconBox { min-height:26px; }
  .dayIconWrap { padding:3px; border-radius:10px; }
  .dayIcon { width:22px; height:22px; }
  .dayBottom { font-size:9px; line-height:1.0; }
  .dayCell:hover { transform:none; box-shadow:0 4px 10px rgba(15,23,42,.10); }

  /* スマホは縦積み（カレンダーを主役に） */
  .bulkPanel { padding:12px; }
  .bulkFormRow { grid-template-columns: 1fr; gap:10px; }
  .bulkSelect { padding:12px; font-size:13px; border-radius:14px; }
  .bulkApplyBtn { padding:12px 14px; font-size:13px; border-radius:14px; }
}
`;
