<?php
// Formulaire du portfolio : envoie la demande par e-mail.
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$TO   = 'zoubgakmdieudonne@gmail.com';
$FROM = 'portfolio@zoubgakmb.click';

function field($k, $max = 2000) {
    $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
    $v = str_replace(["\r", "\0"], '', $v);
    return mb_substr($v, 0, $max, 'UTF-8');
}

// Piège à robots : ce champ invisible doit rester vide.
if (field('website') !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

$name    = field('name', 120);
$company = field('company', 160);
$email   = field('email', 160);
$phone   = field('phone', 60);
$country = field('country', 80);
$sector  = field('sector', 120);
$budget  = field('budget', 120);
$goal    = field('goal', 120);
$message = field('message', 4000);
$lang    = field('lang', 4) === 'en' ? 'EN' : 'FR';

if ($name === '' || $company === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || field('consent', 2) !== '1') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

// Limite simple : une demande toutes les 30 secondes par adresse IP.
$ip   = $_SERVER['REMOTE_ADDR'] ?? 'x';
$lock = sys_get_temp_dir() . '/zk_form_' . md5($ip);
if (is_file($lock) && (time() - filemtime($lock)) < 30) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'wait']);
    exit;
}
@touch($lock);

$oneLine = function ($s) { return str_replace("\n", ' ', $s); };

$body  = "Nouvelle demande depuis zoubgakmb.click (version $lang)\n\n";
$body .= "Nom : $name\n";
$body .= "Entreprise : $company\n";
$body .= "E-mail : $email\n";
$body .= "Téléphone : $phone\n";
$body .= "Pays : $country\n";
$body .= "Secteur : $sector\n";
$body .= "Budget : $budget\n";
$body .= "Objectif : $goal\n\n";
$body .= "Produits ou services :\n$message\n\n";
$body .= "Le client a confirmé : demande sérieuse, budget publicitaire prévu, accepte d'être recontacté.\n\n";
$body .= "---\nRépondez directement à cet e-mail pour écrire à $name.\n";

$subject = '=?UTF-8?B?' . base64_encode('Nouvelle demande : ' . $oneLine($company) . ' (' . $oneLine($name) . ')') . '?=';
$fromName = '=?UTF-8?B?' . base64_encode('Portfolio Zoubga Kmb Dieudonné') . '?=';

$headers  = "From: $fromName <$FROM>\r\n";
$headers .= "Reply-To: " . $oneLine($email) . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = @mail($TO, $subject, $body, $headers, '-f' . $FROM);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'mail']);
}
