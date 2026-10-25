#!/usr/bin/env bash
# Démarre Mailpit en service systemd (SMTP 1025, interface web 8025).
set -euo pipefail

case "${1:-install}" in
  install)
    MAILPIT_BIN="$(command -v mailpit || echo /usr/local/bin/mailpit)"
    cat > /etc/systemd/system/mailpit.service <<SERVICE
[Unit]
Description=Mailpit (capture e-mails de test ICS Bank)
After=network.target

[Service]
# Interface web liée à localhost ; SMTP écoute en 1025 pour l'application.
ExecStart=${MAILPIT_BIN} --smtp 127.0.0.1:1025 --listen 127.0.0.1:8025
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
SERVICE
    systemctl daemon-reload
    systemctl enable --now mailpit
    echo "Mailpit démarré (SMTP 127.0.0.1:1025, web http://localhost:8025)"
    ;;
  stop)    systemctl stop mailpit || true ;;
  *)       echo "usage: $0 [install|stop]" ;;
esac
