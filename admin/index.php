<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_formateur_auth();

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

$page_titre = 'Dashboard';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-speedometer2 me-2"></i>Mes questionnaires</h1>
    <a href="questionnaire_edit.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Nouveau quiz
    </a>
</div>

<?php if (empty($questionnaires)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="text-muted mt-3">Aucun questionnaire pour le moment.</p>
        <a href="questionnaire_edit.php" class="btn btn-outline-primary">Créer mon premier quiz</a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($questionnaires as $q): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm" id="quiz-<?= $q['id'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= h($q['titre']) ?></h5>
                        <p class="card-text text-muted small">
                            <?= $q['description'] ? h($q['description']) : '<em>Pas de description</em>' ?>
                        </p>
                        <span class="badge bg-secondary">
                            <i class="bi bi-list-check me-1"></i><?= $q['nb_questions'] ?> question(s)
                        </span>
                    </div>
                    <div class="card-footer bg-transparent d-flex gap-2">
                        <a href="questionnaire_edit.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Éditer
                        </a>
                        <a href="session_start.php?questionnaire_id=<?= $q['id'] ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-play-fill me-1"></i>Lancer
                        </a>
                        <button class="btn btn-sm btn-outline-danger ms-auto btn-delete-quiz"
                                data-id="<?= $q['id'] ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
$(function() {
    $('.btn-delete-quiz').on('click', function() {
        if (!confirm('Supprimer ce questionnaire et toutes ses questions ?')) return;
        const id = $(this).data('id');
        $.post('ajax/delete_questionnaire.php', { id: id }, function(res) {
            if (res.success) {
                $('#quiz-' + id).closest('.col-md-6').fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(res.error || 'Erreur');
            }
        }, 'json');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
