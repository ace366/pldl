import React from 'react';

export default function EnrollCompletePage({ loginId, lineUrl, lineImg }) {
  return (
    <div className="space-y-8">
      <header className="space-y-4 text-center">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
          <span className="text-3xl">✓</span>
        </div>
        <div className="space-y-2">
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
            登録完了
          </h1>
          <p className="text-sm text-slate-600 sm:text-base">
            下記よりLINE登録をお願いします
          </p>
        </div>
        <div className="flex items-center justify-center">
          <a href={lineUrl} className="inline-flex">
            <img src={lineImg} alt="友だち追加" height="36" className="h-10" />
          </a>
        </div>
      </header>

      <section className="rounded-2xl border border-slate-200 bg-slate-50/80 p-6 sm:p-8">
        <div className="space-y-3 text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-slate-500">
            Login ID
          </p>
          <div className="rounded-2xl border border-emerald-200 bg-white px-4 py-5">
            <div className="text-3xl font-black tracking-[0.2em] text-emerald-700 sm:text-4xl">
              {loginId || '---'}
            </div>
          </div>
          <p className="text-sm text-slate-700">
            上記IDがサイトのログインIDになります。大切に保管してください。
          </p>
        </div>
      </section>

      <section className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        <div className="space-y-4">
          <h2 className="text-base font-bold text-slate-900">QRコードについて</h2>
          <p className="text-sm leading-relaxed text-slate-600">
            QRコードは出席の時に使用します。お子様が携帯電話をお持ちの場合は、本人もログインできるようにすることをおすすめします（任意）。
          </p>
          <p className="text-sm leading-relaxed text-slate-600">
            まだ携帯電話をお持ちでないお子様には、QRコード付きのカードを配布しますので、会場の管理者へお伝えください。
          </p>
        </div>
      </section>

      <div className="rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 p-[2px]">
        <div className="rounded-[14px] bg-white px-5 py-4 text-center text-sm text-slate-700">
          何かお困りごとがあれば、LINEからお気軽にご連絡ください。
        </div>
      </div>

      <div className="flex justify-center">
        <a
          href="/family/login"
          className="inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800"
        >
          お知らせへ進む
        </a>
      </div>
    </div>
  );
}
