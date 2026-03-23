<?php
require_once __DIR__ . '/../config.php';

$_SESSION = [];
session_destroy();

redirect(BASE_URL . '/admin/login.php');
