# JA Nekretnine

Web sajt agencije za nekretnine sa admin panelom za upravljanje nekretninama, zgradama i upitima kupaca.
Produkcija: [janekretnine.com](https://janekretnine.com)

## Funkcionalnosti

**Javni deo**
- Pregled zgrada (projekata) i nekretnina (stanovi, kuće, poslovni prostori) — prodaja i iznajmljivanje
- Detalji nekretnine: galerija slika i videa, kvadratura, broj soba, status (slobodno / rezervisano / prodato)
- Detalji zgrade: galerija, video, PDF katalog za preuzimanje
- Forma za upit vezan za konkretnu nekretninu ili zgradu, sa slanjem emaila preko SMTP-a
- Kontakt forma

**Admin panel**
- Dodavanje, izmena i brisanje nekretnina i zgrada
- Upload više slika i MP4 videa, uz automatsku kompresiju i uklanjanje EXIF podataka
- Brza promena statusa prodaje (AJAX)
- Kalendar dostupnosti za nekretnine koje se iznajmljuju
- Pregled upita i slanje odgovora kupcu emailom

## Tehnologije

| Sloj | Tehnologija |
|---|---|
| Backend | PHP 8.0+ (bez frameworka), PDO |
| Baza | MySQL / MariaDB |
| Frontend | HTML, CSS, vanilla JavaScript (javni JSON API u `api/`) |
| Email | PHPMailer (SMTP) |
| Hosting | Hostinger (Apache) |

## Struktura projekta

```
├── index.html                  # početna stranica
├── nekretnine-sve.php          # lista svih nekretnina
├── zgrade-sve.php              # lista svih zgrada
├── contact.php                 # obrada kontakt forme
├── api/                        # javni JSON API (nekretnine, zgrade, detalji, dostupnost, upit)
├── admin-nekretnine-x7f2k9m/   # admin panel
├── config/
│   ├── config.example.php      # šablon konfiguracije (commitovan)
│   ├── config.php              # prava konfiguracija (NIJE u gitu)
│   └── database.php            # PDO konekcija
├── includes/                   # auth, CSRF, upload i pomoćne funkcije
├── phpmailer/                  # PHPMailer biblioteka
├── setup/                      # jednokratne skripte (kreiranje admina, demo podaci)
├── database/schema.sql         # šema baze bez podataka
├── uploads/                    # slike, video i dokumenti (NIJE u gitu)
├── css/  js/  images/
```

## Pokretanje lokalno

1. Kloniraj repozitorijum:
   ```bash
   git clone https://github.com/<korisnik>/ja-nekretnine.git
   cd ja-nekretnine
   ```
2. Napravi bazu i uvezi šemu:
   ```bash
   mysql -u root -p -e "CREATE DATABASE janek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p janek < database/schema.sql
   ```
3. Napravi konfiguraciju iz šablona i popuni podatke za bazu i SMTP:
   ```bash
   cp config/config.example.php config/config.php
   ```
4. Pokreni PHP server:
   ```bash
   php -S localhost:8000
   ```
5. Otvori `http://localhost:8000/setup/create-admin.php` i napravi admin nalog.
   Opcionalno, `setup/seed-demo-podaci.php` ubacuje demo nekretnine.
6. Admin panel: `http://localhost:8000/admin-nekretnine-x7f2k9m/login.php`

## Deploy na Hostinger

1. Upload fajlova u `public_html` (File Manager, FTP ili Hostinger Git integracija).
2. Na serveru napravi `config/config.php` sa produkcionim podacima. Taj fajl se ne nalazi u repozitorijumu.
3. Uvezi `database/schema.sql` kroz phpMyAdmin (samo za novu instalaciju).
4. Posle kreiranja admin naloga obriši folder `setup/` sa servera.

## Bezbednost

- Lozinke su heširane (`password_hash`), svi upiti idu kroz PDO prepared statements
- CSRF token na svakoj formi koja menja podatke
- Zaključavanje naloga na 15 minuta posle 5 neuspešnih prijava
- Upload proverava pravi MIME tip, slike se ponovo iscrtavaju i snimaju pod nasumičnim imenom
- `uploads/` ima `.htaccess` koji zabranjuje izvršavanje skripti
- Sesijski kolačići su HttpOnly, Secure i SameSite=Strict

## Autor

Miloš Milosavljević
