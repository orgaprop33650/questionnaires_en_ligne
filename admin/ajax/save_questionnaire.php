<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$id          = (int)($_POST['id'] ?? 0);
$titre       = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($titre === '') {
    json_response(['success' => false, 'error' => 'Le titre est requis.'], 400);
}

if ($id > 0) {
    // Vérifier propriété
    $stmt = $pdo->prepare('SELECT id FROM questionnaires WHERE id = ? AND formateur_id = ?');
    $stmt->execute([$id, get_formateur_id()]);
    if (!$stmt->fetch()) {
        json_response(['success' => false, 'error' => 'Questionnaire introuvable.'], 404);
    }

    $stmt = $pdo->prepare('UPDATE questionnaires SET titre = ?, description = ? WHERE id = ?');
    $stmt->execute([$titre, $description, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO questionnaires (formateur_id, titre, description) VALUES (?, ?, ?)');
    $stmt->execute([get_formateur_id(), $titre, $description]);
    $id = (int)$pdo->lastInsertId();
}

json_response(['success' => true, 'id' => $id]);
