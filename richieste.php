<?php

$PASSWORD     = 'ilsignoredeglianelli';          // ← la tua password per l'area riservata
$DATA_DIR     = __DIR__ . '/richieste-privato';     // cartella protetta dove finiscono i messaggi
$DATA_FILE    = $DATA_DIR . '/richieste.txt';

// Facoltativo: ricevere anche un'email a ogni messaggio (funziona solo se l'hosting supporta mail()).
$NOTIFY_EMAIL = '';                                 // es. 'tuonome@email.com' — lascia vuoto per disattivare

date_default_timezone_set('Europe/Rome');

$ALLOWED_TYPES = ['ux-ui', 'html-css', 'graphic-design', 'collaboration', 'other'];

/* ---------- 1. RICEZIONE DEL FORM ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json; charset=utf-8');
    $reply = function ($code, $ok, $msg) {
        http_response_code($code);
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    };

    // campo trappola: se è compilato è un bot, fingiamo che sia andato tutto bene
    if (!empty($_POST['botcheck'])) $reply(200, true, 'OK');

    $clean = function ($v, $max) {
        $v = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$v)));
        return mb_substr($v, 0, $max);
    };
    $name       = $clean($_POST['name'] ?? '', 100);
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $type       = in_array($_POST['request_type'] ?? '', $ALLOWED_TYPES, true) ? $_POST['request_type'] : 'other';
    $typeLabel  = $clean($_POST['request_label'] ?? $type, 80);
    $message    = mb_substr(trim(strip_tags((string)($_POST['message'] ?? ''))), 0, 5000);
    $lang       = ($_POST['language'] ?? '') === 'it' ? 'it' : 'en';

    if ($name === '' || !$email || mb_strlen($message) < 10) $reply(400, false, 'Invalid data');

    // limite semplice: massimo un messaggio ogni 30 secondi dallo stesso indirizzo
    if (!is_dir($DATA_DIR) && !mkdir($DATA_DIR, 0755, true)) $reply(500, false, 'Storage error');
    // se manca, crea il file che blocca l'accesso diretto alla cartella dei messaggi
    if (!is_file($DATA_DIR . '/.htaccess')) {
        @file_put_contents($DATA_DIR . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    $ipHash   = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'mn-salt'), 0, 16);
    $rateFile = $DATA_DIR . '/.rate-' . $ipHash;
    if (file_exists($rateFile) && time() - filemtime($rateFile) < 30) $reply(429, false, 'Too many requests');
    @touch($rateFile);

    $entry = '[' . date('Y-m-d H:i') . "] ($lang)\n"
           . "Nome: $name\nEmail: $email\nTipo: $typeLabel\nMessaggio:\n$message\n"
           . str_repeat('-', 40) . "\n";

    if (file_put_contents($DATA_FILE, $entry, FILE_APPEND | LOCK_EX) === false) $reply(500, false, 'Storage error');

    if ($NOTIFY_EMAIL !== '') {
        $subject = '=?UTF-8?B?' . base64_encode("Nuovo messaggio dal portfolio · $typeLabel") . '?=';
        $headers = "Content-Type: text/plain; charset=utf-8\r\nReply-To: $email\r\n";
        @mail($NOTIFY_EMAIL, $subject, $entry, $headers);
    }
    $reply(200, true, 'OK');
}

/* ---------- 2. AREA RISERVATA PER LEGGERE I MESSAGGI ---------- */
session_start();
if (isset($_GET['logout'])) { session_destroy(); header('Location: richieste.php'); exit; }

$error = '';
if (isset($_POST['pass'])) {
    if (hash_equals($PASSWORD, (string)$_POST['pass']) && $PASSWORD !== 'CAMBIA-QUESTA-PASSWORD') {
        session_regenerate_id(true);
        $_SESSION['mn_ok'] = true;
    } else {
        sleep(1);
        $error = $PASSWORD === 'CAMBIA-QUESTA-PASSWORD'
            ? 'Imposta prima una password in richieste.php.'
            : 'Password errata.';
    }
}

$page = function ($inner) {
    echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex, nofollow"><title>Richieste · area riservata</title>'
       . '<style>body{margin:0;background:#f5f6f2;color:#101010;font-family:"DM Sans",system-ui,sans-serif}'
       . '.w{max-width:820px;margin:48px auto;padding:0 20px}h1{font-weight:600;font-size:1.6rem;margin:0 0 1.2rem}'
       . 'form{display:flex;gap:.6rem;flex-wrap:wrap}input{padding:.7rem 1rem;border:1px solid #c8cac4;border-radius:999px;font:inherit;min-width:240px}'
       . 'button,.b{padding:.7rem 1.3rem;border:0;border-radius:999px;background:#c0df97;font:inherit;font-weight:600;cursor:pointer;color:#101010;text-decoration:none}'
       . '.e{color:#b3342e;margin-top:.8rem}.m{background:#fff;border:1px solid #c8cac4;border-radius:16px;padding:1rem 1.2rem;margin:0 0 .8rem;white-space:pre-wrap;font-size:.95rem;line-height:1.5}'
       . '.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem}</style></head><body><div class="w">'
       . $inner . '</div></body></html>';
    exit;
};

if (empty($_SESSION['mn_ok'])) {
    $page('<h1>Area riservata richieste</h1><form method="POST"><input type="password" name="pass" placeholder="Password" required autofocus><button type="submit">Accedi</button></form>'
        . ($error ? '<p class="e">' . htmlspecialchars($error) . '</p>' : ''));
}

$html = '<div class="top"><h1>Messaggi ricevuti</h1><a class="b" href="?logout=1">Esci</a></div>';
if (is_file($DATA_FILE)) {
    $blocks = array_filter(array_map('trim', explode(str_repeat('-', 40), file_get_contents($DATA_FILE))));
    $blocks = array_reverse($blocks); // i più recenti in alto
    foreach ($blocks as $b) $html .= '<div class="m">' . htmlspecialchars($b) . '</div>';
    if (!$blocks) $html .= '<p>Nessun messaggio.</p>';
} else {
    $html .= '<p>Nessun messaggio ancora.</p>';
}
$page($html);
