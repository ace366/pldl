import './bootstrap.js';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// React
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * staff/attendance/today
 */
function mountStaffAttendanceToday() {
  const el = document.getElementById('react-staff-attendance-today');
  if (!el) return;

  const load = async () => {
    const mod = await import('./components/StaffAttendanceToday.jsx');
    return mod.default;
  };

  const props = {
    clockInAction: el.dataset.clockInAction || null,
    clockOutAction: el.dataset.clockOutAction || null,
    canIn: el.dataset.canIn === '1',
    canOut: el.dataset.canOut === '1',
    csrf: el.dataset.csrf || '',
  };

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

/**
 * admin/attendance-intents/react
 */
function mountAdminAttendanceIntents() {
  const el = document.getElementById('react-admin-attendance-intents');
  if (!el) return;

  const load = async () => {
    const mod = await import('./components/AdminAttendanceIntents.jsx');
    return mod.default;
  };

  const props = {
    date: el.dataset.date || '',
    apiSummary: el.dataset.apiSummary || '',
    apiTogglePickup: el.dataset.apiTogglePickup || '',
    apiToggleManual: el.dataset.apiToggleManual || '',
    csrf: el.dataset.csrf || '',
    carImg: el.dataset.car || '',
    ccarImg: el.dataset.ccar || '',
    canEdit: el.dataset.canEdit === '1',
  };

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

/**
 * admin/children/today/react
 */
function mountAdminTodayParticipants() {
  const el = document.getElementById('react-admin-today-participants');
  if (!el) return;

  const load = async () => {
    const mod = await import('./components/AdminTodayParticipants.jsx');
    return mod.default;
  };

  const props = {
    date: el.dataset.date || '',
    apiSummary: el.dataset.apiSummary || '',
    apiTogglePickup: el.dataset.apiTogglePickup || '',
    apiToggleManual: el.dataset.apiToggleManual || '',
    apiCheckout: el.dataset.apiCheckout || '',
    csrf: el.dataset.csrf || '',
    carImg: el.dataset.car || '',
    ccarImg: el.dataset.ccar || '',
    canEdit: el.dataset.canEdit === '1',
  };

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}
/**
 * family/availability (React)
 * - availability_react.blade.php に
 *   <div id="react-family-availability"></div>
 *   <script id="family-availability-props" type="application/json">...</script>
 *   を置く想定
 */
function mountFamilyAvailability() {
  const el = document.getElementById('react-family-availability');
  if (!el) return;

  const load = async () => {
    const mod = await import('./pages/family/AvailabilityPage.jsx');
    return mod.default;
  };

  const propsEl = document.getElementById('family-availability-props');
  let props = null;

  try {
    props = propsEl ? JSON.parse(propsEl.textContent || '{}') : null;
  } catch (e) {
    props = null;
  }

  if (!props) return;

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

/**
 * family/siblings (React)
 */
function mountFamilySiblings() {
  const el = document.getElementById('react-family-siblings');
  if (!el) return;

  const load = async () => {
    const mod = await import('./pages/family/SiblingsPage.jsx');
    return mod.default;
  };

  const propsEl = document.getElementById('family-siblings-props');
  let props = null;

  try {
    props = propsEl ? JSON.parse(propsEl.textContent || '{}') : null;
  } catch (e) {
    props = null;
  }

  if (!props) return;

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

mountStaffAttendanceToday();
mountAdminAttendanceIntents();
mountAdminTodayParticipants();
mountFamilyAvailability();
mountFamilySiblings();

/**
 * family/home (React)
 */
function mountFamilyHome() {
  const el = document.getElementById('react-family-home');
  if (!el) return;

  const load = async () => {
    const mod = await import('./pages/family/HomePage.jsx');
    return mod.default;
  };

  const propsEl = document.getElementById('family-home-props');
  let props = null;

  try {
    props = propsEl ? JSON.parse(propsEl.textContent || '{}') : null;
  } catch (e) {
    props = null;
  }

  if (!props) return;

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

mountFamilyHome();

/**
 * enroll/complete (React)
 */
function mountEnrollComplete() {
  const el = document.getElementById('react-enroll-complete');
  if (!el) return;

  const load = async () => {
    const mod = await import('./pages/enroll/CompletePage.jsx');
    return mod.default;
  };

  const propsEl = document.getElementById('enroll-complete-props');
  let props = null;

  try {
    props = propsEl ? JSON.parse(propsEl.textContent || '{}') : null;
  } catch (e) {
    props = null;
  }

  if (!props) return;

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

mountEnrollComplete();

/**
 * admin/children/messages (React)
 */
function mountAdminChildMessages() {
  const el = document.getElementById('react-admin-child-messages');
  if (!el) return;

  const load = async () => {
    const mod = await import('./pages/admin/ChildMessagesPage.jsx');
    return mod.default;
  };

  const propsEl = document.getElementById('admin-child-messages-props');
  let props = null;

  try {
    props = propsEl ? JSON.parse(propsEl.textContent || '{}') : null;
  } catch (e) {
    props = null;
  }

  if (!props) return;

  if (el.dataset.mounted === '1') return;
  el.dataset.mounted = '1';

  load().then((Component) => {
    createRoot(el).render(<Component {...props} />);
  });
}

mountAdminChildMessages();

/**
 * profile/edit (bank lookup)
 */
function mountProfileBankLookup() {
  const root = document.getElementById('profile-bank-lookup');
  if (!root) return;

  const triggerIds = ['bank_name', 'bank_code', 'bank_branch_name', 'bank_branch_code'];
  const triggerEls = triggerIds
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  if (!triggerEls.length) return;

  const statusEl = document.createElement('p');
  statusEl.className = 'text-xs text-gray-500 hidden';
  root.insertBefore(statusEl, root.children[1] ?? null);

  let isLoaded = false;
  let isLoading = false;
  let pendingTarget = null;

  const showStatus = (message, isError = false) => {
    statusEl.textContent = message;
    statusEl.classList.remove('hidden', 'text-gray-500', 'text-red-600');
    statusEl.classList.add(isError ? 'text-red-600' : 'text-gray-500');
  };

  const hideStatus = () => {
    statusEl.textContent = '';
    statusEl.classList.add('hidden');
  };

  const ensureLookupLoaded = (target) => {
    pendingTarget = target ?? pendingTarget;

    if (isLoaded || isLoading) return;

    isLoading = true;
    showStatus('銀行検索データを読み込み中です...');

    import('./profile/bankLookup.js')
      .then((mod) => {
        const init = mod.default;
        if (typeof init === 'function') init();

        isLoaded = true;
        isLoading = false;
        hideStatus();

        if (pendingTarget) {
          pendingTarget.dispatchEvent(new Event('input', { bubbles: true }));
          pendingTarget = null;
        }
      })
      .catch(() => {
        isLoading = false;
        showStatus('銀行検索データの読み込みに失敗しました。再入力で再試行します。', true);
      });
  };

  const onInteract = (event) => {
    ensureLookupLoaded(event?.target ?? null);
  };

  triggerEls.forEach((el) => {
    el.addEventListener('focus', onInteract, { once: true });
    el.addEventListener('input', onInteract);
  });
}

mountProfileBankLookup();
