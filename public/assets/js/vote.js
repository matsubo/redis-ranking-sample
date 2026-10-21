// Member filter and in-place voting for the ranking board. Loaded with defer,
// so the document is parsed when this runs. Without JS the forms post
// normally and the server redirects back to the member's row.
(() => {
  const board = document.getElementById('board');
  if (!board) return;
  const items = [...board.querySelectorAll('li[data-name]')];
  const toast = document.getElementById('toast');
  const total = document.querySelector('[data-total]');
  const fmt = new Intl.NumberFormat('ja-JP');
  let toastTimer;

  const say = (message, error = false) => {
    toast.textContent = message;
    toast.toggleAttribute('data-error', error);
    toast.setAttribute('data-show', '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.removeAttribute('data-show'), error ? 6000 : 2600);
  };

  // Member filter
  const norm = (s) => s.normalize('NFKC').toLowerCase();
  const keys = items.map((li) => norm(li.dataset.name));
  const search = document.getElementById('search');
  const noHit = document.getElementById('no-hit');
  document.getElementById('search-box').hidden = false;
  search.addEventListener('input', () => {
    const q = norm(search.value.trim());
    let shown = 0;
    items.forEach((li, i) => {
      const hit = keys[i].includes(q);
      li.hidden = !hit;
      shown += hit;
    });
    noHit.hidden = shown > 0;
  });

  // Vote in place
  const rescale = () => {
    const top = Math.max(...items.map((li) => +li.dataset.votes));
    for (const li of items) {
      li.querySelector('[data-bar]').style.setProperty('--share', top ? (li.dataset.votes / top) * 100 + '%' : '0%');
    }
  };

  board.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    const li = form.closest('li');
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error(data.error || '投票に失敗しました。');

      li.dataset.votes = data.votes;
      const count = li.querySelector('[data-count]');
      count.textContent = fmt.format(data.votes);
      total.textContent = fmt.format(Number(total.textContent.replace(/\D/g, '')) + 1);
      rescale();

      li.classList.remove('motion-safe:animate-voted');
      void li.offsetWidth;
      li.classList.add('motion-safe:animate-voted');
      const plus = document.createElement('span');
      plus.textContent = '+1';
      plus.className = 'pointer-events-none absolute -top-4 left-full ml-1 font-sans text-sm font-bold text-pink motion-safe:animate-plus';
      count.parentElement.append(plus);
      setTimeout(() => plus.remove(), 1200);

      say(`${data.name} に投票しました（現在 ${data.rank} 位）`);
    } catch (err) {
      say(err.message, true);
    } finally {
      button.disabled = false;
    }
  });
})();
