<?php
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="bs">
<head>
  <link rel="icon" type="image/png" href="images/logo.png" />
  <link rel="apple-touch-icon" href="images/logo.png" />
  <meta name="theme-color" content="#0d0d0d" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sve nekretnine – JA Nekretnine</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/style2.css" />
</head>
<body>

  <!-- NAV -->
  <nav class="nav scrolled" id="nav">
    <div class="nav__inner">
      <a href="index.html" class="nav__logo">
        <img src="images/logo2.png" alt="JA Nekretnine" class="nav__logo-img" />
      </a>
      <button class="nav__burger" id="burger" aria-label="Meni">
        <span></span><span></span><span></span>
      </button>
      <ul class="nav__links" id="navLinks">
        <li><a href="index.html#hero">Početak</a></li>
        <li><a href="index.html#about">Ko smo mi</a></li>
        <li><a href="index.html#services">Usluge</a></li>
        <li><a href="nekretnine-sve.php">Nekretnine</a></li>
        <li><a href="zgrade-sve.php">Zgrade</a></li>
        <li><a href="index.html#philosophy">Filozofija</a></li>
        <li><a href="index.html#contact" class="nav__cta">Kontakt</a></li>
      </ul>
    </div>
  </nav>

  <!-- HEADER -->
  <header class="page-header">
    <div class="container">
      <a href="index.html" class="page-header__back">&larr; Nazad na početnu</a>
      <h1>Sve naše nekretnine</h1>
    </div>
  </header>

  <!-- NEKRETNINE -->
  <section class="section nekretnine" id="nekretnine" style="padding-top:0">
    <div class="container">

      <div class="nekretnine__filters reveal">
        <button class="filter-btn active" data-filter="sve">Sve</button>
        <button class="filter-btn" data-filter="Prodaja">Prodaja</button>
        <button class="filter-btn" data-filter="Iznajmljivanje">Iznajmljivanje</button>
        <button class="filter-btn" data-filter="Stan">Stanovi</button>
        <button class="filter-btn" data-filter="Kuca">Kuće</button>
        <button class="filter-btn" data-filter="Poslovni prostor">Poslovni</button>
      </div>

      <!-- Kartice se ubacuju automatski putem js/main.js (SVE aktivne nekretnine) -->
      <div class="nekretnine__grid" id="nekretnineGrid" data-scope="sve"></div>

    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container footer__inner">
      <div class="footer__col footer__col--brand">
        <span class="footer__logo">JA <em>Nekretnine</em></span>
        <p class="footer__tagline">Ja sam ime i garancija.</p>
        <div class="footer__values">
          <span>Diskrecija</span>
          <span>Preciznost</span>
          <span>Standard</span>
        </div>
      </div>
      <div class="footer__col footer__col--links">
        <h4 class="footer__heading">Navigacija</h4>
        <nav class="footer__nav">
          <a href="index.html#hero">Početak</a>
          <a href="index.html#about">Ko smo mi</a>
          <a href="index.html#services">Usluge</a>
          <a href="nekretnine-sve.php">Nekretnine</a>
          <a href="zgrade-sve.php">Zgrade</a>
          <a href="index.html#philosophy">Filozofija</a>
          <a href="index.html#contact">Kontakt</a>
        </nav>
      </div>
      <div class="footer__col footer__col--contact">
        <h4 class="footer__heading">Kontakt</h4>
        <div class="footer__contact-list">
          <a href="tel:+387 65 903 933">
            <svg viewBox="0 0 20 20" fill="none"><path d="M3 3h4l2 4-2 2a12 12 0 005 5l2-2 4 2v4a2 2 0 01-2 2C7 19 1 13 1 5a2 2 0 012-2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
            +387 65 903 933
          </a>
          <a href="index.html#contact">
            <svg viewBox="0 0 20 20" fill="none"><rect x="2" y="4" width="16" height="12" rx="1" stroke="currentColor" stroke-width="1.3"/><path d="M2 5l8 7 8-7" stroke="currentColor" stroke-width="1.3"/></svg>
            janekretnine@gmail.com
          </a>
          <span>
            <svg viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 016 6c0 4-6 10-6 10S4 12 4 8a6 6 0 016-6z" stroke="currentColor" stroke-width="1.3"/><circle cx="10" cy="8" r="2" stroke="currentColor" stroke-width="1.3"/></svg>
            Cerska 7, Starčevica, Banja Luka, Bosna i Hercegovina
          </span>
        </div>
      </div>
    </div>
    <div class="footer__bottom">
      <div class="container footer__bottom-inner">
        <p>&copy; 2026 JA Nekretnine. Sva prava zadržana.</p>
        <p>Diskrecija garantovana.</p>
      </div>
    </div>
  </footer>

  <!-- Modal se generiše dinamički iz main.js -->
  <div class="nekr-modal-overlay" id="nekrModal"></div>

  <script src="js/main.js?v=10"></script>
</body>
</html>
