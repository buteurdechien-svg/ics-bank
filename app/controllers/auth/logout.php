<?php
/** Déconnexion. */
$u = current_user();
log_auth('logout', $u['login'] ?? null);

// [LAB · Défi 10 — session faible]
// On ne vide que la variable d'identité ; la session n'est pas réellement
// détruite ni invalidée côté serveur (pas de session_destroy complet).
unset($_SESSION['uid']);

flash('ok', "Vous êtes déconnecté.");
redirect('/login');
