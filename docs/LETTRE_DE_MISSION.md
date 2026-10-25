# Lettre de mission — Audit de sécurité ICS Bank

**Client (fictif)** : ICS Bank — Ivoire Cyber Shield
**Prestataire** : _(nom du participant / de l'équipe)_
**Date** : _______________

## 1. Contexte
ICS Bank, service de mobile money, souhaite faire évaluer la sécurité de sa
plateforme web et de son API avant mise en production.

## 2. Périmètre autorisé
- Application web : `http://<ip-vm>/`
- API REST : `http://<ip-vm>/api/v1/`
- Interface de capture d'e-mails (test) : `http://<ip-vm>:8025/`

**Hors périmètre** : toute machine autre que la VM du laboratoire ; l'hôte ;
Internet. Rester strictement dans le réseau isolé.

## 3. Type de test
- [ ] Boîte noire (aucune information fournie)
- [ ] Boîte grise (un compte client de test fourni)
- [ ] Boîte blanche (accès au code)

## 4. Règles d'engagement
- Tests autorisés : reconnaissance, exploitation des vulnérabilités sur les
  **données synthétiques uniquement**.
- Interdits : attaques par déni de service, destruction irréversible de
  données hors reset, sortie du périmètre.
- Toutes les preuves doivent être reproductibles et journalisées.

## 5. Livrable attendu
Un **rapport de pentest** (voir `MODELE_RAPPORT.md`) : vulnérabilités,
gravité, impact, preuves, recommandations, et retest.

## 6. Durée
Du ____________ au ____________.

_Signature client (fictif)_ ______  _Signature prestataire_ ______
