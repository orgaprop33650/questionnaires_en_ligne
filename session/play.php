<?php
require_once __DIR__ . '/../config.php';

$participant_id = $_SESSION['participant_id'] ?? 0;
$session_id     = $_SESSION['session_id'] ?? 0;
$pseudo         = $_SESSION['participant_pseudo'] ?? '';

if (!$participant_id || !$session_id) {
    redirect(BASE_URL . '/session/join.php');
}

// Vérifier que la session existe
$stmt = $pdo->prepare('SELECT s.*, q.titre FROM sessions s JOIN questionnaires q ON q.id = s.questionnaire_id WHERE s.id = ?');
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    redirect(BASE_URL . '/session/join.php');
}

$page_titre = $session['titre'];
require_once __DIR__ . '/../includes/header_public.php';
?>

<div id="play-container" data-session-id="<?= $session_id ?>" data-participant-id="<?= $participant_id ?>">

    <!-- Attente -->
    <div id="ecran-attente" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h3>Bienvenue, <strong><?= h($pseudo) ?></strong> !</h3>
        <p class="lead text-muted">En attente de la prochaine question...</p>
        <p class="text-muted"><small>Quiz : <?= h($session['titre']) ?></small></p>
    </div>

    <!-- Question active -->
    <div id="ecran-question" class="d-none">
        <div class="card shadow mx-auto" style="max-width:600px">
            <div class="card-header d-flex justify-content-between">
                <span class="fw-bold" id="play-q-num">Question 1</span>
                <span class="badge bg-warning text-dark fs-6" id="play-chrono">
                    <i class="bi bi-clock me-1"></i><span id="play-chrono-val">30</span>s
                </span>
            </div>
            <div class="card-body">
                <!-- Barre chrono -->
                <div class="progress mb-3" style="height: 6px;">
                    <div id="play-chrono-bar" class="progress-bar bg-warning" style="width:100%"></div>
                </div>

                <h4 id="play-q-texte" class="mb-3"></h4>
                <div id="play-q-image-container" class="text-center mb-3 d-none">
                    <img id="play-q-image" src="" class="img-fluid rounded" style="max-height:200px">
                </div>

                <!-- Choix QCM/vrai_faux -->
                <div id="play-choix"></div>

                <!-- Texte libre -->
                <div id="play-libre" class="d-none">
                    <textarea class="form-control" id="play-texte-libre" rows="3"
                              placeholder="Votre réponse..."></textarea>
                </div>

                <button class="btn btn-success btn-lg w-100 mt-3" id="btn-submit-answer">
                    <i class="bi bi-send me-1"></i>Valider ma réponse
                </button>
            </div>
        </div>
    </div>

    <!-- Réponse envoyée -->
    <div id="ecran-repondu" class="d-none text-center py-5">
        <i class="bi bi-check-circle display-1 text-success"></i>
        <h3 class="mt-3">Réponse enregistrée !</h3>
        <p class="text-muted">En attente de la prochaine question...</p>
    </div>

    <!-- Session terminée -->
    <div id="ecran-termine" class="d-none text-center py-5">
        <i class="bi bi-trophy display-1 text-warning"></i>
        <h2 class="mt-3">Merci pour votre participation !</h2>
        <p class="lead text-muted">La session est terminée.</p>
        <a href="<?= BASE_URL ?>/" class="btn btn-primary mt-3">Retour à l'accueil</a>
    </div>
</div>

<script>
const sessionId = <?= $session_id ?>;
const participantId = <?= $participant_id ?>;
const baseUrl = '<?= BASE_URL ?>';
let currentQuestionId = null;
let hasAnswered = false;
let chronoInterval = null;
let tempsRestant = 0;
let dureeTotale = 0;
let selectedReponseId = null;

$(function() {
    // Polling question active
    pollQuestion();
    setInterval(pollQuestion, 2000);

    // Sélection d'un choix
    $(document).on('click', '.choix-btn', function() {
        $('.choix-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        selectedReponseId = $(this).data('id');
    });

    // Soumettre la réponse
    $('#btn-submit-answer').on('click', function() {
        if (hasAnswered) return;

        const data = {
            question_id: currentQuestionId,
            reponse_possible_id: selectedReponseId || '',
            texte_libre: $('#play-texte-libre').val() || ''
        };

        if (!selectedReponseId && !data.texte_libre) {
            alert('Veuillez sélectionner ou saisir une réponse.');
            return;
        }

        $(this).prop('disabled', true);

        $.post('ajax/submit_answer.php', data, function(res) {
            if (res.success) {
                hasAnswered = true;
                $('#ecran-question').addClass('d-none');
                $('#ecran-repondu').removeClass('d-none');
            } else {
                alert(res.error || 'Erreur');
                $('#btn-submit-answer').prop('disabled', false);
            }
        }, 'json');
    });
});

function pollQuestion() {
    $.get('ajax/get_question.php', { session_id: sessionId }, function(res) {
        if (!res.success) return;

        if (res.statut === 'terminee') {
            showScreen('termine');
            return;
        }

        if (!res.question) {
            // Pas de question active → attente
            if (!hasAnswered) {
                showScreen('attente');
            }
            return;
        }

        // Nouvelle question ?
        if (res.question.id !== currentQuestionId) {
            currentQuestionId = res.question.id;
            hasAnswered = false;
            selectedReponseId = null;
            dureeTotale = res.question.duree_secondes;

            // Vérifier si déjà répondu
            if (res.deja_repondu) {
                hasAnswered = true;
                showScreen('repondu');
                return;
            }

            showScreen('question');
            renderQuestion(res.question, res.reponses_possibles);
        }

        // Mise à jour chrono
        if (res.temps_restant !== undefined) {
            tempsRestant = Math.max(0, res.temps_restant);
            updatePlayChrono();

            if (tempsRestant <= 0 && !hasAnswered) {
                // Temps écoulé sans réponse
                hasAnswered = true;
                showScreen('repondu');
                $('#ecran-repondu h3').text('Temps écoulé !');
                $('#ecran-repondu i').removeClass('text-success').addClass('text-danger')
                    .removeClass('bi-check-circle').addClass('bi-clock-history');
            }
        }
    }, 'json');
}

function showScreen(name) {
    $('#ecran-attente, #ecran-question, #ecran-repondu, #ecran-termine').addClass('d-none');
    $('#ecran-' + name).removeClass('d-none');
}

function renderQuestion(question, reponses) {
    $('#play-q-texte').text(question.texte);
    $('#play-q-num').text('Question');

    // Image
    if (question.image) {
        $('#play-q-image').attr('src', baseUrl + '/' + question.image);
        $('#play-q-image-container').removeClass('d-none');
    } else {
        $('#play-q-image-container').addClass('d-none');
    }

    // Réinitialiser
    $('#play-choix').empty().removeClass('d-none');
    $('#play-libre').addClass('d-none');
    $('#btn-submit-answer').prop('disabled', false);

    if (question.type === 'libre') {
        $('#play-choix').addClass('d-none');
        $('#play-libre').removeClass('d-none');
        $('#play-texte-libre').val('');
    } else {
        const colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
        reponses.forEach(function(r, i) {
            const colorClass = colors[i % colors.length];
            $('#play-choix').append(
                '<button class="btn btn-outline-' + colorClass + ' btn-lg w-100 mb-2 choix-btn text-start" data-id="' + r.id + '">' +
                '<i class="bi bi-circle me-2"></i>' + escapeHtml(r.texte) +
                '</button>'
            );
        });
    }

    // Chrono
    tempsRestant = question.duree_secondes;
    dureeTotale = question.duree_secondes;
    startPlayChrono();
}

function startPlayChrono() {
    if (chronoInterval) clearInterval(chronoInterval);
    const startTime = Date.now();
    const initialRemaining = tempsRestant;
    chronoInterval = setInterval(function() {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        tempsRestant = Math.max(0, initialRemaining - elapsed);
        updatePlayChrono();
        if (tempsRestant <= 0) clearInterval(chronoInterval);
    }, 200);
}

function updatePlayChrono() {
    $('#play-chrono-val').text(tempsRestant);
    const pct = dureeTotale > 0 ? (tempsRestant / dureeTotale) * 100 : 0;
    $('#play-chrono-bar').css('width', pct + '%');
    if (pct < 25) {
        $('#play-chrono-bar').removeClass('bg-warning').addClass('bg-danger');
    } else {
        $('#play-chrono-bar').removeClass('bg-danger').addClass('bg-warning');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
