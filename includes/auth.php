<?php

/**
 * Vérifie que le formateur est connecté, sinon redirige vers login
 */
function require_formateur_auth(): void {
    if (empty($_SESSION['formateur_id'])) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

/**
 * Retourne l'ID du formateur connecté ou null
 */
function get_formateur_id(): ?int {
    return $_SESSION['formateur_id'] ?? null;
}

/**
 * Retourne le nom du formateur connecté
 */
function get_formateur_nom(): string {
    return $_SESSION['formateur_nom'] ?? '';
}
