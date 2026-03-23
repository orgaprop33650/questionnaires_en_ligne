<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$session_id  = (int)($_POST['session_id'] ?? 0);
$question_id = (int)($_POST['question_id'] ?? 0);

// Vérifier propriété de la session
$stmt = $pdo->prepare('
    SELECT s.id FROM sessions s
    JOIN questionnaires q ON q.id = s.questionnaire_id
    WHERE s.id = ? AND q.formateur_id = ?
');
$stmt->execute([$session_id, get_formateur_id()]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Session introuvable.'], 404);
}

// Vérifier que la question existe dans ce questionnaire
$stmt = $pdo->prepare('
    SELECT qu.id FROM questions qu
    JOIN sessions s ON s.questionnaire_id = qu.questionnaire_id
    WHERE qu.id = ? AND s.id = ?
');
$stmt->execute([$question_id, $session_id]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Question introuvable.'], 404);
}

$stmt = $pdo->prepare('
    UPDATE sessions
    SET question_active_id = ?, question_demarree_a = NOW(), statut = "en_cours"
    WHERE id = ?
');
$stmt->execute([$question_id, $session_id]);

json_response(['success' => true]);
