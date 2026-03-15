import React, { useEffect, useMemo, useState } from "react";

function formatTimeJST(iso) {
  if (!iso) return "";
  try {
    const d = new Date(iso);
    return d.toLocaleTimeString("ja-JP", {
      timeZone: "Asia/Tokyo",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return "";
  }
}

function cls(...xs) {
  return xs.filter(Boolean).join(" ");
}

function todayJst() {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Tokyo",
  }).format(new Date());
}

export default function AdminAttendanceIntents(props) {
  const {
    date: initialDate,
    apiSummary,
    apiTogglePickup,
    apiToggleManual,
    csrf,
    carImg,
    ccarImg,
    canEdit: canEditProp,
  } = props;

  const canEdit = canEditProp !== false && canEditProp !== "0";

  const [date, setDate] = useState(initialDate || "");
  const isTodayView = date === todayJst();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [data, setData] = useState(null);

  // フィルタ（現場最頻）
  const [pickupOnly, setPickupOnly] = useState(false); // デフォルトONが現場向き
  const [notArrivedOnly, setNotArrivedOnly] = useState(true);

  // 学校の開閉
  const [openSet, setOpenSet] = useState(() => new Set()); // デフォルト全部閉じる

  const fetchSummary = async (targetDate) => {
    setLoading(true);
    setError("");
    try {
      const url = new URL(apiSummary, window.location.origin);
      if (targetDate) url.searchParams.set("date", targetDate);

      const res = await fetch(url.toString(), {
        headers: { "Accept": "application/json" },
        credentials: "same-origin",
      });
      if (!res.ok) throw new Error(`summary failed: ${res.status}`);
      const json = await res.json();
      setData(json);
    } catch (e) {
      setError("読み込みに失敗しました。再読み込みしてください。");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSummary(date);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const schools = useMemo(() => {
    if (!data?.schools) return [];

    return data.schools.map((s) => {
      let children = Array.isArray(s.children) ? [...s.children] : [];

      // ✅ フィルタ
      if (pickupOnly) children = children.filter((c) => !!c.pickup_required);
      if (notArrivedOnly) children = children.filter((c) => !c.arrived);

      // ✅ 並び替え：未到着を最上段へ（最速）
      // 1) arrived=false を先頭
      // 2) 次に pickup_confirmed=false を先頭（未チェックが先）
      // 3) 最後に名前
      children.sort((a, b2) => {
        const aKey1 = a.arrived ? 1 : 0;
        const bKey1 = b2.arrived ? 1 : 0;
        if (aKey1 !== bKey1) return aKey1 - bKey1;

        const aKey2 = a.pickup_confirmed ? 1 : 0;
        const bKey2 = b2.pickup_confirmed ? 1 : 0;
        if (aKey2 !== bKey2) return aKey2 - bKey2;

        return (a.child_name || "").localeCompare(b2.child_name || "", "ja");
      });

      return { ...s, children };
    });
  }, [data, pickupOnly, notArrivedOnly]);

  const toggleSchool = (schoolId) => {
    setOpenSet((prev) => {
      const s = new Set(prev);
      const key = String(schoolId ?? "null");
      if (s.has(key)) s.delete(key);
      else s.add(key);
      return s;
    });
  };

  const isOpen = (schoolId) => openSet.has(String(schoolId ?? "null"));

  const postJson = async (url, body) => {
    if (!url) throw new Error("POST url missing");
    const res = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": csrf,
      },
      body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`POST failed: ${res.status}`);
    return await res.json();
  };

  const onTogglePickup = async (intentId) => {
    if (!canEdit || !apiTogglePickup) return;
    // 楽観更新（体感速度UP）
    setData((prev) => {
      if (!prev?.schools) return prev;
      const next = { ...prev, schools: prev.schools.map((s) => ({ ...s, children: [...s.children] })) };
      for (const s of next.schools) {
        const idx = s.children.findIndex((c) => c.intent_id === intentId);
        if (idx >= 0) {
          const c = s.children[idx];
          s.children[idx] = {
            ...c,
            pickup_confirmed: !c.pickup_confirmed,
            pickup_confirmed_at: !c.pickup_confirmed ? new Date().toISOString() : null,
          };
          break;
        }
      }
      return next;
    });

    try {
      const out = await postJson(apiTogglePickup, { intent_id: intentId });
      // 正式反映
      setData((prev) => {
        if (!prev?.schools) return prev;
        const next = { ...prev, schools: prev.schools.map((s) => ({ ...s, children: [...s.children] })) };
        for (const s of next.schools) {
          const idx = s.children.findIndex((c) => c.intent_id === intentId);
          if (idx >= 0) {
            const c = s.children[idx];
            s.children[idx] = {
              ...c,
              pickup_confirmed: !!out.pickup_confirmed,
              pickup_confirmed_at: out.pickup_confirmed_at || null,
            };
            break;
          }
        }
        return next;
      });
    } catch {
      // 失敗時は再取得で戻す
      fetchSummary(date);
    }
  };

  const onSetManual = async (intentId, status) => {
    if (!canEdit || !apiToggleManual) return;
    if (status === "not_arrived" && isTodayView && !window.confirm("この児童を欠席に変更しますか？")) {
      return;
    }
    try {
      await postJson(apiToggleManual, { intent_id: intentId, manual_status: status });
      // arrived 表示は summary 再計算が必要なので素直に再取得（確実性重視）
      fetchSummary(date);
    } catch {
      fetchSummary(date);
    }
  };

  const onChangeDate = async (nextDate) => {
    setDate(nextDate);
    await fetchSummary(nextDate);
    // 日付変わったら開閉は維持（現場で学校を開いて連続チェックが多い）
  };

  return (
    <div className="space-y-4">
      {/* 上部：片手オペ用の“太い”操作バー */}
      <div className="sticky top-0 z-10 bg-white/95 backdrop-blur border rounded-xl p-3 shadow-sm">
        <div className="flex items-center gap-2">
          <input
            type="date"
            value={date}
            onChange={(e) => onChangeDate(e.target.value)}
            className="border rounded-lg px-3 py-2 text-sm w-[160px]"
          />

          <button
            type="button"
            onClick={() => fetchSummary(date)}
            className="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50 text-sm"
          >
            再読み込み
          </button>
        </div>

        <div className="mt-3 grid grid-cols-2 gap-2">
          <button
            type="button"
            onClick={() => setPickupOnly((v) => !v)}
            className={cls(
              "rounded-xl px-3 py-3 text-sm font-extrabold border transition",
              pickupOnly
                ? "bg-indigo-600 border-indigo-700 text-white shadow"
                : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
            )}
          >
            送迎のみ {pickupOnly ? "ON" : "OFF"}
          </button>

          <button
            type="button"
            onClick={() => setNotArrivedOnly((v) => !v)}
            className={cls(
              "rounded-xl px-3 py-3 text-sm font-extrabold border transition",
              notArrivedOnly
                ? "bg-red-600 border-red-700 text-white shadow"
                : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
            )}
          >
            未到着のみ {notArrivedOnly ? "ON" : "OFF"}
          </button>
        </div>

        {loading && (
          <div className="mt-2 text-xs text-gray-500">読み込み中…</div>
        )}
        {error && (
          <div className="mt-2 text-xs text-red-600 font-semibold">{error}</div>
        )}
      </div>

      {/* 学校一覧（デフォルト折りたたみ） */}
      <div className="space-y-3">
        {schools.map((s) => {
          const open = isOpen(s.school_id);
          const key = String(s.school_id ?? "null");
          const count = s.children?.length ?? 0;

          return (
            <div key={key} className="border rounded-2xl overflow-hidden">
              {/* ヘッダ（タップで開閉） */}
              <button
                type="button"
                onClick={() => toggleSchool(s.school_id)}
                className="w-full text-left px-4 py-4 bg-gray-50 hover:bg-gray-100 transition"
              >
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <div className="font-extrabold text-base text-gray-900 truncate">
                      {s.school_name}
                    </div>
                    <div className="text-xs text-gray-600 mt-1">
                      参加予定 <b>{s.total}</b> / 送迎 <b>{s.pickup}</b> / 車 <b className="text-indigo-700">{s.cars}</b>
                    </div>
                  </div>

                  <div className={cls(
                    "shrink-0 text-xs font-extrabold px-3 py-2 rounded-xl border",
                    open ? "bg-white border-gray-300" : "bg-white border-gray-300"
                  )}>
                    {open ? "閉じる" : `表示(${count})`}
                  </div>
                </div>
              </button>

              {/* 中身 */}
              {open && (
                <div className="p-3 space-y-3 bg-white">
                  {count === 0 ? (
                    <div className="text-sm text-gray-500 py-8 text-center">
                      対象者がいません
                    </div>
                  ) : (
                    s.children.map((c) => {
                      const arrived = !!c.arrived;
                      const pickupConfirmed = !!c.pickup_confirmed;
                      const isAbsent = isTodayView && String(c.manual_status || "") === "not_arrived";

                      return (
                        <div
                          key={c.intent_id}
                          className={cls(
                            "rounded-2xl border p-3",
                            isAbsent || !arrived ? "bg-red-50 border-red-200" : "bg-white border-gray-200"
                          )}
                        >
                          {/* 名前：1行固定（縦伸び防止） */}
                          <div className="flex items-center justify-between gap-2">
                            <div className="min-w-0">
                              <div className="font-extrabold text-base text-gray-900 truncate">
                                {c.child_name || "（氏名不明）"}
                              </div>
                              <div className="text-[11px] text-gray-600 truncate">
                                {c.child_name_kana || "—"}
                              </div>

                              <div className="mt-1 flex items-center gap-2">
                                <span className={cls(
                                  "text-[11px] px-2 py-0.5 rounded-full border font-semibold",
                                  isAbsent
                                    ? "bg-red-100 border-red-300 text-red-800"
                                    : arrived
                                    ? "bg-emerald-50 border-emerald-200 text-emerald-800"
                                    : "bg-red-100 border-red-200 text-red-800"
                                )}>
                                  {isAbsent ? "欠席" : (arrived ? "出席済" : "未到着")}
                                </span>

                                {c.manual_status ? (
                                  <span className="text-[11px] px-2 py-0.5 rounded-full bg-yellow-100 border border-yellow-300 text-yellow-800 font-semibold">
                                    手動
                                  </span>
                                ) : (
                                  <span className="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 font-semibold">
                                    自動
                                  </span>
                                )}
                              </div>
                            </div>

                            {/* 右上：送迎済時刻（日本時間） */}
                            <div className="shrink-0 text-right">
                              {pickupConfirmed && c.pickup_confirmed_at ? (
                                <div className="text-xs text-gray-700 font-bold">
                                  {formatTimeJST(c.pickup_confirmed_at)}
                                </div>
                              ) : (
                                <div className="text-xs text-gray-400"> </div>
                              )}
                            </div>
                          </div>

                          {/* 操作ボタン：下にまとめる（片手＆縦長対策） */}
                          <div className="mt-3 grid grid-cols-2 gap-2">
                            {/* 車：親指サイズ（最重要） */}
                            <button
                              type="button"
                              onClick={() => onTogglePickup(c.intent_id)}
                              disabled={!canEdit || isAbsent}
                              className={cls(
                                "rounded-2xl border px-3 py-3 flex items-center justify-center gap-2 transition active:scale-[0.99]",
                                isAbsent
                                  ? "bg-gray-100 border-gray-200 text-gray-400"
                                  : pickupConfirmed
                                  ? "bg-indigo-600 border-indigo-700 text-white shadow"
                                  : "bg-orange-50 border-orange-300 text-orange-900 hover:bg-orange-100",
                                (!canEdit || isAbsent) && "cursor-not-allowed opacity-70"
                              )}
                              aria-label="乗車確認"
                            >
                              <img
                                src={pickupConfirmed ? ccarImg : carImg}
                                alt={pickupConfirmed ? "済" : "未"}
                                className={cls("w-7 h-7 object-contain", pickupConfirmed ? "" : "opacity-70")}
                              />
                              <div className="text-sm font-extrabold">
                                {pickupConfirmed ? "送迎：済" : "送迎：未"}
                              </div>
                            </button>

                            {/* 到着ステータス：必要時のみ触る想定（小さすぎない） */}
                            <div className="grid grid-cols-3 gap-2">
                              <button
                                type="button"
                                onClick={() => onSetManual(c.intent_id, "arrived")}
                                disabled={!canEdit}
                                className={cls(
                                  "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                  c.manual_status === "arrived"
                                    ? "bg-emerald-100 border-emerald-300 text-emerald-900"
                                    : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                  !canEdit && "cursor-not-allowed opacity-70"
                                )}
                              >
                                出席
                              </button>
                              <button
                                type="button"
                                onClick={() => onSetManual(c.intent_id, "not_arrived")}
                                disabled={!canEdit}
                                className={cls(
                                  "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                  c.manual_status === "not_arrived"
                                    ? "bg-red-100 border-red-300 text-red-900"
                                    : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                  !canEdit && "cursor-not-allowed opacity-70"
                                )}
                              >
                                {isTodayView ? "欠席" : "未到着"}
                              </button>
                              <button
                                type="button"
                                onClick={() => onSetManual(c.intent_id, "auto")}
                                disabled={!canEdit}
                                className={cls(
                                  "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                  !c.manual_status
                                    ? "bg-gray-100 border-gray-300 text-gray-800"
                                    : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                  !canEdit && "cursor-not-allowed opacity-70"
                                )}
                              >
                                自動
                              </button>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>

      <div className="text-xs text-gray-500">
        ※ 未到着が自動で上に来ます。送迎のみONが現場向けデフォルトです。
      </div>
    </div>
  );
}
