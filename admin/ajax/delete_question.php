<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT q.id, q.image FROM questions q
    JOIN questionnaires qn ON qn.id = q.questionnaire_id
    WHERE q.id = ? AND qn.formateur_id = ?
');
$stmt->execute([$id, get_formateur_id()]);
$question = $stmt->fetch();
if (!$question) {
    json_response(['success' => false, 'error' => 'Question introuvable.'], 404);
}

// Supprimer le fichier image si existant
if ($question['image']) {
    $image_file = __DIR__ . '/../../' . $question['image'];
    if (file_exists($image_file)) {
        unlink($image_file);
    }
}

$pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
json_response(['success' => true]);
