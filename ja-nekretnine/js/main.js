'use strict';

// =============================================
// NEKRETNINE — sada se učitavaju sa servera (api/nekretnine.php)
// Vlasnik ih dodaje/uređuje kroz admin panel, ne kroz ovaj fajl.
// =============================================

var NEKRETNINE_DATA = [];

function ucitajNekretnine() {
  const grid = document.getElementById('nekretnineGrid');
  const scope = grid ? (grid.dataset.scope || 'istaknute') : 'istaknute';
  const url = scope === 'sve' ? 'api/nekretnine.php' : 'api/nekretnine.php?istaknute=1';

  fetch(url)
    .then(r => r.json())
    .then(data => {
      NEKRETNINE_DATA = data;
      renderNekretnine(NEKRETNINE_DATA);
    })
    .catch(() => {
      if (grid) grid.innerHTML = '<div class="nekretnine__empty">Trenutno ne možemo učitati nekretnine. Pokušajte kasnije.</div>';
    });
}



// ── NAV SCROLL ──────────────────────────────
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 60);
});

// ── MOBILE BURGER ───────────────────────────
const burger   = document.getElementById('burger');
const navLinks = document.getElementById('navLinks');

burger.addEventListener('click', () => {
  const open = navLinks.classList.toggle('open');
  burger.setAttribute('aria-expanded', open);
  const bars = burger.querySelectorAll('span');
  if (open) {
    bars[0].style.transform = 'translateY(6.5px) rotate(45deg)';
    bars[1].style.opacity   = '0';
    bars[2].style.transform = 'translateY(-6.5px) rotate(-45deg)';
  } else {
    bars.forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
  }
});

navLinks.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    burger.querySelectorAll('span').forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
  });
});

// ── REVEAL ON SCROLL ────────────────────────
const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      const siblings = entry.target.parentElement
        ? [...entry.target.parentElement.querySelectorAll('.reveal, .reveal-left, .reveal-right')]
        : [];
      const idx = siblings.indexOf(entry.target);
      entry.target.style.transitionDelay = idx > 0 ? (idx * 0.1) + 's' : '0s';
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

revealEls.forEach(el => observer.observe(el));

// ── CONTACT FORM ────────────────────────────
const form = document.getElementById('contactForm');
if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn      = form.querySelector('button[type="submit"]');
    const note     = form.querySelector('.form-note');
    const original = btn.textContent;

    // Stanje: šalje se
    btn.textContent = 'Šaljemo...';
    btn.disabled    = true;
    if (note) { note.style.color = ''; note.textContent = 'Odgovaramo u roku od 24 sata. Diskrecija garantovana.'; }

    try {
      const data = new FormData(form);

      const response = await fetch('/contact.php', {
        method: 'POST',
        body:   data
      });

      const result = await response.json();

      if (result.success) {
        // ── Uspjeh ──
        btn.textContent    = '✓ Poruka je poslana';
        btn.style.background = '#5c8a28';
        if (note) {
          note.style.color   = '#7ab33a';
          note.textContent   = 'Hvala! Odgovorit ćemo vam u roku od 24 sata.';
        }
        form.reset();

        setTimeout(() => {
          btn.textContent      = original;
          btn.style.background = '';
          btn.disabled         = false;
          if (note) {
            note.style.color   = '';
            note.textContent   = 'Odgovaramo u roku od 24 sata. Diskrecija garantovana.';
          }
        }, 5000);

      } else {
        // ── Greška sa servera ──
        btn.textContent      = 'Greška — pokušajte ponovo';
        btn.style.background = '#8a2828';
        btn.disabled         = false;
        if (note) {
          note.style.color   = '#c0392b';
          note.textContent   = result.message || 'Došlo je do greške. Kontaktirajte nas direktno.';
        }
        setTimeout(() => {
          btn.textContent      = original;
          btn.style.background = '';
          if (note) {
            note.style.color   = '';
            note.textContent   = 'Odgovaramo u roku od 24 sata. Diskrecija garantovana.';
          }
        }, 4000);
      }

    } catch (err) {
      // ── Mrežna greška ──
      btn.textContent      = 'Greška veze';
      btn.style.background = '#8a2828';
      btn.disabled         = false;
      if (note) {
        note.style.color   = '#c0392b';
        note.textContent   = 'Nije moguće uspostaviti vezu. Provjerite internet konekciju.';
      }
      setTimeout(() => {
        btn.textContent      = original;
        btn.style.background = '';
        btn.disabled         = false;
        if (note) {
          note.style.color   = '';
          note.textContent   = 'Odgovaramo u roku od 24 sata. Diskrecija garantovana.';
        }
      }, 4000);
    }
  });
}

// ── SMOOTH SCROLL ───────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.getElementById(this.getAttribute('href').slice(1));
    if (!target) return;
    e.preventDefault();
    window.scrollTo({
      top: target.getBoundingClientRect().top + window.scrollY - 80,
      behavior: 'smooth'
    });
  });
});

// ── CURSOR GLOW ─────────────────────────────
const glow = document.createElement('div');
glow.style.cssText = 'position:fixed;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(122,179,58,0.06)0%,transparent 70%);pointer-events:none;z-index:9999;transform:translate(-50%,-50%);transition:opacity 0.5s;top:-999px;left:-999px;';
document.body.appendChild(glow);
let glowVisible = false;
document.addEventListener('mousemove', (e) => {
  glow.style.top = e.clientY + 'px';
  glow.style.left = e.clientX + 'px';
  if (!glowVisible) { glow.style.opacity = '1'; glowVisible = true; }
});
document.addEventListener('mouseleave', () => { glow.style.opacity = '0'; glowVisible = false; });

// ── GENERISANJE KARTICA IZ NEKRETNINE_DATA ──
function renderNekretnine(lista) {
  const grid = document.getElementById('nekretnineGrid');
  if (!grid) return;

  if (!lista || lista.length === 0) {
    grid.innerHTML = '<div class="nekretnine__empty">Nekretnine uskoro.</div>';
    return;
  }

  grid.innerHTML = lista.map((n, i) => {
    const sobeHTML = n.sobe > 0
      ? `<div class="nekr-card__meta-item">
           <svg viewBox="0 0 16 16" fill="none"><path d="M1 10V6a2 2 0 012-2h10a2 2 0 012 2v4M1 10h14M1 10v3M15 10v3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
           ${n.sobe} ${n.sobe === 1 ? 'soba' : 'sobe'}
         </div>`
      : '';

    const slikaHTML = n.slika
      ? `<img src="${n.slika}" alt="${n.naziv}" loading="lazy"
             onerror="this.parentElement.innerHTML='<div class=\\'nekr-card__img-placeholder\\'>📷 Slika uskoro</div>'">`
      : `<div class="nekr-card__img-placeholder">📷 Slika uskoro</div>`;

    const statusBadgeHTML = (n.tip === 'Prodaja' && n.status_prodaje)
      ? `<span class="status-badge status-badge--${n.status_prodaje.toLowerCase()}">${n.status_prodaje}</span>`
      : '';

    return `
      <article class="nekr-card" data-tip="${n.tip}" data-kat="${n.kategorija}" data-id="${n.id}">
        <div class="nekr-card__img">
          ${slikaHTML}
          <span class="nekr-card__badge${n.tip === 'Iznajmljivanje' ? ' nekr-card__badge--iznajmljivanje' : ''}">${n.tip}</span>
          ${n.istaknuta ? '<span class="nekr-card__featured">◆ Istaknuto</span>' : ''}
        </div>
        <div class="nekr-card__body">
          <p class="nekr-card__lokacija">${n.lokacija}</p>
          <h3 class="nekr-card__naziv">${n.naziv}</h3>
          <p class="nekr-card__opis">${n.opis}</p>
          <div class="nekr-card__meta">
            <div class="nekr-card__meta-item">
              <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="14" height="14" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M1 6h14" stroke="currentColor" stroke-width="1.2"/></svg>
              ${n.kvadratura} m²
            </div>
            ${sobeHTML}
            <div class="nekr-card__meta-item">
              <svg viewBox="0 0 16 16" fill="none"><path d="M8 1a5 5 0 015 5c0 3.5-5 9-5 9S3 9.5 3 6a5 5 0 015-5z" stroke="currentColor" stroke-width="1.2"/></svg>
              ${n.kategorija}
            </div>
          </div>
          ${statusBadgeHTML ? `<div class="nekr-card__status-row">${statusBadgeHTML}</div>` : ''}
          <div class="nekr-card__cijena">
            ${n.cijena}
            <span>Detalji →</span>
          </div>
        </div>
      </article>`;
  }).join('');

  // Reveal animacija za kartice
  grid.querySelectorAll('.nekr-card').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(28px)';
    setTimeout(() => {
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, i * 100);
  });
}

// ── FILTERI ─────────────────────────────────
let aktivniFilter = 'sve';

function primijeniFilter(filter) {
  aktivniFilter = filter;
  const kartice = document.querySelectorAll('.nekr-card');
  kartice.forEach(kartica => {
    const tip = kartica.dataset.tip;
    const kat = kartica.dataset.kat;
    const prikazati = filter === 'sve' || tip === filter || kat === filter;
    kartica.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    if (prikazati) {
      kartica.style.display = '';
      setTimeout(() => { kartica.style.opacity = '1'; kartica.style.transform = 'translateY(0)'; }, 10);
    } else {
      kartica.style.opacity = '0';
      kartica.style.transform = 'translateY(8px)';
      setTimeout(() => { kartica.style.display = 'none'; }, 300);
    }
  });
}

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    primijeniFilter(btn.dataset.filter);
  });
});

// ── MODAL ───────────────────────────────────
document.addEventListener('click', (e) => {
  const kartica = e.target.closest('.nekr-card');
  if (!kartica) return;

  const id = parseInt(kartica.dataset.id, 10);
  if (!id) return;

  if (kartica.dataset.type === 'zgrada') {
    fetch(`api/zgrada-detalji.php?id=${id}`)
      .then(r => r.json())
      .then(z => {
        if (z.error) return;
        otvoriZgradaModal(z);
      });
    return;
  }

  fetch(`api/nekretnina-detalji.php?id=${id}`)
    .then(r => r.json())
    .then(n => {
      if (n.error) return;
      otvoriModal(n);
    });
});

// ── Deljene funkcije za galeriju slika (koriste ih i nekretnine i zgrade) ──
function kreirajMapuHTML(lat, lng, naziv) {
  if (lat === null || lat === undefined || lng === null || lng === undefined) return '';
  const delta = 0.006; // priblizno ~600m okvir oko markera
  const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`;
  const src = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;
  return `
    <div class="nekr-modal__mapa-sekcija">
      <h3 class="nekr-modal__subtitle">Lokacija</h3>
      <div class="nekr-modal__mapa-wrap">
        <iframe src="${src}" loading="lazy" title="Mapa — ${naziv}"></iframe>
      </div>
    </div>`;
}

function renderMedijSlajd(stavka, naslov) {
  if (stavka.tip === 'video') {
    return `<video src="${stavka.url}" controls playsinline id="nekrModalGlavniMedij"></video>`;
  }
  return `<img src="${stavka.url}" alt="${naslov || ''}" id="nekrModalGlavniMedij">`;
}

function renderMinijatura(stavka, i) {
  if (stavka.tip === 'video') {
    return `<div class="nekr-modal__thumb nekr-modal__thumb--video${i === 0 ? ' active' : ''}" data-i="${i}"
                style="position:relative;overflow:hidden;padding:0;">
        <video src="${stavka.url}" muted style="width:100%;height:100%;object-fit:cover;pointer-events:none;display:block;"></video>
        <span style="position:absolute;inset:0;background:rgba(0,0,0,0.35);"></span>
        <span class="nekr-modal__thumb-play" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
              width:22px;height:22px;border-radius:50%;background:rgba(122,179,58,0.9);color:#fff;
              display:flex;align-items:center;justify-content:center;font-size:10px;padding-left:1px;pointer-events:none;">▶</span>
      </div>`;
  }
  return `<img src="${stavka.url}" class="nekr-modal__thumb${i === 0 ? ' active' : ''}" data-i="${i}">`;
}

function kreirajGalerijuHTML(slike, naslov) {
  const slikaHTML = slike.length
    ? `<div id="nekrModalMedijWrap">${renderMedijSlajd(slike[0], naslov)}</div>`
    : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--mid)">📷 Slika uskoro</div>`;

  const strelice = slike.length > 1
    ? `<button class="nekr-modal__arrow nekr-modal__arrow--prev" id="nekrModalPrev" aria-label="Prethodni medij">
         <svg viewBox="0 0 20 20" fill="none"><path d="M12.5 4l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
       </button>
       <button class="nekr-modal__arrow nekr-modal__arrow--next" id="nekrModalNext" aria-label="Sledeći medij">
         <svg viewBox="0 0 20 20" fill="none"><path d="M7.5 4l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
       </button>
       <span class="nekr-modal__counter" id="nekrModalCounter">1 / ${slike.length}</span>`
    : '';

  const galerijaHTML = slike.length > 1
    ? `<div class="nekr-modal__thumbs">
        ${slike.map((s, i) => renderMinijatura(s, i)).join('')}
      </div>`
    : '';

  return { slikaHTML, strelice, galerijaHTML };
}

function inicijalizujKarusel(overlay, slike, naslov) {
  let trenutniIndex = 0;

  function prikaziSliku(i) {
    if (!slike.length) return;
    trenutniIndex = (i + slike.length) % slike.length;
    const wrap = document.getElementById('nekrModalMedijWrap');
    if (wrap) wrap.innerHTML = renderMedijSlajd(slike[trenutniIndex], naslov);
    const counter = document.getElementById('nekrModalCounter');
    if (counter) counter.textContent = `${trenutniIndex + 1} / ${slike.length}`;
    overlay.querySelectorAll('.nekr-modal__thumb').forEach(t => {
      t.classList.toggle('active', parseInt(t.dataset.i, 10) === trenutniIndex);
    });
  }

  const prevBtn = document.getElementById('nekrModalPrev');
  const nextBtn = document.getElementById('nekrModalNext');
  if (prevBtn) prevBtn.addEventListener('click', () => prikaziSliku(trenutniIndex - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => prikaziSliku(trenutniIndex + 1));

  overlay.querySelectorAll('.nekr-modal__thumb').forEach(thumb => {
    thumb.addEventListener('click', () => prikaziSliku(parseInt(thumb.dataset.i, 10)));
  });

  function tastaturaKarusel(e) {
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'ArrowLeft') prikaziSliku(trenutniIndex - 1);
    if (e.key === 'ArrowRight') prikaziSliku(trenutniIndex + 1);
  }
  document.addEventListener('keydown', tastaturaKarusel);
  overlay._tastaturaKarusel = tastaturaKarusel; // sačuvano da se ukloni pri zatvaranju
}

// ── Video link (YouTube / Vimeo / Instagram) — posebna sekcija ispod glavnih detalja ──
function kreirajVideoEmbedHTML(info) {
  if (!info) return '';

  if (info.platform === 'instagram') {
    return `<div class="nekr-modal__video-sekcija">
      <h3 class="nekr-modal__subtitle">Video</h3>
      <blockquote class="instagram-media" data-instgrm-permalink="${info.post_url}" data-instgrm-version="14" style="margin:0 auto;max-width:540px;"></blockquote>
    </div>`;
  }

  return `<div class="nekr-modal__video-sekcija">
    <h3 class="nekr-modal__subtitle">Video</h3>
    <div class="nekr-modal__video-wrap">
      <iframe src="${info.embed_url}" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>`;
}

function ucitajInstagramEmbedSkriptuAkoTreba(info) {
  if (!info || info.platform !== 'instagram') return;
  if (window.instgrm && window.instgrm.Embeds) {
    window.instgrm.Embeds.process();
    return;
  }
  if (document.getElementById('instagramEmbedScript')) return;
  const script = document.createElement('script');
  script.id = 'instagramEmbedScript';
  script.src = 'https://www.instagram.com/embed.js';
  script.async = true;
  document.body.appendChild(script);
}

function posaljiUpit(upitForma, extraFields) {
  upitForma.addEventListener('submit', (ev) => {
    ev.preventDefault();
    const statusEl = upitForma.querySelector('.nekr-modal__upit-status');
    const submitBtn = upitForma.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    statusEl.textContent = 'Slanje...';

    const formData = new URLSearchParams({
      ...extraFields,
      ime: upitForma.querySelector('[name="ime"]').value,
      email: upitForma.querySelector('[name="email"]').value,
      telefon: upitForma.querySelector('[name="telefon"]').value,
      poruka: upitForma.querySelector('[name="poruka"]').value,
    });

    fetch('api/upit.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData })
      .then(r => r.json())
      .then(data => {
        submitBtn.disabled = false;
        if (data.success) {
          statusEl.textContent = 'Hvala! Vaša poruka je poslata.';
          upitForma.reset();
        } else {
          statusEl.textContent = data.message || 'Greška pri slanju.';
        }
      })
      .catch(() => {
        submitBtn.disabled = false;
        statusEl.textContent = 'Greška u komunikaciji sa serverom.';
      });
  });
}

function otvoriModal(n) {
  const overlay = document.getElementById('nekrModal');

  const sobeDetalj = n.sobe > 0
    ? `<div class="nekr-modal__detalj">
         <div class="nekr-modal__detalj-label">Sobe</div>
         <div class="nekr-modal__detalj-value">${n.sobe}</div>
       </div>`
    : '';

  const slike = n.slike && n.slike.length ? n.slike : [];
  const galerija = kreirajGalerijuHTML(slike, n.naziv);

  overlay.innerHTML = `
    <div class="nekr-modal">
      <button class="nekr-modal__close" id="zatvoriModal">&#x2715;</button>
      <div class="nekr-modal__img">
        ${galerija.slikaHTML}
        ${galerija.strelice}
      </div>
      ${galerija.galerijaHTML}
      <div class="nekr-modal__body">
        <p class="nekr-modal__tip">${n.tip} · ${n.kategorija}${n.istaknuta ? ' · ◆ Istaknuto' : ''}${(n.tip === 'Prodaja' && n.status_prodaje) ? ` · <span class="status-badge status-badge--${n.status_prodaje.toLowerCase()}">${n.status_prodaje}</span>` : ''}</p>
        <h2 class="nekr-modal__naziv">${n.naziv}</h2>
        <p class="nekr-modal__lokacija">📍 ${n.lokacija}</p>
        <p class="nekr-modal__opis">${n.opis}</p>
        <div class="nekr-modal__detalji">
          <div class="nekr-modal__detalj">
            <div class="nekr-modal__detalj-label">Kvadratura</div>
            <div class="nekr-modal__detalj-value">${n.kvadratura} m²</div>
          </div>
          ${sobeDetalj}
          <div class="nekr-modal__detalj">
            <div class="nekr-modal__detalj-label">Kategorija</div>
            <div class="nekr-modal__detalj-value">${n.kategorija}</div>
          </div>
        </div>

        ${n.dokument ? `<a href="${n.dokument}" target="_blank" class="btn btn--solid btn--dokument">📄 Preuzmi dokument</a>` : ''}

        ${kreirajMapuHTML(n.lat, n.lng, n.naziv)}

        ${kreirajVideoEmbedHTML(n.video_embed)}

        ${n.tip === 'Iznajmljivanje' ? `
          <div class="nekr-modal__kalendar-sekcija">
            <h3 class="nekr-modal__subtitle">Dostupnost</h3>
            <div class="nekr-modal__kalendar-legenda">
              <span><span class="dot dot--free"></span> Slobodno</span>
              <span><span class="dot dot--taken"></span> Zauzeto</span>
            </div>
            <div id="nekrModalKalendar" class="nekr-modal__kalendar">Učitavanje...</div>
          </div>
        ` : ''}

        <div class="nekr-modal__cijena">
          <div>
            <div class="nekr-modal__cijena-label">Cijena</div>
            <div class="nekr-modal__cijena-broj">${n.cijena}</div>
          </div>
          <button class="btn btn--solid" id="otvoriUpitForma">Odgovorite</button>
        </div>

        <form id="nekrUpitForma" class="nekr-modal__upit-forma" style="display:none">
          <div class="form-group">
            <input type="text" name="ime" placeholder="Vaše ime" required />
            <label>Ime i prezime</label>
          </div>
          <div class="form-group">
            <input type="email" name="email" placeholder="Email adresa" required />
            <label>Email</label>
          </div>
          <div class="form-group">
            <input type="tel" name="telefon" placeholder="Broj telefona" />
            <label>Telefon</label>
          </div>
          <div class="form-group">
            <textarea name="poruka" rows="3" placeholder="Vaša poruka" required>Zainteresovan/a sam za: ${n.naziv}</textarea>
            <label>Poruka</label>
          </div>
          <button type="submit" class="btn btn--solid">Pošaljite upit</button>
          <p class="nekr-modal__upit-status"></p>
        </form>
      </div>
    </div>`;

  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  document.getElementById('zatvoriModal').addEventListener('click', zatvoriModal);
  overlay.addEventListener('click', ev => { if (ev.target === overlay) zatvoriModal(); });

  inicijalizujKarusel(overlay, slike, n.naziv);
  ucitajInstagramEmbedSkriptuAkoTreba(n.video_embed);

  const upitBtn = document.getElementById('otvoriUpitForma');
  const upitForma = document.getElementById('nekrUpitForma');
  upitBtn.addEventListener('click', () => {
    upitForma.style.display = upitForma.style.display === 'none' ? 'flex' : 'none';
    if (upitForma.style.display === 'flex') upitForma.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  posaljiUpit(upitForma, { nekretnina_id: n.id });

  if (n.tip === 'Iznajmljivanje') {
    ucitajModalKalendar(n.id);
  }
}

// ═══════════════════════════════════════════
// ZGRADE
// ═══════════════════════════════════════════

function ucitajZgrade() {
  const grid = document.getElementById('zgradeGrid');
  if (!grid) return;
  const scope = grid.dataset.scope || 'istaknute';
  const url = scope === 'sve' ? 'api/zgrade.php' : 'api/zgrade.php?istaknute=1';

  fetch(url)
    .then(r => r.json())
    .then(data => renderZgrade(data))
    .catch(() => {
      grid.innerHTML = '<div class="nekretnine__empty">Trenutno ne možemo učitati zgrade. Pokušajte kasnije.</div>';
    });
}

function renderZgrade(lista) {
  const grid = document.getElementById('zgradeGrid');
  if (!grid) return;

  if (!lista.length) {
    grid.innerHTML = '<div class="nekretnine__empty">Zgrade uskoro.</div>';
    return;
  }

  grid.innerHTML = lista.map(z => {
    const slikaHTML = z.slika
      ? `<img src="${z.slika}" alt="${z.naziv}" loading="lazy">`
      : `<div class="nekr-card__img-placeholder">📷 Slika uskoro</div>`;

    return `
      <article class="nekr-card" data-id="${z.id}" data-type="zgrada">
        <div class="nekr-card__img">
          ${slikaHTML}
          ${z.istaknuta ? '<span class="nekr-card__badge">◆ Istaknuto</span>' : ''}
          <span class="nekr-card__badge nekr-card__badge--stanovi">${z.broj_stanova} stanova</span>
        </div>
        <div class="nekr-card__body">
          <p class="nekr-card__lokacija">📍 ${z.lokacija || ''}</p>
          <h3 class="nekr-card__naziv">${z.naziv}</h3>
          <div class="nekr-card__meta">
            ${z.godina_izgradnje ? `<span>🏗 ${z.godina_izgradnje}.</span>` : ''}
            ${z.broj_spratova ? `<span>${z.broj_spratova} spratova</span>` : ''}
          </div>
        </div>
      </article>`;
  }).join('');
}

function otvoriZgradaModal(z) {
  const overlay = document.getElementById('nekrModal');

  const slike = z.slike && z.slike.length ? z.slike : [];
  const galerija = kreirajGalerijuHTML(slike, z.naziv);

  const videoHTML = kreirajVideoEmbedHTML(z.video_embed);

  const dokumentHTML = z.dokument
    ? `<a href="${z.dokument}" target="_blank" class="btn btn--solid btn--dokument">📄 Preuzmi dokument</a>`
    : '';

  const stanoviHTML = z.stanovi && z.stanovi.length
    ? z.stanovi.map(s => `
        <div class="zgrada-stan" data-stan-id="${s.id}">
          <div class="zgrada-stan__img">
            ${s.slika ? `<img src="${s.slika}" alt="${s.naziv}" loading="lazy">` : '<div class="nekr-card__img-placeholder">📷</div>'}
          </div>
          <div class="zgrada-stan__info">
            <p class="zgrada-stan__tip">${s.tip} · ${s.kategorija}${(s.tip === 'Prodaja' && s.status_prodaje) ? ` · <span class="status-badge status-badge--${s.status_prodaje.toLowerCase()}">${s.status_prodaje}</span>` : ''}</p>
            <h4>${s.naziv}</h4>
            <p class="zgrada-stan__meta">${s.kvadratura} m²${s.sobe > 0 ? ' · ' + s.sobe + ' sobe' : ''}</p>
          </div>
          <div class="zgrada-stan__cijena">${s.cijena}</div>
        </div>
      `).join('')
    : '<p class="empty-state" style="padding:20px 0">Trenutno nema oglašenih stanova u ovoj zgradi.</p>';

  overlay.innerHTML = `
    <div class="nekr-modal">
      <button class="nekr-modal__close" id="zatvoriModal">&#x2715;</button>
      <div class="nekr-modal__img">
        ${galerija.slikaHTML}
        ${galerija.strelice}
      </div>
      ${galerija.galerijaHTML}
      <div class="nekr-modal__body">
        <p class="nekr-modal__tip">Zgrada${z.istaknuta ? ' · ◆ Istaknuto' : ''}</p>
        <h2 class="nekr-modal__naziv">${z.naziv}</h2>
        <p class="nekr-modal__lokacija">📍 ${z.adresa ? z.adresa + ', ' : ''}${z.lokacija || ''}</p>
        <p class="nekr-modal__opis">${z.opis || ''}</p>

        <div class="nekr-modal__detalji">
          ${z.godina_izgradnje ? `
            <div class="nekr-modal__detalj">
              <div class="nekr-modal__detalj-label">Godina izgradnje</div>
              <div class="nekr-modal__detalj-value">${z.godina_izgradnje}</div>
            </div>` : ''}
          ${z.broj_spratova ? `
            <div class="nekr-modal__detalj">
              <div class="nekr-modal__detalj-label">Broj spratova</div>
              <div class="nekr-modal__detalj-value">${z.broj_spratova}</div>
            </div>` : ''}
          ${z.lokacija ? `
            <div class="nekr-modal__detalj">
              <div class="nekr-modal__detalj-label">Grad</div>
              <div class="nekr-modal__detalj-value">${z.lokacija}</div>
            </div>` : ''}
        </div>

        ${dokumentHTML}
        ${videoHTML}
        ${kreirajMapuHTML(z.lat, z.lng, z.naziv)}

        <div class="nekr-modal__kalendar-sekcija">
          <h3 class="nekr-modal__subtitle">Stanovi u ovoj zgradi</h3>
          <div class="zgrada-stanovi-lista">
            ${stanoviHTML}
          </div>
        </div>

        <div class="nekr-modal__cijena">
          <div>
            <div class="nekr-modal__cijena-label">Zainteresovani za zgradu?</div>
          </div>
          <button class="btn btn--solid" id="otvoriUpitForma">Pošaljite upit</button>
        </div>

        <form id="nekrUpitForma" class="nekr-modal__upit-forma" style="display:none">
          <div class="form-group">
            <input type="text" name="ime" placeholder="Vaše ime" required />
            <label>Ime i prezime</label>
          </div>
          <div class="form-group">
            <input type="email" name="email" placeholder="Email adresa" required />
            <label>Email</label>
          </div>
          <div class="form-group">
            <input type="tel" name="telefon" placeholder="Broj telefona" />
            <label>Telefon</label>
          </div>
          <div class="form-group">
            <textarea name="poruka" rows="3" placeholder="Vaša poruka" required>Zainteresovan/a sam za zgradu: ${z.naziv}</textarea>
            <label>Poruka</label>
          </div>
          <button type="submit" class="btn btn--solid">Pošaljite upit</button>
          <p class="nekr-modal__upit-status"></p>
        </form>
      </div>
    </div>`;

  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  document.getElementById('zatvoriModal').addEventListener('click', zatvoriModal);
  overlay.addEventListener('click', ev => { if (ev.target === overlay) zatvoriModal(); });

  inicijalizujKarusel(overlay, slike, z.naziv);
  ucitajInstagramEmbedSkriptuAkoTreba(z.video_embed);

  const upitBtn = document.getElementById('otvoriUpitForma');
  const upitForma = document.getElementById('nekrUpitForma');
  upitBtn.addEventListener('click', () => {
    upitForma.style.display = upitForma.style.display === 'none' ? 'flex' : 'none';
    if (upitForma.style.display === 'flex') upitForma.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  posaljiUpit(upitForma, { zgrada_id: z.id });

  overlay.querySelectorAll('.zgrada-stan').forEach(el => {
    el.addEventListener('click', () => {
      const stanId = parseInt(el.dataset.stanId, 10);
      fetch(`api/nekretnina-detalji.php?id=${stanId}`)
        .then(r => r.json())
        .then(n => {
          if (n.error) return;
          otvoriModal(n);
        });
    });
  });
}

function ucitajModalKalendar(id) {
  fetch(`api/dostupnost.php?id=${id}`)
    .then(r => r.json())
    .then(statusi => {
      const el = document.getElementById('nekrModalKalendar');
      if (!el) return;
      el.innerHTML = renderMiniKalendar(statusi);
    })
    .catch(() => {
      const el = document.getElementById('nekrModalKalendar');
      if (el) el.textContent = 'Kalendar trenutno nije dostupan.';
    });
}

function renderMiniKalendar(statusi) {
  const MESECI = ['Jan','Feb','Mar','Apr','Maj','Jun','Jul','Avg','Sep','Okt','Nov','Dec'];
  const today = new Date();
  let html = '';

  for (let m = 0; m < 2; m++) {
    const year = today.getFullYear() + Math.floor((today.getMonth() + m) / 12);
    const month = (today.getMonth() + m) % 12;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    let startDay = new Date(year, month, 1).getDay();
    startDay = startDay === 0 ? 6 : startDay - 1;

    html += `<div class="mini-cal"><div class="mini-cal__title">${MESECI[month]} ${year}</div><div class="mini-cal__grid">`;
    for (let i = 0; i < startDay; i++) html += '<span class="mini-cal__day mini-cal__day--empty"></span>';
    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const zauzeto = statusi[dateStr] === 'zauzeto';
      html += `<span class="mini-cal__day ${zauzeto ? 'is-taken' : 'is-free'}">${d}</span>`;
    }
    html += '</div></div>';
  }

  return html;
}

function zatvoriModal() {
  const overlay = document.getElementById('nekrModal');
  if (!overlay) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
  if (overlay._tastaturaKarusel) {
    document.removeEventListener('keydown', overlay._tastaturaKarusel);
    overlay._tastaturaKarusel = null;
  }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') zatvoriModal(); });

// ── START ────────────────────────────────────
ucitajNekretnine();
ucitajZgrade();
