<?php
require_once __DIR__ . '/../config.php';

$code  = trim($_GET['code'] ?? '');
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code   = strtoupper(trim($_POST['code'] ?? ''));
    $pseudo = trim($_POST['pseudo'] ?? '');

    if ($code === '' || $pseudo === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        // Chercher la session
        $stmt = $pdo->prepare('SELECT id, statut FROM sessions WHERE code_acces = ? AND statut != "terminee"');
        $stmt->execute([$code]);
        $session = $stmt->fetch();

        if (!$session) {
            $erreur = 'Code invalide ou session terminée.';
        } else {
            // Vérifier pseudo unique dans la session
            $stmt = $pdo->prepare('SELECT id FROM participants WHERE session_id = ? AND pseudo = ?');
            $stmt->execute([$session['id'], $pseudo]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Reconnecter le participant existant
                $_SESSION['participant_id']   = $existing['id'];
                $_SESSION['session_id']       = $session['id'];
                $_SESSION['participant_pseudo'] = $pseudo;
            } else {
                // Créer le participant
                $stmt = $pdo->prepare('INSERT INTO participants (session_id, pseudo) VALUES (?, ?)');
                $stmt->execute([$session['id'], $pseudo]);
                $_SESSION['participant_id']   = (int)$pdo->lastInsertId();
                $_SESSION['session_id']       = $session['id'];
                $_SESSION['participant_pseudo'] = $pseudo;
            }

            redirect(BASE_URL . '/session/play.php');
        }
    }
}

$page_titre = 'Rejoindre une session';
require_once __DIR__ . '/../includes/header_public.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">
                    <i class="bi bi-qr-code-scan me-2 text-primary"></i>Rejoindre le quiz
                </h2>

                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?= h($erreur) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code de session</label>
                        <input type="text" class="form-control form-control-lg text-center text-uppercase fw-bold"
                               id="code" name="code" maxlength="6"
                               value="<?= h($code) ?>" required autofocus
                               placeholder="XXXXXX" style="letter-spacing:.3em; font-size:1.5em">
                    </div>
                    <div class="mb-3">
                        <label for="pseudo" class="form-label">Votre pseudo</label>
                        <input type="text" class="form-control" id="pseudo" name="pseudo"
                               maxlength="50" required placeholder="Ex: Jean D.">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Rejoindre
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
