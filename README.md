# ICS Bank — Cyber Range

> ⚠️ **Application volontairement vulnérable**, à usage de formation au pentest web et API.
> Données 100 % fictives. À installer **uniquement dans une VM isolée**, jamais exposée sur Internet.

ICS Bank est une banque mobile fictive (type mobile money) conçue pour un
**parcours de pentest réaliste**. Le participant arrive sans connaître les
failles, crée son compte, explore les services, et documente les
vulnérabilités dans un **rapport d'audit** — comme sur une vraie mission.

Il n'y a **ni score, ni flags, ni liste de défis** affichés sur le site. Le
livrable est le rapport. La grille de correction est fournie séparément au
formateur (elle ne figure pas dans ce dépôt public).

---

## Installation sur une VM Ubuntu 22.04

### 1. Préparer la VM

- Installer **Ubuntu Server 22.04 LTS** dans VMware Fusion ou VirtualBox.
- **Réseau de la VM : NAT ou Host-only** (jamais « Bridged »/pont), pour que
  la banque vulnérable ne soit visible que de votre machine et de Kali.
- Prévoir ~2 Go de RAM et 10 Go de disque pour la VM.

### 2. Installer ICS Bank

Dans la VM :

```bash
git clone https://github.com/buteurdechien-svg/ics-bank.git
cd ics-bank
sudo ./scripts/install.sh
```

Le script installe Apache, PHP 8.3, MySQL, Mailpit, charge la base et crée
les comptes de démonstration.

### 3. Accéder au laboratoire

| Service | Adresse (dans la VM) |
|---|---|
| Site ICS Bank | http://localhost/ |
| Mailpit (codes OTP) | http://localhost:8025/ |
| API REST | http://localhost/api/v1/ |

Depuis **Kali** (même réseau host-only), remplacez `localhost` par l'IP de
la VM (`hostname -I` la donne).

### 4. Réinitialiser entre deux sessions

```bash
sudo ./scripts/reset-lab.sh
```

Efface tout (comptes créés, argent déplacé) et recharge l'état initial.

---

## Parcours du participant

1. Arriver sur l'accueil public, explorer les pages et formulaires.
2. Créer un compte (numéro fictif) ; récupérer l'OTP dans **Mailpit**.
3. Se connecter, découvrir son portefeuille et les opérations.
4. Tester les fonctions : bénéficiaires, transfert, paiement, historique,
   réclamation.
5. Observer les requêtes HTTP et cartographier l'API `/api/v1/`.
6. Chercher, démontrer et documenter les vulnérabilités.
7. Rédiger le rapport d'audit (gravité, impact, preuve, recommandation).

Un modèle de lettre de mission et de rapport se trouvent dans `docs/`.

---

## Technologies

HTML5, CSS3 natif, JavaScript vanilla · PHP 8.3 (sans framework), PDO ·
Apache 2 · MySQL 8 / InnoDB · Mailpit.

## Structure du dépôt

```
public/           Racine web (seul dossier servi par Apache)
  index.php       Routeur
  api/            (routes API gérées par app/api.php)
  assets/         CSS / JS / images
app/
  controllers/    Logique des pages (public, auth, client, pro)
  views/          Gabarits d'affichage
  lib/            Database (PDO), helpers, Mailer
  api.php         API REST /api/v1/
config/           Configuration
database/         schema.sql + seed.php (comptes de démonstration)
scripts/          install.sh, reset-lab.sh, services SSRF et Mailpit
storage/          Pièces jointes, journaux, exports (hors de public/)
docs/             Lettre de mission, modèle de rapport
```

## Avertissement

Ce projet contient des vulnérabilités **intentionnelles**. Ne jamais le
déployer sur une machine de production, ni l'exposer sur un réseau public.
Les auteurs déclinent toute responsabilité en cas d'usage hors d'un
laboratoire isolé.
