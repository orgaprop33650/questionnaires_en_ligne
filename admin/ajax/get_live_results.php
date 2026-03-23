<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_formateur_auth();

$session_id = (int)($_GET['session_id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT s.*, q.titre AS quiz_titre
    FROM sessions s
    JOIN questionnaires q ON q.id = s.questionnaire_id
    WHERE s.id = ? AND q.formateur_id = ?
');
$stmt->execute([$session_id, get_formateur_id()]);
$session = $stmt->fetch();

if (!$session) {
    json_response(['success' => false, 'error' => 'Session introuvable.'], 404);
}

// Participants
$stmt = $pdo->prepare('SELECT id, pseudo FROM participants WHERE session_id = ? ORDER BY date_inscription');
$stmt->execute([$session_id]);
$participants = $stmt->fetchAll();

$result = [
    'success'          => true,
    'statut'           => $session['statut'],
    'participants'     => $participants,
    'question_active'  => null,
    'temps_restant'    => null,
    'nb_reponses'      => 0,
    'reponses_distribution' => [],
];

if ($session['question_active_id']) {
    // Infos question active
    $stmt = $pdo->prepare('SELECT id, texte, type, duree_secondes FROM questions WHERE id = ?');
    $stmt->execute([$session['question_active_id']]);
    $question = $stmt->fetch();
    $result['question_active'] = $question;

    // Temps restant (calcul serveur)
    $stmt = $pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, question_demarree_a, NOW()) AS elapsed FROM sessions WHERE id = ?');
    $stmt->execute([$session_id]);
    $elapsed = (int)$stmt->fetchColumn();
    $result['temps_restant'] = max(0, $question['duree_secondes'] - $elapsed);

    // Nombre de réponses
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM reponses r
        JOIN participants p ON p.id = r.participant_id
        WHERE r.question_id = ? AND p.session_id = ?
    ');
    $stmt->execute([$question['id'], $session_id]);
    $result['nb_reponses'] = (int)$stmt->fetchColumn();

    // Distribution des réponses (pour QCM / vrai_faux)
    if ($question['type'] !== 'libre') {
        $stmt = $pdo->prepare('
            SELECT rp.id, rp.texte, rp.est_correcte,
                   COUNT(r.id) AS count
            FROM reponses_possibles rp
            LEFT JOIN reponses r ON r.reponse_possible_id = rp.id
                AND r.participant_id IN (SELECT id FROM participants WHERE session_id = ?)
            WHERE rp.question_id = ?
            ORDER BY rp.ordre
        ');
        $stmt->execute([$session_id, $question['id']]);
        $result['reponses_distribution'] = $stmt->fetchAll();
    }
}

json_response($result);
