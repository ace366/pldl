import React, { useEffect, useMemo, useState, useCallback } from "react";

/**
 * props:
 * - clockInAction: string|null
 * - clockOutAction: string|null
 * - canIn: boolean
 * - canOut: boolean
 * - csrf: string
 */
export default function StaffAttendanceToday({
  clockInAction,
  clockOutAction,
  canIn,
  canOut,
  csrf,
}) {
  const [now, setNow] = useState(() => new Date());
  const [submitting, setSubmitting] = useState(null); // "in" | "out" | null

  // 秒表示なので 1秒更新でOK（省電力）
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(t);
  }, []);

  const timeParts = useMemo(() => {
    const h = String(now.getHours()).padStart(2, "0");
    const m = String(now.getMinutes()).padStart(2, "0");
    const s = String(now.getSeconds()).padStart(2, "0");
    return { h, m, s };
  }, [now]);

  const post = useCallback(
    async (action, kind) => {
      if (!action) return;
      if (submitting) return;

      try {
        setSubmitting(kind);

        const res = await fetch(action, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
          },
          body: new URLSearchParams({ _token: csrf }).toString(),
          redirect: "follow",
        });

        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }

        // 成功 → 画面更新（サーバ側で状態が変わる想定）
        window.location.reload();
      } catch (e) {
        console.error(e);
        alert("送信に失敗しました。通信状況を確認して、もう一度お試しください。");
        setSubmitting(null);
      }
    },
    [csrf, submitting]
  );

  const SquareButton = ({ kind, label, emoji, enabled, action }) => {
    const disabled = !enabled || !action || submitting !== null;
    const isBusy = submitting === kind;

    const base =
      "w-full aspect-square rounded-2xl border shadow-sm flex flex-col items-center justify-center gap-2 font-extrabold select-none";
    const cls = disabled
      ? `${base} bg-slate-200 border-slate-200 text-slate-500 cursor-not-allowed`
      : kind === "in"
      ? `${base} bg-emerald-600 border-emerald-700 text-white hover:bg-emerald-700 active:scale-[0.99]`
      : `${base} bg-indigo-600 border-indigo-700 text-white hover:bg-indigo-700 active:scale-[0.99]`;

    return (
      <button
        type="button"
        onClick={() => post(action, kind)}
        disabled={disabled}
        className={cls}
        aria-busy={isBusy ? "true" : "false"}
      >
        <div className="text-3xl leading-none">{isBusy ? "⏳" : emoji}</div>
        <div className="text-base leading-tight text-center">{label}</div>
      </button>
    );
  };

  // 状態メッセージ（最小限・現場向け）
  const hint = useMemo(() => {
    if (!clockInAction || !clockOutAction) {
      return "勤怠レコードが無い場合は打刻できません（管理者へ連絡）";
    }
    if (!canIn && !canOut) {
      return "打刻できる状態ではありません（シフト/打刻状況を確認）";
    }
    return "※押したら自動で更新されます";
  }, [clockInAction, clockOutAction, canIn, canOut]);

  return (
    <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
      <div className="p-4 sm:p-6">
        {/* ✅ 時刻：中央に大きく、秒だけ小さく */}
        <div className="flex flex-col items-center justify-center text-center">
          <div className="text-[11px] sm:text-xs text-slate-500 font-semibold">
            現在時刻
          </div>

          <div className="mt-1 flex items-baseline justify-center">
            <div className="text-6xl sm:text-7xl font-extrabold tracking-tight text-slate-900 leading-none">
              {timeParts.h}:{timeParts.m}
            </div>
            <div className="ms-2 text-2xl sm:text-3xl font-extrabold text-slate-600 leading-none">
              {timeParts.s}
            </div>
          </div>
        </div>

        {/* ✅ すぐ下に出勤/退勤（スマホでも横並び） */}
        <div className="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
          <SquareButton
            kind="in"
            label="出勤"
            emoji="✅"
            enabled={!!canIn}
            action={clockInAction}
          />
          <SquareButton
            kind="out"
            label="退勤"
            emoji="🏁"
            enabled={!!canOut}
            action={clockOutAction}
          />
        </div>

        <div className="mt-3 text-center text-xs text-slate-500">{hint}</div>
      </div>
    </div>
  );
}
