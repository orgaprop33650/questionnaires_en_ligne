<?php
// Included from questionnaire_edit.php — variables: $q (question), $i (index)
$typeLabels = ['qcm' => 'QCM', 'vrai_faux' => 'Vrai/Faux', 'libre' => 'Libre'];
?>
<div class="card shadow-sm mb-3 question-card" data-question-id="<?= $q['id'] ?>" data-type="<?= h($q['type']) ?>">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-grip-vertical me-1 handle"></i>
            Question <span class="q-numero"><?= $i + 1 ?></span>
            <span class="badge bg-info ms-2 q-type-badge"><?= $typeLabels[$q['type']] ?? $q['type'] ?></span>
        </span>
        <div class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0 small">Duree (s) :</label>
            <input type="number" class="form-control form-control-sm q-duree" style="width:80px"
                   min="5" max="300" value="<?= (int)$q['duree_secondes'] ?>">
            <button class="btn btn-sm btn-outline-danger btn-delete-question">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <textarea class="form-control q-texte" rows="2"
                      placeholder="Texte de la question"><?= h($q['texte']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label small text-muted"><i class="bi bi-image me-1"></i>Image (optionnelle)</label>
            <?php if (!empty($q['image'])): ?>
                <div class="q-image-preview mb-2">
                    <img src="<?= BASE_URL . '/' . h($q['image']) ?>" class="img-thumbnail" style="max-height:150px">
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-remove-image">
                        <i class="bi bi-x-lg me-1"></i>Supprimer l'image
                    </button>
                </div>
            <?php endif; ?>
            <input type="file" class="form-control form-control-sm q-image" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <div class="reponses-container">
            <?php foreach ($q['reponses'] as $rp): ?>
                <div class="input-group mb-2 reponse-row">
                    <div class="input-group-text">
                        <input type="checkbox" class="form-check-input rp-correcte"
                               title="Bonne reponse" <?= $rp['est_correcte'] ? 'checked' : '' ?>>
                    </div>
                    <input type="text" class="form-control rp-texte"
                           placeholder="Texte du choix" value="<?= h($rp['texte']) ?>">
                    <button class="btn btn-outline-danger btn-remove-reponse" type="button">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($q['type'] === 'qcm'): ?>
            <button class="btn btn-sm btn-outline-secondary btn-add-reponse mt-2">
                <i class="bi bi-plus me-1"></i>Ajouter un choix
            </button>
        <?php endif; ?>
        <div class="mt-3 text-end">
            <button class="btn btn-sm btn-success btn-save-question">
                <i class="bi bi-check-lg me-1"></i>Sauvegarder la question
            </button>
        </div>
    </div>
</div>
