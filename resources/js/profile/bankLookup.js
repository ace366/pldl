import zenginCode from 'zengin-code';

const ZENGIN = zenginCode?.default ?? zenginCode;

const hiraToKata = (str) => {
  if (!str) return '';
  return str.replace(/[\u3041-\u3096]/g, (ch) =>
    String.fromCharCode(ch.charCodeAt(0) + 0x60)
  );
};

const normalize = (str) => {
  return hiraToKata(String(str || '')).replace(/\s+/g, '').toUpperCase();
};

const createResultList = (container, items, onPick) => {
  container.innerHTML = '';
  if (!items.length) {
    container.classList.add('hidden');
    return;
  }
  const ul = document.createElement('ul');
  ul.className = 'max-h-64 overflow-auto';

  items.forEach((item) => {
    const li = document.createElement('li');
    li.className = 'px-3 py-2 cursor-pointer hover:bg-gray-100 border-b last:border-b-0';
    li.textContent = item.label;
    li.addEventListener('click', () => onPick(item));
    ul.appendChild(li);
  });

  container.appendChild(ul);
  container.classList.remove('hidden');
};

export default function initProfileBankLookup() {
  const root = document.getElementById('profile-bank-lookup');
  if (!root || !ZENGIN) return;

  const bankNameEl = document.getElementById('bank_name');
  const bankCodeEl = document.getElementById('bank_code');
  const branchNameEl = document.getElementById('bank_branch_name');
  const branchCodeEl = document.getElementById('bank_branch_code');
  const resultsEl = document.getElementById('bank-search-results');
  const branchResultsEl = document.getElementById('branch-search-results');

  if (!bankNameEl || !bankCodeEl || !branchNameEl || !branchCodeEl || !resultsEl || !branchResultsEl) return;

  const banks = Object.entries(ZENGIN).map(([code, bank]) => ({
    code,
    name: bank?.name ?? '',
    kana: bank?.kana ?? '',
    branches: bank?.branches ?? {},
  }));

  const findBank = (code) => banks.find((b) => b.code === code);

  const renderBankCandidates = (query) => {
    const q = normalize(query);
    if (!q) {
      resultsEl.classList.add('hidden');
      return;
    }
    const candidates = banks
      .filter((b) => {
        const name = normalize(b.name);
        const kana = normalize(b.kana);
        return b.code.startsWith(q) || name.includes(q) || kana.includes(q);
      })
      .slice(0, 20)
      .map((b) => ({
        ...b,
        label: `${b.code} ${b.name}`.trim(),
      }));

    createResultList(resultsEl, candidates, (b) => {
      bankNameEl.value = b.name;
      bankCodeEl.value = b.code;
      resultsEl.classList.add('hidden');
      branchNameEl.value = '';
      branchCodeEl.value = '';
    });
  };

  const renderBranchCandidates = (query) => {
    const bank = findBank(bankCodeEl.value);
    if (!bank) {
      branchResultsEl.classList.add('hidden');
      return;
    }
    const branches = Object.entries(bank.branches || {}).map(([code, branch]) => ({
      code,
      name: branch?.name ?? '',
      kana: branch?.kana ?? '',
    }));

    const q = normalize(query);
    if (!q) {
      branchResultsEl.classList.add('hidden');
      return;
    }

    const candidates = branches
      .filter((b) => {
        const name = normalize(b.name);
        const kana = normalize(b.kana);
        return b.code.startsWith(q) || name.includes(q) || kana.includes(q);
      })
      .slice(0, 20)
      .map((b) => ({
        ...b,
        label: `${b.code} ${b.name}`.trim(),
      }));

    createResultList(branchResultsEl, candidates, (b) => {
      branchNameEl.value = b.name;
      branchCodeEl.value = b.code;
      branchResultsEl.classList.add('hidden');
    });
  };

  bankNameEl.addEventListener('input', (e) => renderBankCandidates(e.target.value));
  bankCodeEl.addEventListener('input', (e) => renderBankCandidates(e.target.value));
  branchNameEl.addEventListener('input', (e) => renderBranchCandidates(e.target.value));
  branchCodeEl.addEventListener('input', (e) => renderBranchCandidates(e.target.value));

  document.addEventListener('click', (e) => {
    if (!resultsEl.contains(e.target) && e.target !== bankNameEl && e.target !== bankCodeEl) {
      resultsEl.classList.add('hidden');
    }
    if (!branchResultsEl.contains(e.target) && e.target !== branchNameEl && e.target !== branchCodeEl) {
      branchResultsEl.classList.add('hidden');
    }
  });
}
