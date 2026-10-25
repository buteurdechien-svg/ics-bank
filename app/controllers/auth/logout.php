<?php
/** Déconnexion. */
$u = current_user();
log_auth('logout', $u['login'] ?? null);

unset($_SESSION['uid']);

flash('ok', "Vous êtes déconnecté.");
redirect('/login');
