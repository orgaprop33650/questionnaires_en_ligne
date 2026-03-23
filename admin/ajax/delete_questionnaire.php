<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM questionnaires WHERE id = ? AND formateur_id = ?');
$stmt->execute([$id, get_formateur_id()]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Questionnaire introuvable.'], 404);
}

$pdo->prepare('DELETE FROM questionnaires WHERE id = ?')->execute([$id]);
json_response(['success' => true]);
