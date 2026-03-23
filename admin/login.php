<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

if (get_formateur_id()) {
    redirect(BASE_URL . '/admin/index.php');
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $mdp === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nom, mot_de_passe FROM formateurs WHERE email = ?');
        $stmt->execute([$email]);
        $formateur = $stmt->fetch();

        if ($formateur && password_verify($mdp, $formateur['mot_de_passe'])) {
            $_SESSION['formateur_id']  = $formateur['id'];
            $_SESSION['formateur_nom'] = $formateur['nom'];
            redirect(BASE_URL . '/admin/index.php');
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}

$page_titre = 'Connexion';
require_once __DIR__ . '/../includes/header_public.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">
                    <i class="bi bi-person-lock me-2"></i>Connexion formateur
                </h2>

                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?= h($erreur) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= h($email ?? '') ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <small>Compte test : admin@test.com / admin123</small>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
