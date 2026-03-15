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

export default function AdminTodayParticipants(props) {
  const {
    date: initialDate,
    apiSummary,
    apiTogglePickup,
    apiToggleManual,
    apiCheckout,
    csrf,
    carImg,
    ccarImg,
    canEdit: canEditProp,
  } = props;

  const canEdit = canEditProp !== false && canEditProp !== "0";

  const [date, setDate] = useState(initialDate || "");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [data, setData] = useState(null);

  const fetchSummary = async (targetDate) => {
    setLoading(true);
    setError("");
    try {
      const url = new URL(apiSummary, window.location.origin);
      if (targetDate) url.searchParams.set("date", targetDate);

      const res = await fetch(url.toString(), {
        headers: { Accept: "application/json" },
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
    return data.schools.map((s) => ({
      ...s,
      children: Array.isArray(s.children) ? [...s.children] : [],
    }));
  }, [data]);

  const postJson = async (url, body) => {
    if (!url) throw new Error("POST url missing");
    const res = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrf,
      },
      body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`POST failed: ${res.status}`);
    return await res.json();
  };

  const onTogglePickup = async (intentId) => {
    if (!canEdit || !apiTogglePickup || !intentId) return;
    try {
      await postJson(apiTogglePickup, { intent_id: intentId });
      fetchSummary(date);
    } catch {
      fetchSummary(date);
    }
  };

  const onSetManual = async (intentId, status) => {
    if (!canEdit || !apiToggleManual || !intentId) return;
    try {
      await postJson(apiToggleManual, { intent_id: intentId, manual_status: status });
      fetchSummary(date);
    } catch {
      fetchSummary(date);
    }
  };

  const onCheckout = async (childId) => {
    if (!canEdit || !apiCheckout || !childId) return;
    const url = apiCheckout.replace(/\/0(\/?)/, `/${childId}$1`);
    try {
      await postJson(url, { date });
      setData((prev) => {
        if (!prev?.schools) return prev;
        const next = {
          ...prev,
          schools: prev.schools.map((s) => ({ ...s, children: [...s.children] })),
        };
        for (const s of next.schools) {
          const idx = s.children.findIndex((c) => c.child_id === childId);
          if (idx >= 0) {
            const c = s.children[idx];
            s.children[idx] = {
              ...c,
              state: "checked_out",
              checked_out: true,
            };
            break;
          }
        }
        return next;
      });
    } catch {
      fetchSummary(date);
    }
  };

  const onChangeDate = async (nextDate) => {
    setDate(nextDate);
    await fetchSummary(nextDate);
  };

  return (
    <div className="space-y-4">
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

        {loading && <div className="mt-2 text-xs text-gray-500">読み込み中…</div>}
        {error && <div className="mt-2 text-xs text-red-600 font-semibold">{error}</div>}
      </div>

      <div className="space-y-3">
        {schools.map((s) => {
          const key = String(s.school_id ?? "null");
          const children = s.children || [];
          const count = children.length;
          const attendingCount = children.filter((c) => String(c.state || "") === "attending").length;
          const pickupActiveCount = children.filter((c) => String(c.state || "") === "pickup").length;
          const checkedOutCount = children.filter((c) => String(c.state || "") === "checked_out").length;

          return (
            <div key={key} className="border rounded-2xl overflow-hidden">
              <div className="w-full text-left px-4 py-4 bg-gray-50">
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <div className="font-extrabold text-base text-gray-900 truncate">
                      {s.school_name}
                    </div>
                    <div className="text-xs text-gray-600 mt-1">
                      送迎中 <b>{pickupActiveCount}</b> / 参加中 <b>{attendingCount}</b> / 帰宅{" "}
                      <b>{checkedOutCount}</b>
                    </div>
                  </div>
                  <div className="shrink-0 text-xs font-extrabold px-3 py-2 rounded-xl border bg-white border-gray-300">
                    {count} 名
                  </div>
                </div>
              </div>

              <div className="p-3 space-y-3 bg-white">
                {count === 0 ? (
                  <div className="text-sm text-gray-500 py-8 text-center">対象者がいません</div>
                ) : (
                  children.map((c) => {
                    const state = String(c.state || "");
                    const resolvedState = state || (c.checked_out ? "checked_out" : c.arrived ? "attending" : c.pickup_confirmed ? "pickup" : "registered");
                    const pickupRequired = !!c.pickup_required;
                    const pickupConfirmed = !!c.pickup_confirmed;
                    const checkedOut = resolvedState === "checked_out";

                    return (
                      <div
                        key={c.child_id}
                        className={cls(
                          "rounded-2xl border p-3",
                          resolvedState === "pickup" && "bg-amber-50 border-amber-200",
                          resolvedState === "attending" && "bg-emerald-50 border-emerald-200",
                          resolvedState === "checked_out" && "bg-gray-50 border-gray-300",
                          (!resolvedState || resolvedState === "registered") && "bg-slate-50 border-slate-200"
                        )}
                      >
                        <div className="flex items-center justify-between gap-2">
                          <div className="min-w-0">
                            <div className="font-extrabold text-base text-gray-900 truncate">
                              {c.child_name || "（氏名不明）"}
                            </div>

                            <div className="mt-1 flex items-center gap-2">
                              {resolvedState === "pickup" ? (
                                <span className="text-[11px] px-2 py-0.5 rounded-full border font-semibold bg-amber-100 border-amber-300 text-amber-800">
                                  送迎中
                                </span>
                              ) : checkedOut ? (
                                <span className="text-[11px] px-2 py-0.5 rounded-full border font-semibold bg-gray-100 border-gray-300 text-gray-700">
                                  帰宅済
                                </span>
                              ) : resolvedState === "attending" ? (
                                <span className="text-[11px] px-2 py-0.5 rounded-full border font-semibold bg-emerald-50 border-emerald-200 text-emerald-800">
                                  参加中
                                </span>
                              ) : (
                                <span className="text-[11px] px-2 py-0.5 rounded-full border font-semibold bg-slate-100 border-slate-300 text-slate-700">
                                  登録済
                                </span>
                              )}

                              <span className={cls(
                                "text-[11px] px-2 py-0.5 rounded-full border font-semibold",
                                pickupRequired
                                  ? "bg-indigo-50 border-indigo-300 text-indigo-700"
                                  : "bg-slate-100 border-slate-300 text-slate-600"
                              )}>
                                {pickupRequired ? "送迎対象" : "送迎対象外"}
                              </span>
                            </div>
                          </div>

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

                        <div className="mt-3 grid grid-cols-2 gap-2">
                          <button
                            type="button"
                            onClick={() => onTogglePickup(c.intent_id)}
                            disabled={!canEdit || !c.intent_id}
                            className={cls(
                              "rounded-2xl border px-3 py-3 flex items-center justify-center gap-2 transition active:scale-[0.99]",
                              pickupConfirmed
                                ? "bg-indigo-600 border-indigo-700 text-white shadow"
                                : "bg-orange-50 border-orange-300 text-orange-900 hover:bg-orange-100",
                              (!canEdit || !c.intent_id) && "cursor-not-allowed opacity-70"
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

                          <div className="grid grid-cols-3 gap-2">
                            <button
                              type="button"
                              onClick={() => onSetManual(c.intent_id, "arrived")}
                              disabled={!canEdit || !c.intent_id}
                              className={cls(
                                "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                c.manual_status === "arrived"
                                  ? "bg-emerald-100 border-emerald-300 text-emerald-900"
                                  : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                (!canEdit || !c.intent_id) && "cursor-not-allowed opacity-70"
                              )}
                            >
                              出席
                            </button>
                            <button
                              type="button"
                              onClick={() => onSetManual(c.intent_id, "not_arrived")}
                              disabled={!canEdit || !c.intent_id}
                              className={cls(
                                "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                c.manual_status === "not_arrived"
                                  ? "bg-red-100 border-red-300 text-red-900"
                                  : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                (!canEdit || !c.intent_id) && "cursor-not-allowed opacity-70"
                              )}
                            >
                              未到着
                            </button>
                            <button
                              type="button"
                              onClick={() => onSetManual(c.intent_id, "auto")}
                              disabled={!canEdit || !c.intent_id}
                              className={cls(
                                "rounded-xl border px-2 py-3 text-xs font-extrabold",
                                !c.manual_status
                                  ? "bg-gray-100 border-gray-300 text-gray-800"
                                  : "bg-white border-gray-300 text-gray-700 hover:bg-gray-50",
                                (!canEdit || !c.intent_id) && "cursor-not-allowed opacity-70"
                              )}
                            >
                              自動
                            </button>
                          </div>
                        </div>

                        <div className="mt-2">
                          <button
                            type="button"
                            onClick={() => onCheckout(c.child_id)}
                            disabled={!canEdit || checkedOut}
                              className={cls(
                                "w-full rounded-2xl border px-3 py-3 text-sm font-extrabold transition",
                                checkedOut
                                  ? "bg-gray-200 border-gray-300 text-gray-600"
                                : "bg-rose-600 border-rose-700 text-white shadow hover:bg-rose-700",
                              (!canEdit || checkedOut) && "cursor-not-allowed opacity-70"
                            )}
                          >
                            {checkedOut ? "帰宅済" : "帰宅"}
                          </button>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          );
        })}
      </div>

      <div className="text-xs text-gray-500">
        ※ 日付ごとに履歴を保持して表示します。状態は送迎中 / 参加中 / 帰宅で確認できます。
      </div>
    </div>
  );
}
