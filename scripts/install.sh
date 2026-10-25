#!/usr/bin/env bash
#
# ICS Bank — installation sur Ubuntu Server 22.04 LTS (sans Docker)
# Installe Apache 2, PHP 8.3, MySQL 8 et Mailpit, puis déploie le laboratoire.
#
# Usage :  sudo ./scripts/install.sh
#
# ATTENTION : application VOLONTAIREMENT VULNÉRABLE. À n'installer que dans
# une VM isolée (réseau NAT ou Host-only), jamais exposée sur Internet.

set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "Ce script doit être lancé avec sudo." >&2
  exit 1
fi

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

# Identifiants de base (laboratoire)
DB_NAME="ics_bank"
DB_USER="ics_app"
DB_PASS="ics_app_lab"

echo "==> ICS Bank — installation dans : $PROJECT_DIR"

# ─── 1. Paquets système ───────────────────────────────────────────────
echo "==> Mise à jour des paquets..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y

echo "==> Installation d'Apache, MySQL et des outils..."
apt-get install -y software-properties-common curl unzip apache2 mysql-server

echo "==> Installation de PHP 8.3..."
if ! php -v 2>/dev/null | grep -q "PHP 8.3"; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
fi
apt-get install -y php8.3 libapache2-mod-php8.3 php8.3-mysql php8.3-mbstring php8.3-curl

# ─── 2. Mailpit (capture des e-mails / OTP) ───────────────────────────
if ! command -v mailpit >/dev/null 2>&1; then
  echo "==> Installation de Mailpit..."
  curl -fsSL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
fi

# ─── 3. Fichier .env ──────────────────────────────────────────────────
# (non consommé au runtime — Apache fournit les variables — mais utile en CLI)
if [[ ! -f .env ]]; then
  echo "==> Création du fichier .env..."
  if [[ -f .env.example ]]; then
    cp .env.example .env
  else
    cat > .env <<ENV
APP_NAME="ICS Bank"
APP_ENV=lab
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASS}
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM=no-reply@ics-bank.local
ENV
  fi
fi

# ─── 4. Base de données ───────────────────────────────────────────────
echo "==> Configuration de MySQL..."
systemctl enable --now mysql
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost'  IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1'  IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "==> Chargement du schéma..."
mysql "${DB_NAME}" < database/schema.sql

echo "==> Remplissage des données de démonstration..."
DB_HOST=127.0.0.1 DB_USER="${DB_USER}" DB_PASSWORD="${DB_PASS}" DB_NAME="${DB_NAME}" \
  php database/seed.php

# ─── 5. Stockage privé ────────────────────────────────────────────────
mkdir -p storage/logs storage/attachments storage/exports
chown -R www-data:www-data storage
chmod -R 0775 storage

# ─── 6. Apache ────────────────────────────────────────────────────────
echo "==> Configuration d'Apache..."
cat > /etc/apache2/sites-available/ics-bank.conf <<CONF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot ${PROJECT_DIR}/public

    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_USER ${DB_USER}
    SetEnv DB_PASSWORD ${DB_PASS}
    SetEnv DB_NAME ${DB_NAME}
    SetEnv MAIL_HOST 127.0.0.1
    SetEnv MAIL_PORT 1025

    <Directory ${PROJECT_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/ics-bank-error.log
    CustomLog \${APACHE_LOG_DIR}/ics-bank-access.log combined
</VirtualHost>
CONF

a2enmod rewrite headers php8.3 >/dev/null 2>&1 || true
a2dissite 000-default >/dev/null 2>&1 || true
a2ensite ics-bank >/dev/null 2>&1
systemctl restart apache2

# ─── 7. Service interne pour le défi SSRF (défi 13) ───────────────────
echo "==> Installation du service interne SSRF (démonstration)..."
bash scripts/ssrf-service.sh install || true

# ─── 8. Mailpit en service ────────────────────────────────────────────
echo "==> Démarrage de Mailpit..."
bash scripts/mailpit-service.sh install || true

IP=$(hostname -I | awk '{print $1}')
cat <<DONE

============================================================
  ICS Bank est installé.

  Site           : http://localhost/        (dans la VM)
  Mailpit (OTP)  : http://localhost:8025/
  API            : http://localhost/api/v1/

  Depuis Kali (même réseau host-only), remplacez localhost
  par l'IP de la VM : ${IP}

  Réinitialiser le laboratoire : sudo ./scripts/reset-lab.sh
============================================================
DONE
