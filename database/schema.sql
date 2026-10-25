-- ICS Bank — schéma de la base (MySQL 8 / InnoDB)
-- Banque fictive de formation. Données exclusivement synthétiques.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS challenge_attempts;
DROP TABLE IF EXISTS lab_challenges;
DROP TABLE IF EXISTS security_events;
DROP TABLE IF EXISTS auth_events;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS merchant_payments;
DROP TABLE IF EXISTS merchants;
DROP TABLE IF EXISTS otp_challenges;
DROP TABLE IF EXISTS beneficiaries;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ───────────────────────── Utilisateurs ─────────────────────────
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  login         VARCHAR(120) NOT NULL UNIQUE,     -- identifiant de connexion (e-mail fictif)
  phone         VARCHAR(20)  NOT NULL UNIQUE,      -- numéro fictif +225...
  secret_hash   VARCHAR(255) NOT NULL,             -- mot de passe / code secret haché
  role          ENUM('client','agent','merchant','support','admin') NOT NULL DEFAULT 'client',
  org           VARCHAR(120) NULL,                 -- organisation fictive
  title         VARCHAR(120) NULL,                 -- fonction fictive
  status        ENUM('active','blocked') NOT NULL DEFAULT 'active',
  is_seeded     TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = compte de démonstration (recréé au reset)
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Portefeuilles ────────────────────────
CREATE TABLE wallets (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  account_no    VARCHAR(24) NOT NULL UNIQUE,       -- numéro de compte affiché
  currency      CHAR(4) NOT NULL DEFAULT 'FCFA',
  balance       BIGINT NOT NULL DEFAULT 0,         -- montant entier en FCFA (jamais de flottant)
  status        ENUM('active','frozen') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wallet_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Transactions ─────────────────────────
CREATE TABLE transactions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  reference     VARCHAR(32) NOT NULL UNIQUE,
  src_wallet    INT NULL,                          -- NULL = dépôt externe (cash-in agent)
  dst_wallet    INT NULL,                          -- NULL = retrait (cash-out)
  amount        BIGINT NOT NULL,
  fee           BIGINT NOT NULL DEFAULT 0,
  type          ENUM('transfer','payment','deposit','withdrawal') NOT NULL DEFAULT 'transfer',
  status        ENUM('pending','done','failed','reversed') NOT NULL DEFAULT 'done',
  label         VARCHAR(140) NULL,                 -- motif saisi par l'utilisateur
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_src FOREIGN KEY (src_wallet) REFERENCES wallets(id) ON DELETE SET NULL,
  CONSTRAINT fk_tx_dst FOREIGN KEY (dst_wallet) REFERENCES wallets(id) ON DELETE SET NULL,
  INDEX idx_tx_src (src_wallet),
  INDEX idx_tx_dst (dst_wallet),
  INDEX idx_tx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Bénéficiaires ────────────────────────
CREATE TABLE beneficiaries (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  owner_id      INT NOT NULL,
  dst_account   VARCHAR(24) NOT NULL,
  nickname      VARCHAR(80) NULL,
  validated     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ben_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ben_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── OTP (codes de test) ──────────────────
CREATE TABLE otp_challenges (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NULL,
  phone         VARCHAR(20) NOT NULL,
  code          VARCHAR(8) NOT NULL,               -- code de test (visible dans Mailpit)
  purpose       ENUM('signup','login','reset') NOT NULL DEFAULT 'signup',
  attempts      INT NOT NULL DEFAULT 0,
  consumed      TINYINT(1) NOT NULL DEFAULT 0,
  expires_at    TIMESTAMP NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Marchands ────────────────────────────
CREATE TABLE merchants (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NULL,                          -- compte marchand associé
  code          VARCHAR(16) NOT NULL UNIQUE,       -- code marchand (saisi au paiement)
  name          VARCHAR(120) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE merchant_payments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id   INT NOT NULL,
  payer_wallet  INT NULL,
  amount        BIGINT NOT NULL,
  reference     VARCHAR(32) NOT NULL UNIQUE,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mp_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
  CONSTRAINT fk_mp_wallet   FOREIGN KEY (payer_wallet) REFERENCES wallets(id) ON DELETE SET NULL,
  INDEX idx_mp_merchant (merchant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Réclamations ─────────────────────────
CREATE TABLE complaints (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  owner_id      INT NOT NULL,
  subject       VARCHAR(160) NOT NULL,
  body          TEXT NOT NULL,
  status        ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cmp_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_cmp_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attachments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  complaint_id  INT NOT NULL,
  stored_name   VARCHAR(80) NOT NULL,              -- nom interne aléatoire (hors public/)
  orig_name     VARCHAR(160) NOT NULL,
  mime          VARCHAR(100) NOT NULL,
  size_bytes    INT NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_cmp FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── Journaux ─────────────────────────────
CREATE TABLE auth_events (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  login         VARCHAR(120) NULL,
  event         VARCHAR(40) NOT NULL,              -- login_ok, login_fail, logout, otp_sent...
  ip            VARCHAR(45) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auth_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE security_events (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  actor         VARCHAR(120) NULL,
  action        VARCHAR(80) NOT NULL,
  target        VARCHAR(120) NULL,
  result        VARCHAR(40) NULL,
  correlation   VARCHAR(40) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────── Suivi pédagogique (réservé formateur) ─────────
CREATE TABLE lab_challenges (
  id            INT PRIMARY KEY,
  title         VARCHAR(160) NOT NULL,
  severity      ENUM('Low','Medium','High','Critical') NOT NULL,
  category      VARCHAR(80) NOT NULL,
  location      VARCHAR(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
