<?php
require_once __DIR__ . '/../../config.php';

$session_id     = (int)($_GET['session_id'] ?? 0);
$participant_id = $_SESSION['participant_id'] ?? 0;

if (!$participant_id) {
    json_response(['success' => false, 'error' => 'Non authentifié.'], 401);
}

$stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ?');
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    json_response(['success' => false, 'error' => 'Session introuvable.'], 404);
}

$result = [
    'success' => true,
    'statut'  => $session['statut'],
    'question' => null,
    'reponses_possibles' => [],
    'temps_restant' => null,
    'deja_repondu' => false,
];

if ($session['statut'] === 'terminee') {
    json_response($result);
}

if ($session['question_active_id']) {
    $stmt = $pdo->prepare('SELECT id, texte, image, type, duree_secondes FROM questions WHERE id = ?');
    $stmt->execute([$session['question_active_id']]);
    $question = $stmt->fetch();
    $result['question'] = $question;

    // Temps restant
    $stmt = $pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, question_demarree_a, NOW()) AS elapsed FROM sessions WHERE id = ?');
    $stmt->execute([$session_id]);
    $elapsed = (int)$stmt->fetchColumn();
    $result['temps_restant'] = max(0, $question['duree_secondes'] - $elapsed);

    // Réponses possibles (ne pas envoyer est_correcte !)
    if ($question['type'] !== 'libre') {
        $stmt = $pdo->prepare('SELECT id, texte FROM reponses_possibles WHERE question_id = ? ORDER BY ordre');
        $stmt->execute([$question['id']]);
        $result['reponses_possibles'] = $stmt->fetchAll();
    }

    // Déjà répondu ?
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reponses WHERE participant_id = ? AND question_id = ?');
    $stmt->execute([$participant_id, $question['id']]);
    $result['deja_repondu'] = $stmt->fetchColumn() > 0;
}

json_response($result);
