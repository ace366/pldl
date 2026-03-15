import React, { useMemo, useState } from "react";

function firstError(errors, key) {
  const arr = errors?.[key];
  return Array.isArray(arr) && arr.length ? String(arr[0]) : "";
}

export default function SiblingsPage(props) {
  const siblings = Array.isArray(props?.siblings) ? props.siblings : [];
  const schools = Array.isArray(props?.schools) ? props.schools : [];
  const bases = Array.isArray(props?.bases) ? props.bases : [];
  const routes = props?.routes || {};
  const csrf = props?.csrf || "";
  const old = props?.old || {};
  const errors = props?.errors || {};
  const loginCode = props?.loginCode || "";

  const hasFormError = useMemo(
    () =>
      [
        "last_name",
        "first_name",
        "last_name_kana",
        "first_name_kana",
        "school_id",
        "grade",
        "birth_date",
        "base_id",
        "has_allergy",
        "allergy_note",
        "note",
      ].some((k) => firstError(errors, k)),
    [errors]
  );

  const [tab, setTab] = useState(hasFormError ? "form" : "list");
  const initialGrade = String(old?.grade || "1");
  const [grade, setGrade] = useState(initialGrade);
  const [birthDate, setBirthDate] = useState(() => {
    const oldBirthDate = String(old?.birth_date || "");
    if (oldBirthDate.trim() !== "") return oldBirthDate;

    const schoolYear = (() => {
      const now = new Date();
      return now.getMonth() + 1 < 4 ? now.getFullYear() - 1 : now.getFullYear();
    })();
    const g = Number.parseInt(initialGrade, 10);
    if (!Number.isInteger(g) || g < 1 || g > 6) return "";
    return `${schoolYear - (g + 6)}-07-01`;
  });
  const [allergyFlag, setAllergyFlag] = useState(String(old?.has_allergy ?? "0"));
  const [allergyNote, setAllergyNote] = useState(String(old?.allergy_note || ""));

  const hasAllergy = allergyFlag === "1";

  function getSchoolYear(today = new Date()) {
    const y = today.getFullYear();
    const m = today.getMonth() + 1;
    return m < 4 ? y - 1 : y;
  }

  function estimatedBirthYear(gradeValue) {
    const g = Number.parseInt(String(gradeValue || ""), 10);
    if (!Number.isInteger(g) || g < 1 || g > 6) return null;
    return getSchoolYear(new Date()) - (g + 6);
  }

  function applyBirthYearByGrade(gradeValue, currentBirthDate) {
    const y = estimatedBirthYear(gradeValue);
    if (!y) return currentBirthDate;

    const matched = String(currentBirthDate || "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (matched) {
      return `${y}-${matched[2]}-${matched[3]}`;
    }

    return `${y}-07-01`;
  }

  return (
    <div className="space-y-4">
      <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div className="text-sm font-semibold text-emerald-900">きょうだい登録</div>
        <div className="text-xs text-emerald-800 mt-1">
          ログインID <span className="font-mono font-bold">{loginCode || "----"}</span> で利用できるきょうだいを管理します。
        </div>
      </div>

      <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
        <button
          type="button"
          onClick={() => setTab("list")}
          className={`px-4 py-2 text-sm font-semibold rounded-lg ${
            tab === "list" ? "bg-white shadow text-gray-900" : "text-gray-600"
          }`}
        >
          きょうだい一覧
        </button>
        <button
          type="button"
          onClick={() => setTab("form")}
          className={`px-4 py-2 text-sm font-semibold rounded-lg ${
            tab === "form" ? "bg-white shadow text-gray-900" : "text-gray-600"
          }`}
        >
          きょうだいを登録
        </button>
      </div>

      {tab === "list" && (
        <div className="space-y-3">
          {siblings.length === 0 && (
            <div className="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">
              まだきょうだいが登録されていません。
            </div>
          )}
          {siblings.map((s) => (
            <div key={s.id} className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
              <div className="text-sm font-bold text-gray-900">{s.name}</div>
              <div className="mt-1 text-xs text-gray-600">{s.kana || "ふりがな未設定"}</div>
              <div className="mt-1 text-xs text-gray-600">
                {s.school} / {s.grade}年 / {s.base}
              </div>
              <div className="mt-3 flex gap-2">
                <a
                  href={s.qrUrl}
                  className="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700"
                >
                  QRを表示
                </a>
                <a
                  href={s.availabilityUrl}
                  className="inline-flex items-center rounded-full bg-indigo-50 border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700"
                >
                  送迎を登録
                </a>
              </div>
            </div>
          ))}
        </div>
      )}

      {tab === "form" && (
        <form method="POST" action={routes.store} className="space-y-4">
          <input type="hidden" name="_token" value={csrf} />

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700">姓（漢字）</label>
              <input name="last_name" defaultValue={old?.last_name || ""} required className="mt-1 block w-full rounded-md border-gray-300" />
              {firstError(errors, "last_name") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "last_name")}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">名（漢字）</label>
              <input name="first_name" defaultValue={old?.first_name || ""} required className="mt-1 block w-full rounded-md border-gray-300" />
              {firstError(errors, "first_name") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "first_name")}</p>}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700">せい（かな）</label>
              <input name="last_name_kana" defaultValue={old?.last_name_kana || ""} className="mt-1 block w-full rounded-md border-gray-300" />
              {firstError(errors, "last_name_kana") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "last_name_kana")}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">めい（かな）</label>
              <input name="first_name_kana" defaultValue={old?.first_name_kana || ""} className="mt-1 block w-full rounded-md border-gray-300" />
              {firstError(errors, "first_name_kana") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "first_name_kana")}</p>}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700">学校名</label>
              <select name="school_id" defaultValue={String(old?.school_id || "")} required className="mt-1 block w-full rounded-md border-gray-300">
                <option value="">選択してください</option>
                {schools.map((s) => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>
              {firstError(errors, "school_id") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "school_id")}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">学年</label>
              <select
                name="grade"
                value={grade}
                onChange={(e) => {
                  const nextGrade = e.target.value;
                  setGrade(nextGrade);
                  setBirthDate((prev) => applyBirthYearByGrade(nextGrade, prev));
                }}
                required
                className="mt-1 block w-full rounded-md border-gray-300"
              >
                {[1, 2, 3, 4, 5, 6].map((n) => (
                  <option key={n} value={n}>{n}年</option>
                ))}
              </select>
              {firstError(errors, "grade") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "grade")}</p>}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700">生年月日</label>
              <input
                type="date"
                name="birth_date"
                value={birthDate}
                onChange={(e) => setBirthDate(e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300"
              />
              {firstError(errors, "birth_date") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "birth_date")}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">拠点</label>
              <select name="base_id" defaultValue={String(old?.base_id || "")} className="mt-1 block w-full rounded-md border-gray-300">
                <option value="">未設定</option>
                {bases.map((b) => (
                  <option key={b.id} value={b.id}>{b.name}</option>
                ))}
              </select>
              {firstError(errors, "base_id") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "base_id")}</p>}
            </div>
          </div>

          <div className="rounded-xl border border-rose-200 bg-rose-50 p-3">
            <label className="block text-sm font-medium text-gray-700">アレルギー</label>
            <div className="mt-2 flex items-center gap-6">
              <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                <input
                  type="radio"
                  name="has_allergy"
                  value="0"
                  checked={allergyFlag === "0"}
                  onChange={(e) => setAllergyFlag(e.target.value)}
                  className="text-indigo-600 focus:ring-indigo-500"
                />
                無
              </label>
              <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                <input
                  type="radio"
                  name="has_allergy"
                  value="1"
                  checked={allergyFlag === "1"}
                  onChange={(e) => setAllergyFlag(e.target.value)}
                  className="text-indigo-600 focus:ring-indigo-500"
                />
                有
              </label>
            </div>
            {firstError(errors, "has_allergy") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "has_allergy")}</p>}

            {hasAllergy && (
              <div className="mt-2">
                <label className="block text-sm font-medium text-gray-700">アレルギー内容（「有」の場合）</label>
                <textarea
                  name="allergy_note"
                  value={allergyNote}
                  onChange={(e) => setAllergyNote(e.target.value)}
                  required={hasAllergy}
                  rows={3}
                  placeholder="例：卵、乳、小麦"
                  className="mt-1 block w-full rounded-md border-gray-300"
                />
                {firstError(errors, "allergy_note") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "allergy_note")}</p>}
              </div>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">備考</label>
            <textarea name="note" defaultValue={old?.note || ""} rows={3} className="mt-1 block w-full rounded-md border-gray-300" />
            {firstError(errors, "note") && <p className="mt-1 text-sm text-red-600">{firstError(errors, "note")}</p>}
          </div>

          <div className="flex justify-end">
            <button
              type="submit"
              className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
              きょうだいを登録する
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
