#!/usr/bin/env bash
#
# ICS Bank — remise à zéro du laboratoire.
# Efface toutes les données (comptes créés, argent déplacé) et recharge
# les comptes de démonstration dans leur état initial.
#
# Usage :  sudo ./scripts/reset-lab.sh

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

DB_NAME="${DB_NAME:-ics_bank}"
DB_USER="${DB_USER:-ics_app}"
DB_PASS="${DB_PASSWORD:-ics_app_lab}"

echo "==> Rechargement du schéma..."
mysql "${DB_NAME}" < database/schema.sql

echo "==> Remplissage des données de démonstration..."
DB_HOST=127.0.0.1 DB_USER="${DB_USER}" DB_PASSWORD="${DB_PASS}" DB_NAME="${DB_NAME}" \
  php database/seed.php

echo "==> Nettoyage des pièces jointes et journaux..."
rm -f storage/attachments/* storage/logs/* 2>/dev/null || true

echo "==> Laboratoire réinitialisé."
