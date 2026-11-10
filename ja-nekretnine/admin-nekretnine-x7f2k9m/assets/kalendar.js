(function () {
  'use strict';

  const wrap = document.getElementById('calendarWrap');
  const saveBtn = document.getElementById('sacuvajKalendar');
  const statusEl = document.getElementById('kalendarStatus');
  const MESECI = ['Januar','Februar','Mart','April','Maj','Jun','Jul','Avgust','Septembar','Oktobar','Novembar','Decembar'];
  const DANI = ['Pon','Uto','Sre','Čet','Pet','Sub','Ned'];

  // originalno stanje sa servera (bazna linija) i lokalne, još nesačuvane izmene
  let baseline = Object.assign({}, STATUSI);
  let pending = {}; // { 'YYYY-MM-DD': 'slobodno' | 'zauzeto' }

  function fmt(y, m, d) {
    return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  }

  function trenutniStatus(dateStr) {
    return Object.prototype.hasOwnProperty.call(pending, dateStr)
      ? pending[dateStr]
      : (baseline[dateStr] || 'slobodno');
  }

  function osveziDugme() {
    const brojIzmena = Object.keys(pending).length;
    saveBtn.disabled = brojIzmena === 0;
    saveBtn.textContent = brojIzmena > 0 ? `Sačuvaj izmene (${brojIzmena})` : 'Sačuvaj izmene';
  }

  function renderMonth(year, month) {
    const first = new Date(year, month, 1);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    let startDay = first.getDay();
    startDay = startDay === 0 ? 6 : startDay - 1;

    const monthEl = document.createElement('div');
    monthEl.className = 'calendar-month';

    const title = document.createElement('h3');
    title.textContent = `${MESECI[month]} ${year}`;
    monthEl.appendChild(title);

    const grid = document.createElement('div');
    grid.className = 'calendar-grid';

    DANI.forEach(d => {
      const cell = document.createElement('div');
      cell.className = 'calendar-cell calendar-cell--head';
      cell.textContent = d;
      grid.appendChild(cell);
    });

    for (let i = 0; i < startDay; i++) {
      const cell = document.createElement('div');
      cell.className = 'calendar-cell calendar-cell--empty';
      grid.appendChild(cell);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = fmt(year, month, d);
      const cell = document.createElement('div');
      cell.className = 'calendar-cell calendar-cell--day';
      cell.textContent = d;
      cell.dataset.date = dateStr;
      postaviKlasu(cell, dateStr);
      cell.addEventListener('click', () => toggleDay(cell));
      grid.appendChild(cell);
    }

    monthEl.appendChild(grid);
    return monthEl;
  }

  function postaviKlasu(cell, dateStr) {
    const status = trenutniStatus(dateStr);
    cell.classList.toggle('is-taken', status === 'zauzeto');
    cell.classList.toggle('is-free', status === 'slobodno');
    cell.classList.toggle('is-pending', Object.prototype.hasOwnProperty.call(pending, dateStr));
  }

  function toggleDay(cell) {
    const dateStr = cell.dataset.date;
    const current = trenutniStatus(dateStr);
    const next = current === 'zauzeto' ? 'slobodno' : 'zauzeto';

    if (next === (baseline[dateStr] || 'slobodno')) {
      delete pending[dateStr];
    } else {
      pending[dateStr] = next;
    }

    postaviKlasu(cell, dateStr);
    osveziDugme();
    statusEl.textContent = '';
  }

  function sacuvajIzmene() {
    const promene = Object.keys(pending).map(datum => ({ datum, status: pending[datum] }));
    if (!promene.length) return;

    saveBtn.disabled = true;
    saveBtn.textContent = 'Čuvanje...';
    statusEl.textContent = '';
    statusEl.className = 'calendar-status';

    fetch('kalendar-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        nekretnina_id: NEKRETNINA_ID,
        promene: JSON.stringify(promene),
        csrf_token: CSRF_TOKEN,
      }),
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Object.assign(baseline, pending);
          pending = {};
          osveziDugme();
          statusEl.textContent = '✓ Izmene su sačuvane.';
          statusEl.className = 'calendar-status calendar-status--ok';
        } else {
          statusEl.textContent = data.message || 'Greška pri čuvanju izmena.';
          statusEl.className = 'calendar-status calendar-status--error';
          saveBtn.disabled = false;
          saveBtn.textContent = `Sačuvaj izmene (${promene.length})`;
        }
      })
      .catch(() => {
        statusEl.textContent = 'Greška u komunikaciji sa serverom. Pokušaj ponovo.';
        statusEl.className = 'calendar-status calendar-status--error';
        saveBtn.disabled = false;
        saveBtn.textContent = `Sačuvaj izmene (${promene.length})`;
      });
  }

  saveBtn.addEventListener('click', sacuvajIzmene);

  window.addEventListener('beforeunload', (e) => {
    if (Object.keys(pending).length > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  const today = new Date();
  for (let i = 0; i < 6; i++) {
    const y = today.getFullYear();
    const m = today.getMonth() + i;
    const targetYear = y + Math.floor(m / 12);
    const targetMonth = m % 12;
    wrap.appendChild(renderMonth(targetYear, targetMonth));
  }
  osveziDugme();
})();
