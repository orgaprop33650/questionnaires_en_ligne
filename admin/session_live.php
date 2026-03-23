<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_formateur_auth();

$session_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT s.*, q.titre AS quiz_titre, q.formateur_id
    FROM sessions s
    JOIN questionnaires q ON q.id = s.questionnaire_id
    WHERE s.id = ?
');
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session || $session['formateur_id'] != get_formateur_id()) {
    redirect(BASE_URL . '/admin/index.php');
}

// Récupérer les questions ordonnées
$stmt = $pdo->prepare('SELECT id, texte, image, type, duree_secondes, ordre FROM questions WHERE questionnaire_id = ? ORDER BY ordre, id');
$stmt->execute([$session['questionnaire_id']]);
$questions = $stmt->fetchAll();

$joinUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/session/join.php?code=' . $session['code_acces'];

$page_titre = 'Session live';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div id="session-container" data-session-id="<?= $session_id ?>">

    <!-- Phase 1 : Attente (QR code + participants) -->
    <div id="phase-attente">
        <div class="text-center mb-4">
            <h2><i class="bi bi-qr-code me-2"></i><?= h($session['quiz_titre']) ?></h2>
            <p class="lead">Scannez le QR code ou entrez le code pour rejoindre</p>
        </div>

        <div class="row justify-content-center g-4">
            <div class="col-md-4 text-center">
                <div class="card shadow p-4">
                    <div id="qrcode" class="mx-auto mb-3"></div>
                    <p class="mb-1"><a href="<?= h($joinUrl) ?>" target="_blank"><?= h($joinUrl) ?></a></p>
                    <h1 class="display-4 fw-bold text-primary"><?= h($session['code_acces']) ?></h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow h-100">
                    <div class="card-header">
                        <i class="bi bi-people-fill me-2"></i>Participants
                        <span class="badge bg-primary ms-2" id="nb-participants">0</span>
                    </div>
                    <div class="card-body" style="max-height:300px;overflow-y:auto">
                        <ul id="participants-list" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button class="btn btn-success btn-lg" id="btn-lancer-premiere">
                <i class="bi bi-play-fill me-1"></i>Lancer la première question
            </button>
        </div>
    </div>

    <!-- Phase 2 : Question active -->
    <div id="phase-question" class="d-none">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            Question <span id="q-current-num">1</span> / <?= count($questions) ?>
                            <span class="badge bg-info ms-2" id="q-type-badge"></span>
                        </span>
                        <div>
                            <span class="badge bg-warning text-dark fs-5" id="chrono-display">
                                <i class="bi bi-clock me-1"></i><span id="chrono-val">30</span>s
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h3 id="q-texte" class="mb-3"></h3>
                        <div id="q-image-container" class="text-center mb-3 d-none">
                            <img id="q-image" src="" class="img-fluid rounded" style="max-height:300px">
                        </div>

                        <!-- Barre chrono -->
                        <div class="progress mb-4" style="height: 8px;">
                            <div id="chrono-bar" class="progress-bar bg-warning" style="width:100%"></div>
                        </div>

                        <!-- Résultats live -->
                        <div id="resultats-live"></div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <div>
                            <span class="text-muted">
                                <i class="bi bi-chat-dots me-1"></i>Réponses : <strong id="nb-reponses">0</strong>
                                / <span id="nb-total-participants">0</span>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-info" id="btn-show-results">
                                <i class="bi bi-bar-chart me-1"></i>Afficher correction
                            </button>
                            <button class="btn btn-primary" id="btn-next">
                                <i class="bi bi-skip-forward me-1"></i>Question suivante
                            </button>
                            <button class="btn btn-danger" id="btn-stop-session">
                                <i class="bi bi-stop-fill me-1"></i>Terminer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header">
                        <i class="bi bi-people-fill me-2"></i>Participants
                        <span class="badge bg-primary ms-2" id="nb-participants-2">0</span>
                    </div>
                    <div class="card-body" style="max-height:400px;overflow-y:auto">
                        <ul id="participants-list-2" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase 3 : Session terminée -->
    <div id="phase-terminee" class="d-none">
        <div class="text-center py-5">
            <i class="bi bi-check-circle display-1 text-success"></i>
            <h2 class="mt-3">Session terminée !</h2>
            <p class="lead text-muted">Merci à tous les participants.</p>
            <div id="recap-final" class="mt-4"></div>
            <a href="index.php" class="btn btn-primary mt-4">
                <i class="bi bi-arrow-left me-1"></i>Retour au dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const sessionId = <?= $session_id ?>;
const baseUrl = '<?= BASE_URL ?>';
const questions = <?= json_encode(array_values($questions)) ?>;
let currentQuestionIndex = -1;
let pollInterval = null;
let chronoInterval = null;
let tempsRestant = 0;
let showingCorrection = false;

$(function() {
    // Générer QR code
    new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode($joinUrl) ?>,
        width: 200,
        height: 200
    });

    // Polling participants en attente
    pollParticipants();
    setInterval(pollParticipants, 2000);

    // Lancer la première question
    $('#btn-lancer-premiere').on('click', function() {
        lancerQuestion(0);
    });

    // Question suivante
    $('#btn-next').on('click', function() {
        if (currentQuestionIndex + 1 < questions.length) {
            lancerQuestion(currentQuestionIndex + 1);
        } else {
            stopSession();
        }
    });

    // Afficher correction
    $('#btn-show-results').on('click', function() {
        showingCorrection = true;
        $.post('ajax/show_results.php', { session_id: sessionId }, function() {
            // Les résultats vont s'afficher via le polling
        });
    });

    // Terminer session
    $('#btn-stop-session').on('click', function() {
        if (confirm('Terminer la session ?')) {
            stopSession();
        }
    });
});

function pollParticipants() {
    $.get('ajax/get_live_results.php', { session_id: sessionId }, function(res) {
        if (!res.success) return;

        // Mise à jour participants
        const list = res.participants || [];
        $('#nb-participants, #nb-participants-2').text(list.length);
        $('#nb-total-participants').text(list.length);

        let html = '';
        list.forEach(function(p) {
            html += '<li class="list-group-item py-1"><i class="bi bi-person me-1"></i>' +
                    escapeHtml(p.pseudo) + '</li>';
        });
        $('#participants-list, #participants-list-2').html(html);

        // Résultats question active
        if (res.question_active && res.reponses_distribution) {
            $('#nb-reponses').text(res.nb_reponses);
            renderResultats(res.reponses_distribution, res.nb_reponses);
        }

        // Mise à jour chrono depuis serveur
        if (res.temps_restant !== undefined && res.temps_restant !== null) {
            tempsRestant = Math.max(0, res.temps_restant);
            updateChronoDisplay();
        }
    }, 'json');
}

function lancerQuestion(index) {
    currentQuestionIndex = index;
    showingCorrection = false;
    const q = questions[index];

    $.post('ajax/next_question.php', {
        session_id: sessionId,
        question_id: q.id
    }, function(res) {
        if (!res.success) { alert(res.error || 'Erreur'); return; }

        // Basculer vers la phase question
        $('#phase-attente').addClass('d-none');
        $('#phase-question').removeClass('d-none');
        $('#phase-terminee').addClass('d-none');

        // Afficher la question
        const typeLabels = { qcm: 'QCM', vrai_faux: 'Vrai/Faux', libre: 'Libre' };
        $('#q-current-num').text(index + 1);
        $('#q-type-badge').text(typeLabels[q.type] || q.type);
        $('#q-texte').text(q.texte);
        if (q.image) {
            $('#q-image').attr('src', baseUrl + '/' + q.image);
            $('#q-image-container').removeClass('d-none');
        } else {
            $('#q-image-container').addClass('d-none');
        }
        $('#nb-reponses').text('0');
        $('#resultats-live').html('');

        // Chrono
        tempsRestant = q.duree_secondes;
        updateChronoDisplay();
        startChrono(q.duree_secondes);

        // Masquer/afficher bouton suivant
        if (index + 1 >= questions.length) {
            $('#btn-next').html('<i class="bi bi-stop-fill me-1"></i>Terminer la session');
        } else {
            $('#btn-next').html('<i class="bi bi-skip-forward me-1"></i>Question suivante');
        }
    }, 'json');
}

function startChrono(duree) {
    if (chronoInterval) clearInterval(chronoInterval);
    const startTime = Date.now();
    chronoInterval = setInterval(function() {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        tempsRestant = Math.max(0, duree - elapsed);
        updateChronoDisplay();
        if (tempsRestant <= 0) {
            clearInterval(chronoInterval);
        }
    }, 200);
}

function updateChronoDisplay() {
    $('#chrono-val').text(tempsRestant);
    const q = questions[currentQuestionIndex];
    if (q) {
        const pct = (tempsRestant / q.duree_secondes) * 100;
        $('#chrono-bar').css('width', pct + '%');
        if (pct < 25) {
            $('#chrono-bar').removeClass('bg-warning').addClass('bg-danger');
        } else {
            $('#chrono-bar').removeClass('bg-danger').addClass('bg-warning');
        }
    }
}

function renderResultats(distribution, nbReponses) {
    let html = '';
    distribution.forEach(function(item) {
        const pct = nbReponses > 0 ? Math.round((item.count / nbReponses) * 100) : 0;
        let barClass = 'bg-primary';
        if (showingCorrection) {
            barClass = item.est_correcte ? 'bg-success' : 'bg-danger';
        }
        html += '<div class="mb-2">';
        html += '<div class="d-flex justify-content-between mb-1">';
        html += '<span>' + escapeHtml(item.texte) + '</span>';
        html += '<span class="fw-bold">' + item.count + ' (' + pct + '%)</span>';
        html += '</div>';
        html += '<div class="progress" style="height:24px">';
        html += '<div class="progress-bar ' + barClass + '" style="width:' + pct + '%">' + pct + '%</div>';
        html += '</div></div>';
    });

    // Réponses libres
    if (distribution.length === 0 && nbReponses > 0) {
        html = '<p class="text-muted">Question libre — ' + nbReponses + ' réponse(s) reçue(s)</p>';
    }

    $('#resultats-live').html(html);
}

function stopSession() {
    $.post('ajax/stop_session.php', { session_id: sessionId }, function(res) {
        if (chronoInterval) clearInterval(chronoInterval);
        $('#phase-attente, #phase-question').addClass('d-none');
        $('#phase-terminee').removeClass('d-none');

        if (res.recap) {
            let recapHtml = '<div class="table-responsive"><table class="table">';
            recapHtml += '<thead><tr><th>Question</th><th>Réponses</th></tr></thead><tbody>';
            res.recap.forEach(function(r) {
                recapHtml += '<tr><td>' + escapeHtml(r.texte) + '</td><td>' + r.nb_reponses + '</td></tr>';
            });
            recapHtml += '</tbody></table></div>';
            $('#recap-final').html(recapHtml);
        }
    }, 'json');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
