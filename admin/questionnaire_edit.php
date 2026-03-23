<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_formateur_auth();

$id = (int)($_GET['id'] ?? 0);
$questionnaire = null;
$questions = [];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM questionnaires WHERE id = ? AND formateur_id = ?');
    $stmt->execute([$id, get_formateur_id()]);
    $questionnaire = $stmt->fetch();
    if (!$questionnaire) {
        redirect(BASE_URL . '/admin/index.php');
    }

    $stmt = $pdo->prepare('
        SELECT q.*, GROUP_CONCAT(
            CONCAT(rp.id, "||", rp.texte, "||", rp.est_correcte, "||", rp.ordre)
            ORDER BY rp.ordre SEPARATOR "@@"
        ) AS reponses_raw
        FROM questions q
        LEFT JOIN reponses_possibles rp ON rp.question_id = q.id
        WHERE q.questionnaire_id = ?
        GROUP BY q.id
        ORDER BY q.ordre
    ');
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll();

    foreach ($questions as &$q) {
        $q['reponses'] = [];
        if ($q['reponses_raw']) {
            foreach (explode('@@', $q['reponses_raw']) as $r) {
                $parts = explode('||', $r);
                $q['reponses'][] = [
                    'id'           => (int)$parts[0],
                    'texte'        => $parts[1],
                    'est_correcte' => (int)$parts[2],
                    'ordre'        => (int)$parts[3],
                ];
            }
        }
        unset($q['reponses_raw']);
    }
    unset($q);
}

$page_titre = $questionnaire ? 'Editer : ' . $questionnaire['titre'] : 'Nouveau questionnaire';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="bi bi-pencil-square me-2"></i>
        <?= $questionnaire ? 'Editer le questionnaire' : 'Nouveau questionnaire' ?>
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<!-- Formulaire questionnaire -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form id="form-questionnaire">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="titre" class="form-label">Titre</label>
                    <input type="text" class="form-control" id="titre" name="titre"
                           value="<?= h($questionnaire['titre'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description (optionnelle)</label>
                    <textarea class="form-control" id="description" name="description"
                              rows="2"><?= h($questionnaire['description'] ?? '') ?></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Section questions (visible seulement si questionnaire sauvegarde) -->
<div id="section-questions" class="<?= $id ? '' : 'd-none' ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-list-ol me-2"></i>Questions</h3>
        <div class="btn-group">
            <button class="btn btn-outline-primary btn-add-question" data-type="qcm">
                <i class="bi bi-plus me-1"></i>QCM
            </button>
            <button class="btn btn-outline-primary btn-add-question" data-type="vrai_faux">
                <i class="bi bi-plus me-1"></i>Vrai/Faux
            </button>
            <button class="btn btn-outline-primary btn-add-question" data-type="libre">
                <i class="bi bi-plus me-1"></i>Libre
            </button>
        </div>
    </div>

    <div id="questions-list">
        <?php foreach ($questions as $i => $q): ?>
            <?php include __DIR__ . '/_question_card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if (empty($questions)): ?>
        <div id="no-questions" class="text-center py-4 text-muted">
            <i class="bi bi-plus-circle display-4"></i>
            <p class="mt-2">Ajoutez votre premiere question ci-dessus</p>
        </div>
    <?php endif; ?>
</div>

<!-- Template question card (hidden) -->
<template id="tpl-question-card">
    <div class="card shadow-sm mb-3 question-card" data-question-id="0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="bi bi-grip-vertical me-1 handle"></i>
                Question <span class="q-numero"></span>
                <span class="badge bg-info ms-2 q-type-badge"></span>
            </span>
            <div class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 small">Duree (s) :</label>
                <input type="number" class="form-control form-control-sm q-duree" style="width:80px"
                       min="5" max="300" value="30">
                <button class="btn btn-sm btn-outline-danger btn-delete-question">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <textarea class="form-control q-texte" rows="2" placeholder="Texte de la question"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted"><i class="bi bi-image me-1"></i>Image (optionnelle)</label>
                <div class="q-image-preview mb-2 d-none">
                    <img src="" class="img-thumbnail" style="max-height:150px">
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-remove-image">
                        <i class="bi bi-x-lg me-1"></i>Supprimer l'image
                    </button>
                </div>
                <input type="file" class="form-control form-control-sm q-image" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="reponses-container"></div>
            <button class="btn btn-sm btn-outline-secondary btn-add-reponse mt-2 d-none">
                <i class="bi bi-plus me-1"></i>Ajouter un choix
            </button>
            <div class="mt-3 text-end">
                <button class="btn btn-sm btn-success btn-save-question">
                    <i class="bi bi-check-lg me-1"></i>Sauvegarder la question
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Template reponse possible -->
<template id="tpl-reponse">
    <div class="input-group mb-2 reponse-row">
        <div class="input-group-text">
            <input type="checkbox" class="form-check-input rp-correcte" title="Bonne reponse">
        </div>
        <input type="text" class="form-control rp-texte" placeholder="Texte du choix">
        <button class="btn btn-outline-danger btn-remove-reponse" type="button">
            <i class="bi bi-x"></i>
        </button>
    </div>
</template>

<script>
const questionnaireId = <?= $id ?>;
const baseUrl = '<?= BASE_URL ?>';

$(function() {
    // Sauvegarder questionnaire
    $('#form-questionnaire').on('submit', function(e) {
        e.preventDefault();
        const data = {
            id: $('[name=id]').val(),
            titre: $('#titre').val(),
            description: $('#description').val()
        };
        $.post('ajax/save_questionnaire.php', data, function(res) {
            if (res.success) {
                if (!data.id || data.id == '0') {
                    window.location.href = 'questionnaire_edit.php?id=' + res.id;
                } else {
                    showToast('Questionnaire sauvegarde');
                }
            } else {
                alert(res.error || 'Erreur');
            }
        }, 'json');
    });

    // Ajouter question
    $('.btn-add-question').on('click', function() {
        const type = $(this).data('type');
        addQuestionCard(0, type, '', null, 30, []);
        $('#no-questions').hide();
    });

    // Delegation : supprimer question
    $(document).on('click', '.btn-delete-question', function() {
        const card = $(this).closest('.question-card');
        const qId = card.data('question-id');
        if (qId > 0) {
            if (!confirm('Supprimer cette question ?')) return;
            $.post('ajax/delete_question.php', { id: qId }, function(res) {
                if (res.success) card.fadeOut(300, function() { $(this).remove(); renumber(); });
                else alert(res.error || 'Erreur');
            }, 'json');
        } else {
            card.fadeOut(300, function() { $(this).remove(); renumber(); });
        }
    });

    // Delegation : ajouter reponse
    $(document).on('click', '.btn-add-reponse', function() {
        const container = $(this).siblings('.reponses-container');
        container.append($('#tpl-reponse').html());
    });

    // Delegation : supprimer reponse
    $(document).on('click', '.btn-remove-reponse', function() {
        $(this).closest('.reponse-row').remove();
    });

    // Delegation : supprimer image
    $(document).on('click', '.btn-remove-image', function() {
        const card = $(this).closest('.question-card');
        card.find('.q-image-preview').addClass('d-none');
        card.find('.q-image-preview img').attr('src', '');
        card.find('.q-image').val('');
        card.data('supprimer-image', 1);
    });

    // Delegation : sauvegarder question (avec FormData pour l'upload)
    $(document).on('click', '.btn-save-question', function() {
        const card = $(this).closest('.question-card');

        const formData = new FormData();
        formData.append('id', card.data('question-id'));
        formData.append('questionnaire_id', questionnaireId);
        formData.append('texte', card.find('.q-texte').val());
        formData.append('type', card.data('type'));
        formData.append('duree_secondes', card.find('.q-duree').val());
        formData.append('ordre', card.index() + 1);

        // Image
        const imageInput = card.find('.q-image')[0];
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }
        if (card.data('supprimer-image')) {
            formData.append('supprimer_image', '1');
        }

        // Collecter les reponses possibles
        const reponses = [];
        card.find('.reponse-row').each(function() {
            reponses.push({
                texte: $(this).find('.rp-texte').val(),
                est_correcte: $(this).find('.rp-correcte').is(':checked') ? 1 : 0
            });
        });
        formData.append('reponses', JSON.stringify(reponses));

        $.ajax({
            url: 'ajax/save_question.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    card.data('question-id', res.id);
                    card.attr('data-question-id', res.id);
                    card.data('supprimer-image', 0);

                    // Mettre a jour l'apercu image
                    if (res.image) {
                        card.find('.q-image-preview').removeClass('d-none');
                        card.find('.q-image-preview img').attr('src', baseUrl + '/' + res.image);
                    } else {
                        card.find('.q-image-preview').addClass('d-none');
                        card.find('.q-image-preview img').attr('src', '');
                    }
                    card.find('.q-image').val('');

                    showToast('Question sauvegardee');
                } else {
                    alert(res.error || 'Erreur');
                }
            },
            error: function() {
                alert('Erreur de communication avec le serveur.');
            }
        });
    });

    // Charger les questions existantes depuis PHP
    <?php if (!empty($questions)): ?>
    // Questions already rendered server-side, just bind data attributes
    <?php foreach ($questions as $i => $q): ?>
    // handled by PHP include above
    <?php endforeach; ?>
    <?php endif; ?>
});

function addQuestionCard(id, type, texte, image, duree, reponses) {
    const tpl = $($('#tpl-question-card').html());
    tpl.data('question-id', id).attr('data-question-id', id);
    tpl.data('type', type).attr('data-type', type);
    tpl.find('.q-texte').val(texte);
    tpl.find('.q-duree').val(duree);

    const typeLabels = { qcm: 'QCM', vrai_faux: 'Vrai/Faux', libre: 'Libre' };
    tpl.find('.q-type-badge').text(typeLabels[type] || type);

    // Afficher image existante
    if (image) {
        tpl.find('.q-image-preview').removeClass('d-none');
        tpl.find('.q-image-preview img').attr('src', baseUrl + '/' + image);
    }

    if (type === 'qcm') {
        tpl.find('.btn-add-reponse').removeClass('d-none');
        if (reponses.length === 0) {
            // 4 choix par defaut
            for (let i = 0; i < 4; i++) {
                tpl.find('.reponses-container').append($('#tpl-reponse').html());
            }
        }
    } else if (type === 'vrai_faux') {
        // 2 choix fixes
        const container = tpl.find('.reponses-container');
        const r1 = $($('#tpl-reponse').html());
        r1.find('.rp-texte').val('Vrai');
        const r2 = $($('#tpl-reponse').html());
        r2.find('.rp-texte').val('Faux');
        container.append(r1).append(r2);
    }
    // libre : pas de reponses possibles

    // Remplir les reponses existantes
    if (reponses.length > 0) {
        tpl.find('.reponses-container').empty();
        reponses.forEach(function(r) {
            const row = $($('#tpl-reponse').html());
            row.find('.rp-texte').val(r.texte);
            if (r.est_correcte) row.find('.rp-correcte').prop('checked', true);
            tpl.find('.reponses-container').append(row);
        });
    }

    $('#questions-list').append(tpl);
    renumber();
}

function renumber() {
    $('#questions-list .question-card').each(function(i) {
        $(this).find('.q-numero').text(i + 1);
    });
}

function showToast(msg) {
    // Simple inline toast
    const toast = $('<div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3" style="z-index:9999">' +
        msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
    $('body').append(toast);
    setTimeout(function() { toast.alert('close'); }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
