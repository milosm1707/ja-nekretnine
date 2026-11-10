<?php

function clean($v): string {
    return htmlspecialchars(trim((string)($v ?? '')), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/**
 * Sigurno snimanje uploadovane slike na disk.
 * - proverava pravi MIME tip (ne veruje ekstenziji)
 * - proverava da je zaista slika
 * - snima pod nasumičnim imenom
 * - ponovo iscrtava sliku da bi se uklonili EXIF/meta podaci
 *
 * Vraća ime fajla (bez putanje) ili baca Exception sa porukom za korisnika.
 */
/**
 * Ispravlja orijentaciju slike na osnovu EXIF podatka (telefoni često snime
 * sliku "sirovo" i samo zabeleže u EXIF-u da treba da se rotira pri prikazu).
 * Pošto mi brišemo EXIF radi bezbednosti, moramo fizički da rotiramo sliku
 * pre toga, inače bi na sajtu ispala okrenuta na stranu.
 */
function ispravi_orijentaciju($image, int $orientation) {
    switch ($orientation) {
        case 3:
            return imagerotate($image, 180, 0);
        case 6:
            return imagerotate($image, -90, 0);
        case 8:
            return imagerotate($image, 90, 0);
        default:
            return $image;
    }
}

function save_uploaded_image(array $file, string $targetDir = UPLOAD_DIR): string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Fajl nije ispravno poslat.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Greška prilikom slanja fajla.');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $maxMb = (int)(MAX_UPLOAD_SIZE / 1024 / 1024);
        throw new Exception("Slika je prevelika (maksimalno {$maxMb}MB).");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new Exception('Dozvoljeni formati slika su samo JPG, PNG i WEBP.');
    }

    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        throw new Exception('Fajl nije validna slika.');
    }

    switch ($mime) {
        case 'image/jpeg':
            $ext = 'jpg';
            $image = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $ext = 'png';
            $image = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $ext = 'webp';
            $image = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new Exception('Nepodržan format slike.');
    }

    if ($image === false) {
        throw new Exception('Greška pri obradi slike.');
    }

    // ispravi orijentaciju ako telefon/foto-aparat čuva EXIF rotaciju (samo za JPEG)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        if ($exif && !empty($exif['Orientation'])) {
            $image = ispravi_orijentaciju($image, (int)$exif['Orientation']);
        }
    }

    // smanji rezoluciju ako je veća od dozvoljenog maksimuma — glavni razlog zašto
    // slike sa telefona (10-15MB) postanu par stotina KB, bez vidljivog gubitka kvaliteta na ekranu
    $sirina = imagesx($image);
    $visina = imagesy($image);
    if ($sirina > MAX_IMAGE_DIMENSION || $visina > MAX_IMAGE_DIMENSION) {
        $razmera = min(MAX_IMAGE_DIMENSION / $sirina, MAX_IMAGE_DIMENSION / $visina);
        $novaSirina = (int)round($sirina * $razmera);
        $novaVisina = (int)round($visina * $razmera);

        $smanjena = imagecreatetruecolor($novaSirina, $novaVisina);
        if ($ext === 'png') {
            imagealphablending($smanjena, false);
            imagesavealpha($smanjena, true);
        }
        imagecopyresampled($smanjena, $image, 0, 0, 0, 0, $novaSirina, $novaVisina, $sirina, $visina);
        imagedestroy($image);
        $image = $smanjena;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $targetDir . $filename;

    $ok = match ($ext) {
        'jpg'  => imagejpeg($image, $destination, 82),
        'png'  => imagepng($image, $destination, 6),
        'webp' => imagewebp($image, $destination, 82),
    };
    imagedestroy($image);

    if (!$ok) {
        throw new Exception('Greška pri snimanju slike na server.');
    }

    return $filename;
}

function delete_image_file(string $filename, string $targetDir = UPLOAD_DIR): void {
    $path = $targetDir . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}


/**
 * Sigurno snimanje uploadovanog PDF dokumenta (npr. brošura/tlocrt zgrade).
 * Vraća ime fajla (bez putanje) ili baca Exception sa porukom za korisnika.
 */
function save_uploaded_document(array $file): string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Fajl nije ispravno poslat.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Greška prilikom slanja fajla.');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE * 4) { // dozvoli malo veće PDF-ove (do 20MB)
        throw new Exception('Dokument je prevelik (maksimalno 20MB).');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        throw new Exception('Dokument mora biti u PDF formatu.');
    }

    if (!is_dir(UPLOAD_DOC_DIR)) {
        mkdir(UPLOAD_DOC_DIR, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.pdf';
    $destination = UPLOAD_DOC_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Greška pri snimanju dokumenta na server.');
    }

    return $filename;
}

function delete_document_file(string $filename): void {
    $path = UPLOAD_DOC_DIR . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Prepoznaje YouTube / Vimeo / Instagram link i vraća podatke potrebne za embed.
 * Vraća null ako link nije prepoznat.
 * - youtube/vimeo: ['platform' => ..., 'embed_url' => '...pogodno za <iframe>']
 * - instagram: ['platform' => 'instagram', 'post_url' => '...kanonski link posta, za blockquote embed']
 */
function video_embed_info(?string $url): ?array {
    if (!$url) return null;

    if (preg_match('~youtu\.be/([a-zA-Z0-9_-]+)~', $url, $m) || preg_match('~youtube\.com/watch\?v=([a-zA-Z0-9_-]+)~', $url, $m) || preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]+)~', $url, $m)) {
        return ['platform' => 'youtube', 'embed_url' => 'https://www.youtube.com/embed/' . $m[1]];
    }
    if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
        return ['platform' => 'vimeo', 'embed_url' => 'https://player.vimeo.com/video/' . $m[1]];
    }
    if (preg_match('~instagram\.com/(reel|p|tv)/([a-zA-Z0-9_-]+)~', $url, $m)) {
        return ['platform' => 'instagram', 'post_url' => 'https://www.instagram.com/' . $m[1] . '/' . $m[2] . '/'];
    }
    return null;
}

/**
 * Sigurno snimanje uploadovanog MP4 fajla (video u galeriji, uz slike).
 * Ne postoji way da se video "ponovo iscrta" kao kod slika, pa se samo
 * proverava pravi MIME tip i snima pod nasumičnim imenom.
 */
function save_uploaded_video(array $file, string $targetDir): string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Fajl nije ispravno poslat.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Greška prilikom slanja fajla.');
    }
    if ($file['size'] > MAX_VIDEO_SIZE) {
        $maxMb = (int)(MAX_VIDEO_SIZE / 1024 / 1024);
        throw new Exception("Video je prevelik (maksimalno {$maxMb}MB).");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_VIDEO_TYPES, true)) {
        throw new Exception('Dozvoljen je samo MP4 format za video.');
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.mp4';
    $destination = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Greška pri snimanju videa na server.');
    }

    return $filename;
}

