import React, { useState } from 'react';

const weekdayLabels = [
  { value: 1, label: '月' },
  { value: 2, label: '火' },
  { value: 3, label: '水' },
  { value: 4, label: '木' },
  { value: 5, label: '金' },
];

export default function ShiftCreatePage(props) {
  const initialForm = props?.initialForm || {};
  const urls = props?.urls || {};
  const preview = props?.preview || {};
  const errors = Array.isArray(props?.errors) ? props.errors : [];
  const staffUsers = Array.isArray(props?.staffUsers) ? props.staffUsers : [];
  const bases = Array.isArray(props?.bases) ? props.bases : [];

  const [form, setForm] = useState({
    entryMode: initialForm.entryMode || 'single',
    baseId: String(initialForm.baseId || ''),
    userId: String(initialForm.userId || ''),
    shiftDate: initialForm.shiftDate || '',
    startTime: initialForm.startTime || '14:00',
    endTime: initialForm.endTime || '18:00',
    note: initialForm.note || '',
    bulkStartDate: initialForm.bulkStartDate || '',
    bulkEndDate: initialForm.bulkEndDate || '',
    bulkPattern: initialForm.bulkPattern || 'daily',
    bulkWeekdays: Array.isArray(initialForm.bulkWeekdays) ? initialForm.bulkWeekdays.map(String) : ['1', '2', '3', '4', '5'],
    confirmOverwrite: Boolean(initialForm.confirmOverwrite),
  });

  const isAdmin = props?.isAdmin === true;
  const canSubmit = !isAdmin || staffUsers.length > 0;
  const hasPreview =
    (preview.targetDates || []).length > 0 ||
    (preview.duplicateDates || []).length > 0 ||
    (preview.blocked || []).length > 0;

  const updateField = (key, value) => {
    setForm((current) => ({ ...current, [key]: value }));
  };

  const toggleWeekday = (value) => {
    setForm((current) => {
      const exists = current.bulkWeekdays.includes(value);
      return {
        ...current,
        bulkWeekdays: exists
          ? current.bulkWeekdays.filter((item) => item !== value)
          : [...current.bulkWeekdays, value].sort(),
      };
    });
  };

  const buildQueryString = (nextBaseId) => {
    const params = new URLSearchParams();
    params.set('base_id', String(nextBaseId));
    params.set('date', form.shiftDate || form.bulkStartDate || '');
    params.set('entry_mode', form.entryMode);
    params.set('shift_date', form.shiftDate || '');
    params.set('start_time', form.startTime || '');
    params.set('end_time', form.endTime || '');
    params.set('note', form.note || '');
    params.set('user_id', form.userId || '');
    params.set('bulk_start_date', form.bulkStartDate || '');
    params.set('bulk_end_date', form.bulkEndDate || '');
    params.set('bulk_pattern', form.bulkPattern || 'daily');
    form.bulkWeekdays.forEach((weekday) => params.append('bulk_weekdays[]', weekday));
    return params.toString();
  };

  const handleBaseChange = (nextBaseId) => {
    updateField('baseId', nextBaseId);
    if (!urls.react) return;
    window.location.assign(`${urls.react}?${buildQueryString(nextBaseId)}`);
  };

  const handleSubmit = (event) => {
    if (!window.confirm('この内容でシフトを登録します。よろしいですか？')) {
      event.preventDefault();
    }
  };

  return (
    <div className="space-y-4">
      <div className="rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
              Shift Create
            </div>
            <div className="mt-1 flex items-center gap-3">
              <img src={props?.assets?.shiftIcon || ''} alt="" className="h-8 w-8 object-contain" />
              <div>
                <h1 className="text-xl font-black tracking-tight text-slate-900">シフト登録</h1>
                <p className="text-sm text-slate-500">スマホ向け登録画面</p>
              </div>
            </div>
          </div>

          <div className="flex shrink-0 gap-2">
            <a
              href={urls.create || '#'}
              className="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
              PC版
            </a>
            <a
              href={urls.index || '#'}
              className="inline-flex items-center rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200"
            >
              一覧
            </a>
          </div>
        </div>
      </div>

      {errors.length > 0 && (
        <div className="rounded-[1.5rem] border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800">
          <div className="font-extrabold">入力内容を確認してください</div>
          <ul className="mt-2 space-y-1 text-xs">
            {errors.map((error, index) => (
              <li key={`${error}-${index}`}>{error}</li>
            ))}
          </ul>
        </div>
      )}

      <form
        method="POST"
        action={urls.store || '#'}
        className="space-y-4"
        onSubmit={handleSubmit}
      >
        <input type="hidden" name="_token" value={props?.csrf || ''} />

        <div className="rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm">
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => updateField('entryMode', 'single')}
              className={`rounded-2xl px-4 py-4 text-sm font-black transition ${
                form.entryMode === 'single'
                  ? 'bg-indigo-600 text-white shadow'
                  : 'bg-slate-100 text-slate-700'
              }`}
            >
              単日
            </button>
            <button
              type="button"
              onClick={() => updateField('entryMode', 'bulk')}
              className={`rounded-2xl px-4 py-4 text-sm font-black transition ${
                form.entryMode === 'bulk'
                  ? 'bg-indigo-600 text-white shadow'
                  : 'bg-slate-100 text-slate-700'
              }`}
            >
              一括
            </button>
          </div>
        </div>

        <div className="rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm">
          <div className="grid grid-cols-1 gap-3">
            <div>
              <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">拠点</label>
              <select
                name="base_id"
                value={form.baseId}
                onChange={(event) => handleBaseChange(event.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                required
              >
                <option value="">選択してください</option>
                {bases.map((base) => (
                  <option key={base.id} value={base.id}>
                    {base.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">担当者</label>
              {isAdmin ? (
                staffUsers.length > 0 ? (
                  <select
                    name="user_id"
                    value={form.userId}
                    onChange={(event) => updateField('userId', event.target.value)}
                    className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                    required
                  >
                    <option value="">選択してください</option>
                    {staffUsers.map((user) => (
                      <option key={user.id} value={user.id}>
                        {user.name}
                      </option>
                    ))}
                  </select>
                ) : (
                  <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-bold text-amber-900">
                    この拠点の担当者候補がありません
                  </div>
                )
              ) : (
                <>
                  <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-base font-bold text-slate-800">
                    {props?.displayName || 'ユーザー'}
                  </div>
                  <input type="hidden" name="user_id" value={form.userId} />
                </>
              )}
            </div>
          </div>
        </div>

        {form.entryMode === 'single' ? (
          <div className="rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm">
            <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">日付</label>
            <input
              type="date"
              name="shift_date"
              value={form.shiftDate}
              onChange={(event) => updateField('shiftDate', event.target.value)}
              className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
              required
            />
          </div>
        ) : (
          <div className="space-y-4 rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">開始日</label>
                <input
                  type="date"
                  name="bulk_start_date"
                  value={form.bulkStartDate}
                  onChange={(event) => updateField('bulkStartDate', event.target.value)}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                  required
                />
              </div>
              <div>
                <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">終了日</label>
                <input
                  type="date"
                  name="bulk_end_date"
                  value={form.bulkEndDate}
                  onChange={(event) => updateField('bulkEndDate', event.target.value)}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                  required
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => updateField('bulkPattern', 'daily')}
                className={`rounded-2xl px-4 py-4 text-sm font-black transition ${
                  form.bulkPattern === 'daily'
                    ? 'bg-sky-600 text-white shadow'
                    : 'bg-slate-100 text-slate-700'
                }`}
              >
                毎日
              </button>
              <button
                type="button"
                onClick={() => updateField('bulkPattern', 'weekday')}
                className={`rounded-2xl px-4 py-4 text-sm font-black transition ${
                  form.bulkPattern === 'weekday'
                    ? 'bg-sky-600 text-white shadow'
                    : 'bg-slate-100 text-slate-700'
                }`}
              >
                曜日
              </button>
            </div>
            <input type="hidden" name="bulk_pattern" value={form.bulkPattern} />

            {form.bulkPattern === 'weekday' && (
              <div className="grid grid-cols-5 gap-2">
                {weekdayLabels.map((weekday) => {
                  const checked = form.bulkWeekdays.includes(String(weekday.value));
                  return (
                    <button
                      key={weekday.value}
                      type="button"
                      onClick={() => toggleWeekday(String(weekday.value))}
                      className={`rounded-2xl px-3 py-4 text-sm font-black transition ${
                        checked
                          ? 'bg-emerald-600 text-white shadow'
                          : 'bg-slate-100 text-slate-700'
                      }`}
                    >
                      {weekday.label}
                    </button>
                  );
                })}
                {form.bulkWeekdays.map((weekday) => (
                  <input key={weekday} type="hidden" name="bulk_weekdays[]" value={weekday} />
                ))}
              </div>
            )}

            {hasPreview && (
              <div className="space-y-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                <div className="grid grid-cols-3 gap-2 text-center">
                  <div className="rounded-2xl bg-white px-3 py-3">
                    <div className="text-[10px] font-bold text-slate-500">新規</div>
                    <div className="mt-1 text-lg font-black text-slate-900">{(preview.newDates || []).length}</div>
                  </div>
                  <div className="rounded-2xl bg-white px-3 py-3">
                    <div className="text-[10px] font-bold text-slate-500">重複</div>
                    <div className="mt-1 text-lg font-black text-amber-700">{(preview.duplicateDates || []).length}</div>
                  </div>
                  <div className="rounded-2xl bg-white px-3 py-3">
                    <div className="text-[10px] font-bold text-slate-500">祝日除外</div>
                    <div className="mt-1 text-lg font-black text-slate-900">{(preview.excludedHolidays || []).length}</div>
                  </div>
                </div>

                {(preview.blocked || []).length > 0 && (
                  <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-900">
                    {(preview.blocked || []).map((item, index) => (
                      <div key={`${item.date}-${index}`}>{item.date}: {item.reason}</div>
                    ))}
                  </div>
                )}

                {(preview.duplicateDates || []).length > 0 && (preview.blocked || []).length === 0 && (
                  <label className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                    <input
                      type="checkbox"
                      name="confirm_overwrite"
                      value="1"
                      checked={form.confirmOverwrite}
                      onChange={(event) => updateField('confirmOverwrite', event.target.checked)}
                      className="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600"
                    />
                    <span>重複日を上書きして登録する</span>
                  </label>
                )}
              </div>
            )}
          </div>
        )}

        <input type="hidden" name="entry_mode" value={form.entryMode} />

        <div className="rounded-[1.75rem] border border-slate-100 bg-white p-4 shadow-sm">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">開始</label>
              <input
                type="time"
                name="start_time"
                value={form.startTime}
                onChange={(event) => updateField('startTime', event.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                required
              />
            </div>
            <div>
              <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">終了</label>
              <input
                type="time"
                name="end_time"
                value={form.endTime}
                onChange={(event) => updateField('endTime', event.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                required
              />
            </div>
          </div>

          <div className="mt-3">
            <label className="mb-1 block text-[11px] font-bold tracking-wide text-slate-500">メモ</label>
            <textarea
              name="note"
              rows="3"
              value={form.note}
              onChange={(event) => updateField('note', event.target.value)}
              className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
              placeholder="メモ"
            />
          </div>
        </div>

        <div className="sticky bottom-[4.75rem] z-10 rounded-[1.5rem] border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur sm:bottom-4">
          <button
            type="submit"
            disabled={!canSubmit}
            className="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-4 text-base font-black text-white shadow hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
          >
            {form.entryMode === 'bulk' ? '一括登録する' : '登録する'}
          </button>
        </div>
      </form>
    </div>
  );
}
