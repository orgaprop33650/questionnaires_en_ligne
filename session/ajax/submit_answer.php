<?php
require_once __DIR__ . '/../../config.php';

$participant_id      = $_SESSION['participant_id'] ?? 0;
$session_id          = $_SESSION['session_id'] ?? 0;
$question_id         = (int)($_POST['question_id'] ?? 0);
$reponse_possible_id = (int)($_POST['reponse_possible_id'] ?? 0);
$texte_libre         = trim($_POST['texte_libre'] ?? '');

if (!$participant_id) {
    json_response(['success' => false, 'error' => 'Non authentifié.'], 401);
}

// Vérifier que la question est bien active dans cette session
$stmt = $pdo->prepare('
    SELECT s.question_active_id, s.question_demarree_a, q.duree_secondes
    FROM sessions s
    JOIN questions q ON q.id = s.question_active_id
    WHERE s.id = ? AND s.question_active_id = ?
');
$stmt->execute([$session_id, $question_id]);
$session = $stmt->fetch();

if (!$session) {
    json_response(['success' => false, 'error' => 'Cette question n\'est plus active.'], 400);
}

// Vérifier le chrono côté serveur (anti-triche)
$stmt = $pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) AS elapsed');
$stmt->execute([$session['question_demarree_a']]);
$elapsed = (int)$stmt->fetchColumn();

if ($elapsed > $session['duree_secondes']) {
    json_response(['success' => false, 'error' => 'Le temps est écoulé !'], 400);
}

// Vérifier qu'il n'a pas déjà répondu
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reponses WHERE participant_id = ? AND question_id = ?');
$stmt->execute([$participant_id, $question_id]);
if ($stmt->fetchColumn() > 0) {
    json_response(['success' => false, 'error' => 'Vous avez déjà répondu.'], 400);
}

// Insérer la réponse
$stmt = $pdo->prepare('
    INSERT INTO reponses (participant_id, question_id, reponse_possible_id, texte_libre)
    VALUES (?, ?, ?, ?)
');
$stmt->execute([
    $participant_id,
    $question_id,
    $reponse_possible_id ?: null,
    $texte_libre ?: null,
]);

json_response(['success' => true]);
