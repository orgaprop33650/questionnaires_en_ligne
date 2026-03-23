<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$session_id = (int)($_POST['session_id'] ?? 0);

// Vérifier propriété et forcer le chrono à expirer pour montrer les résultats
$stmt = $pdo->prepare('
    SELECT s.id FROM sessions s
    JOIN questionnaires q ON q.id = s.questionnaire_id
    WHERE s.id = ? AND q.formateur_id = ?
');
$stmt->execute([$session_id, get_formateur_id()]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Session introuvable.'], 404);
}

// Mettre le chrono à zéro en remettant question_demarree_a loin dans le passé
$stmt = $pdo->prepare('
    UPDATE sessions
    SET question_demarree_a = DATE_SUB(NOW(), INTERVAL 1 HOUR)
    WHERE id = ?
');
$stmt->execute([$session_id]);

json_response(['success' => true]);
