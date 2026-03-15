// resources/js/pages/staff-attendance-today.jsx
import React, { useEffect, useMemo, useState } from "react";
import { createRoot } from "react-dom/client";

function pad2(n) {
  return String(n).padStart(2, "0");
}

function NowClock() {
  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 250);
    return () => clearInterval(id);
  }, []);

  const hh = pad2(now.getHours());
  const mm = pad2(now.getMinutes());
  const ss = pad2(now.getSeconds());

  return (
    <div className="text-center">
      <div className="text-xs text-slate-500">現在時刻</div>
      <div className="mt-1 font-extrabold text-slate-900 leading-none tabular-nums">
        <span className="text-5xl sm:text-6xl">{hh}:{mm}</span>
        <span className="text-2xl sm:text-3xl align-baseline text-slate-600 ml-1">{ss}</span>
      </div>
    </div>
  );
}

function SquareActionButton({
  label,
  emoji,
  enabled,
  onClick,
  tone = "emerald",
}) {
  const toneMap = {
    emerald: "bg-emerald-600 hover:bg-emerald-700",
    indigo: "bg-indigo-600 hover:bg-indigo-700",
    slate: "bg-slate-200",
  };

  const cls = enabled
    ? `${toneMap[tone]} text-white`
    : "bg-slate-200 text-slate-500 cursor-not-allowed";

  return (
    <button
      type="button"
      disabled={!enabled}
      onClick={enabled ? onClick : undefined}
      className={[
        "aspect-square w-full rounded-2xl shadow-sm",
        "flex flex-col items-center justify-center gap-1",
        "font-extrabold active:scale-[0.99] transition",
        cls,
      ].join(" ")}
    >
      <div className="text-2xl leading-none">{emoji}</div>
      <div className="text-sm leading-none">{label}</div>
    </button>
  );
}

function Page(props) {
  const [success, setSuccess] = useState(props.flashSuccess || "");
  const [error, setError] = useState(props.flashError || "");
  const [info, setInfo] = useState(props.message || "");
  const [busy, setBusy] = useState(false);

  const statusBadge = useMemo(() => {
    return props.statusLabel || "未設定";
  }, [props.statusLabel]);

  async function post(url) {
    if (!url) return;
    setBusy(true);
    setSuccess("");
    setError("");

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": props.csrf,
          "X-Requested-With": "XMLHttpRequest",
          "Accept": "application/json",
        },
        body: new URLSearchParams({}), // 追加パラメータ無し
      });

      // Laravelがリダイレクトを返す場合もあるので、最終的に再読込が安全
      if (res.redirected) {
        window.location.href = res.url;
        return;
      }

      // JSONで返す実装もあり得るので両対応
      const ct = res.headers.get("content-type") || "";
      if (ct.includes("application/json")) {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          setError(data?.message || "失敗しました。");
        } else {
          setSuccess(data?.message || "完了しました。");
          window.location.reload();
        }
        return;
      }

      // HTML等ならリロード（従来運用に合わせる）
      if (res.ok) {
        window.location.reload();
      } else {
        setError("失敗しました。");
      }
    } catch (e) {
      setError("通信エラーが発生しました。");
    } finally {
      setBusy(false);
    }
  }

  const canIn = !!props.canIn && !!props.clockInAction && !busy;
  const canOut = !!props.canOut && !!props.clockOutAction && !busy;

  return (
    <div className="space-y-3 sm:space-y-4">

      {/* メッセージ */}
      {success ? (
        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 font-semibold">
          ✅ {success}
        </div>
      ) : null}
      {error ? (
        <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 font-semibold">
          ⚠️ {error}
        </div>
      ) : null}
      {info ? (
        <div className="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-indigo-800 font-semibold">
          ℹ️ {info}
        </div>
      ) : null}

      {/* カード */}
      <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
        {/* 上部 */}
        <div className="p-4 sm:p-5 border-b border-slate-100">
          <div className="flex items-start justify-between gap-4">
            <div>
              <div className="text-xs text-slate-500">きょう</div>
              <div className="text-2xl font-extrabold text-slate-900">{props.date}</div>
              <div className="mt-1 text-sm text-slate-600">
                会場：<span className="font-bold">{props.baseName}</span>
              </div>
            </div>

            <div className="text-right">
              <div className="text-xs text-slate-500">あなた</div>
              <div className="text-lg font-extrabold">{props.staffName}</div>
            </div>
          </div>
        </div>

        {/* 本文 */}
        <div className="p-4 sm:p-5 space-y-4">

          {/* ✅ 現在時刻 + その下にボタン（要望） */}
          <div className="rounded-2xl border border-slate-200 bg-white px-4 py-4">
            <NowClock />

            {/* 状態バッジ */}
            <div className="mt-3 flex justify-center">
              <span className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700">
                状態：{statusBadge}
              </span>
            </div>

            {/* ✅ ボタン：スマホも横並び / 正方形 */}
            <div className="mt-4 grid grid-cols-2 gap-3">
              <SquareActionButton
                label="出勤"
                emoji={busy ? "⏳" : "✅"}
                enabled={canIn}
                tone="emerald"
                onClick={() => post(props.clockInAction)}
              />
              <SquareActionButton
                label="退勤"
                emoji={busy ? "⏳" : "🏁"}
                enabled={canOut}
                tone="indigo"
                onClick={() => post(props.clockOutAction)}
              />
            </div>

            {/* 注釈（必要最低限） */}
            <div className="mt-3 text-xs text-slate-500 text-center leading-relaxed">
              {(!props.clockInAction || !props.clockOutAction) ? "※勤怠レコードが無い場合は打刻できません（管理者へ）" : "※間違えた場合は管理者へ連絡してください。"}
            </div>
          </div>

          {/* 打刻状況（表示は残す） */}
          <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
            <div className="text-sm font-bold text-slate-800 mb-2">✅ 打刻状況</div>

            <div className="grid grid-cols-2 gap-3 text-sm">
              <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
                <div className="text-xs text-slate-500">出勤</div>
                <div className={"text-2xl font-extrabold " + (props.clockIn ? "text-emerald-700" : "text-slate-700")}>
                  {props.clockIn || "未"}
                </div>
              </div>

              <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
                <div className="text-xs text-slate-500">退勤</div>
                <div className={"text-2xl font-extrabold " + (props.clockOut ? "text-indigo-700" : "text-slate-700")}>
                  {props.clockOut || "未"}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  );
}

// マウント
const el = document.getElementById("staff-attendance-today-root");
if (el) {
  const props = JSON.parse(el.getAttribute("data-props") || "{}");
  createRoot(el).render(<Page {...props} />);
}
