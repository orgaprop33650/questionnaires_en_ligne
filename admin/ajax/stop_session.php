<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$session_id = (int)($_POST['session_id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT s.id, s.questionnaire_id FROM sessions s
    JOIN questionnaires q ON q.id = s.questionnaire_id
    WHERE s.id = ? AND q.formateur_id = ?
');
$stmt->execute([$session_id, get_formateur_id()]);
$session = $stmt->fetch();

if (!$session) {
    json_response(['success' => false, 'error' => 'Session introuvable.'], 404);
}

$stmt = $pdo->prepare('
    UPDATE sessions SET statut = "terminee", date_fin = NOW(), question_active_id = NULL
    WHERE id = ?
');
$stmt->execute([$session_id]);

// Récapitulatif
$stmt = $pdo->prepare('
    SELECT qu.texte, COUNT(r.id) AS nb_reponses
    FROM questions qu
    LEFT JOIN reponses r ON r.question_id = qu.id
        AND r.participant_id IN (SELECT id FROM participants WHERE session_id = ?)
    WHERE qu.questionnaire_id = ?
    GROUP BY qu.id
    ORDER BY qu.ordre, qu.id
');
$stmt->execute([$session_id, $session['questionnaire_id']]);
$recap = $stmt->fetchAll();

json_response(['success' => true, 'recap' => $recap]);
