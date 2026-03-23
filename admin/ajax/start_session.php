<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$questionnaire_id = (int)($_POST['questionnaire_id'] ?? 0);

// Vérifier propriété et existence de questions
$stmt = $pdo->prepare('SELECT id FROM questionnaires WHERE id = ? AND formateur_id = ?');
$stmt->execute([$questionnaire_id, get_formateur_id()]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Questionnaire introuvable.'], 404);
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE questionnaire_id = ?');
$stmt->execute([$questionnaire_id]);
if ($stmt->fetchColumn() == 0) {
    json_response(['success' => false, 'error' => 'Ce questionnaire n\'a aucune question.'], 400);
}

$code = generer_code_acces($pdo);

$stmt = $pdo->prepare('INSERT INTO sessions (questionnaire_id, code_acces, statut) VALUES (?, ?, "attente")');
$stmt->execute([$questionnaire_id, $code]);
$session_id = (int)$pdo->lastInsertId();

json_response(['success' => true, 'session_id' => $session_id, 'code' => $code]);
