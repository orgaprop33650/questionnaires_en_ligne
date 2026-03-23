<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_formateur_auth();

$questionnaire_id = (int)($_GET['questionnaire_id'] ?? 0);

// Récupérer les questionnaires du formateur
$stmt = $pdo->prepare('
    SELECT q.*, COUNT(qu.id) AS nb_questions
    FROM questionnaires q
    LEFT JOIN questions qu ON qu.questionnaire_id = q.id
    WHERE q.formateur_id = ?
    GROUP BY q.id
    ORDER BY q.date_creation DESC
');
$stmt->execute([get_formateur_id()]);
$questionnaires = $stmt->fetchAll();

$page_titre = 'Lancer une session';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<h1 class="mb-4"><i class="bi bi-play-circle me-2"></i>Lancer une session</h1>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($questionnaires)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Aucun questionnaire disponible.</p>
                        <a href="questionnaire_edit.php" class="btn btn-primary">Créer un quiz</a>
                    </div>
                <?php else: ?>
                    <form id="form-start-session">
                        <div class="mb-3">
                            <label for="questionnaire_id" class="form-label">Choisir un questionnaire</label>
                            <select class="form-select" id="questionnaire_id" name="questionnaire_id" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($questionnaires as $q): ?>
                                    <option value="<?= $q['id'] ?>"
                                            <?= $q['id'] == $questionnaire_id ? 'selected' : '' ?>>
                                        <?= h($q['titre']) ?> (<?= $q['nb_questions'] ?> questions)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100" id="btn-start">
                            <i class="bi bi-play-fill me-1"></i>Démarrer la session
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#form-start-session').on('submit', function(e) {
        e.preventDefault();
        const qId = $('#questionnaire_id').val();
        if (!qId) return;
        $('#btn-start').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Lancement...');
        $.post('ajax/start_session.php', { questionnaire_id: qId }, function(res) {
            if (res.success) {
                window.location.href = 'session_live.php?id=' + res.session_id;
            } else {
                alert(res.error || 'Erreur');
                $('#btn-start').prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i>Démarrer la session');
            }
        }, 'json');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
