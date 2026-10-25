#!/usr/bin/env bash
# Service interne de démonstration pour le défi SSRF (défi 13).
# Sert un petit contenu "interne" sur 127.0.0.1:9000 uniquement.
set -euo pipefail
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

case "${1:-install}" in
  install)
    cat > /etc/systemd/system/ics-ssrf.service <<SERVICE
[Unit]
Description=ICS Bank - service interne SSRF (demo)
After=network.target

[Service]
# Lié à 127.0.0.1 : accessible uniquement depuis la VM, jamais du réseau.
ExecStart=/usr/bin/php -S 127.0.0.1:9000 -t ${PROJECT_DIR}/scripts/ssrf-internal
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
SERVICE
    systemctl daemon-reload
    systemctl enable --now ics-ssrf
    echo "Service SSRF interne démarré sur 127.0.0.1:9000"
    ;;
  stop)    systemctl stop ics-ssrf || true ;;
  *)       echo "usage: $0 [install|stop]" ;;
esac
