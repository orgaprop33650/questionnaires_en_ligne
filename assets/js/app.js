/**
 * Questionnaires en ligne — Shared JS
 */

// AJAX error handler global
$(document).ajaxError(function(event, jqXHR, settings) {
    if (jqXHR.status === 401) {
        window.location.href = window.location.pathname.includes('/admin/')
            ? '/questionnaires_en_ligne/admin/login.php'
            : '/questionnaires_en_ligne/session/join.php';
    }
});

// Auto-uppercase pour les champs code
$(function() {
    $('input[name="code"]').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
});
