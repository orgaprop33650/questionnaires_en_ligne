<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'questionnaires_en_ligne');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/questionnaires_en_ligne');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

/**
 * Échappe une chaîne pour affichage HTML
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige vers une URL et arrête le script
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Retourne une réponse JSON et arrête le script
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Génère un code d'accès unique de 6 caractères
 */
function generer_code_acces(PDO $pdo): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE code_acces = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn() > 0);
    return $code;
}
