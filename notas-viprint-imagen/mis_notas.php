<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!is_disenador()) {
    redirect('notas.php');
}
$_GET['disenador'] = current_user()['id'];
require __DIR__ . '/notas.php';
