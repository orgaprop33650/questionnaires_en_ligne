<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$id               = (int)($_POST['id'] ?? 0);
$questionnaire_id = (int)($_POST['questionnaire_id'] ?? 0);
$texte            = trim($_POST['texte'] ?? '');
$type             = $_POST['type'] ?? 'qcm';
$duree            = (int)($_POST['duree_secondes'] ?? 30);
$ordre            = (int)($_POST['ordre'] ?? 0);
$reponses_json    = $_POST['reponses'] ?? '[]';
$supprimer_image  = (int)($_POST['supprimer_image'] ?? 0);

if ($texte === '') {
    json_response(['success' => false, 'error' => 'Le texte de la question est requis.'], 400);
}

if (!in_array($type, ['qcm', 'vrai_faux', 'libre'])) {
    json_response(['success' => false, 'error' => 'Type invalide.'], 400);
}

if ($duree < 5) $duree = 5;
if ($duree > 300) $duree = 300;

// Vérifier propriété du questionnaire
$stmt = $pdo->prepare('SELECT id FROM questionnaires WHERE id = ? AND formateur_id = ?');
$stmt->execute([$questionnaire_id, get_formateur_id()]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Questionnaire introuvable.'], 404);
}

// Récupérer l'image existante si modification
$ancienne_image = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT image FROM questions WHERE id = ? AND questionnaire_id = ?');
    $stmt->execute([$id, $questionnaire_id]);
    $ancienne_image = $stmt->fetchColumn() ?: null;
}

// Gestion de l'upload d'image
$image_path = $ancienne_image; // garder l'existante par défaut
$upload_dir = __DIR__ . '/../../uploads/questions/';

if ($supprimer_image && $ancienne_image) {
    $old_file = $upload_dir . basename($ancienne_image);
    if (file_exists($old_file)) {
        unlink($old_file);
    }
    $image_path = null;
}

if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Valider le type MIME
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($_FILES['image']['tmp_name']);

    if (!in_array($mime_type, $allowed_types)) {
        json_response(['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP.'], 400);
    }

    // Valider la taille (max 2 Mo)
    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        json_response(['success' => false, 'error' => 'L\'image ne doit pas dépasser 2 Mo.'], 400);
    }

    // Déterminer l'extension
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $ext = $extensions[$mime_type] ?? 'jpg';

    // Nom unique
    $filename = uniqid('q_', true) . '.' . $ext;
    $destination = $upload_dir . $filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        json_response(['success' => false, 'error' => 'Erreur lors de l\'upload de l\'image.'], 500);
    }

    // Supprimer l'ancienne image si remplacement
    if ($ancienne_image) {
        $old_file = $upload_dir . basename($ancienne_image);
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    $image_path = 'uploads/questions/' . $filename;
}

$pdo->beginTransaction();
try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE questions SET texte = ?, image = ?, type = ?, duree_secondes = ?, ordre = ? WHERE id = ? AND questionnaire_id = ?');
        $stmt->execute([$texte, $image_path, $type, $duree, $ordre, $id, $questionnaire_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO questions (questionnaire_id, texte, image, type, duree_secondes, ordre) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$questionnaire_id, $texte, $image_path, $type, $duree, $ordre]);
        $id = (int)$pdo->lastInsertId();
    }

    // Supprimer les anciennes réponses possibles et réinsérer
    $pdo->prepare('DELETE FROM reponses_possibles WHERE question_id = ?')->execute([$id]);

    $reponses = json_decode($reponses_json, true);
    if (is_array($reponses)) {
        $stmt = $pdo->prepare('INSERT INTO reponses_possibles (question_id, texte, est_correcte, ordre) VALUES (?, ?, ?, ?)');
        foreach ($reponses as $i => $r) {
            $rTexte = trim($r['texte'] ?? '');
            if ($rTexte === '') continue;
            $stmt->execute([$id, $rTexte, (int)($r['est_correcte'] ?? 0), $i + 1]);
        }
    }

    $pdo->commit();
    json_response(['success' => true, 'id' => $id, 'image' => $image_path]);
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['success' => false, 'error' => 'Erreur serveur.'], 500);
}
