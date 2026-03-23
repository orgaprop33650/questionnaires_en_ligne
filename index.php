<?php
require_once __DIR__ . '/config.php';

$page_titre = 'Accueil';
require_once __DIR__ . '/includes/header_public.php';
?>

<div class="row justify-content-center text-center py-5">
    <div class="col-md-8">
        <i class="bi bi-patch-question-fill display-1 text-primary"></i>
        <h1 class="mt-3">Questionnaires en ligne</h1>
        <p class="lead text-muted">Participez aux quiz en temps réel pendant vos formations !</p>

        <div class="row g-4 mt-4 justify-content-center">
            <!-- Stagiaire : rejoindre -->
            <div class="col-md-5">
                <div class="card shadow h-100 border-primary">
                    <div class="card-body">
                        <i class="bi bi-qr-code-scan display-4 text-primary"></i>
                        <h4 class="mt-3">Rejoindre un quiz</h4>
                        <p class="text-muted">Entrez le code donné par votre formateur</p>
                        <form action="session/join.php" method="get" class="mt-3">
                            <div class="input-group">
                                <input type="text" class="form-control text-center text-uppercase fw-bold"
                                       name="code" maxlength="6" placeholder="CODE"
                                       style="letter-spacing:.2em; font-size:1.2em">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Formateur : admin -->
            <div class="col-md-5">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="bi bi-person-workspace display-4 text-secondary"></i>
                        <h4 class="mt-3">Espace formateur</h4>
                        <p class="text-muted">Créez et gérez vos questionnaires</p>
                        <a href="admin/login.php" class="btn btn-outline-primary mt-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
