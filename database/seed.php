<?php
/**
 * ICS Bank — remplissage des données de démonstration.
 * Exécuté par install.sh et reset-lab.sh.
 * Tout est fictif. Les mots de passe sont hachés ici (jamais stockés en clair).
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/lib/Database.php';
$pdo = Database::pdo();

echo "Nettoyage des données existantes...\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['lab_challenges','security_events','auth_events',
          'attachments','complaints','merchant_payments','merchants',
          'otp_challenges','beneficiaries','transactions','wallets','users'] as $t) {
    $pdo->exec("TRUNCATE TABLE $t");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$hash = fn(string $s): string => password_hash($s, PASSWORD_BCRYPT);

/**
 * Comptes. Les identifiants et mots de passe ci-dessous ne figurent
 * QUE dans ce seeder et dans la grille du formateur — jamais sur le site.
 */
$users = [
    // [full_name, login, phone, secret, role, org, title, balance]
    ['Administrateur ICS',      'admin@ics-bank.local',          '+2250700000000', 'volkoff007@',   'admin',    'ICS Bank',                 'Administrateur',            0],
    ['Kouadio Ahizé Ebagnini',  'ebagniniahize@ics-bank.local',  '+2250700000001', 'Lagune#2026',   'client',   'Lagune Transport Holding', 'PDG',                       160919990000000],
    ['Fran Sowo Massamba',      'massambafran@ics-bank.local',   '+2250700000002', 'Baobab!Fin88',  'client',   'Groupe Baobab Finance',    'Directeur général',         120919990000000],
    ['Victoria Hilaire Malice', 'malicevictoria@ics-bank.local', '+2250700000003', 'Ebene*Mines7',  'client',   'Société Ébène Mines',      'Directrice adjointe',       100919990000000],
    ['Yohan Frédéric Tanoh',    'tanohyohan@ics-bank.local',     '+2250700000004', 'Cacao2026',     'client',   'Cacao Royal Industries',   'Directeur communication',   110919990000000],
    ['Nelly Adjoua Kassi',      'kassinelly@ics-bank.local',     '+2250700000005', 'nelly1990',     'client',   'Cacao Royal Industries',   'DGA Communication',         110919990000000],
    // Comptes professionnels (identifiants connus du seul formateur)
    ['Agent Guichet ICS',       'agent@ics-bank.local',          '+2250700000010', 'AgentICS#2026', 'agent',    'ICS Bank',                 'Agent',                     50000000],
    ['Support ICS',             'support@ics-bank.local',        '+2250700000011', 'SupportICS#26', 'support',  'ICS Bank',                 'Support',                   0],
    ['Boutique Adjamé',         'marchand@ics-bank.local',       '+2250700000012', 'Shop!Adjame7',  'merchant', 'Boutique Adjamé',          'Marchand',                  25000000],
];

echo "Insertion des utilisateurs et portefeuilles...\n";
$uStmt = $pdo->prepare(
    'INSERT INTO users (full_name, login, phone, secret_hash, role, org, title, is_seeded)
     VALUES (?,?,?,?,?,?,?,1)'
);
$wStmt = $pdo->prepare(
    'INSERT INTO wallets (user_id, account_no, balance) VALUES (?,?,?)'
);

$ids = [];       // login => user_id
$acc = [];       // login => account_no
$n = 0;
foreach ($users as $u) {
    [$name,$login,$phone,$secret,$role,$org,$title,$balance] = $u;
    $uStmt->execute([$name,$login,$phone,$hash($secret),$role,$org,$title]);
    $uid = (int)$pdo->lastInsertId();
    $ids[$login] = $uid;
    $accountNo = 'ICS' . str_pad((string)(1000 + ++$n), 7, '0', STR_PAD_LEFT);
    $acc[$login] = $accountNo;
    $wStmt->execute([$uid, $accountNo, $balance]);
}

echo "Création des marchands...\n";
$mStmt = $pdo->prepare('INSERT INTO merchants (user_id, code, name) VALUES (?,?,?)');
$mStmt->execute([$ids['marchand@ics-bank.local'], 'MARCH01', 'Boutique Adjamé']);
$mStmt->execute([null, 'MARCH02', 'Pharmacie du Plateau']);
$mStmt->execute([null, 'MARCH03', 'Station Treichville']);

echo "Génération d'un historique de transactions fictif...\n";
$txStmt = $pdo->prepare(
    'INSERT INTO transactions (reference, src_wallet, dst_wallet, amount, fee, type, label, created_at)
     VALUES (?,?,?,?,?,?,?,?)'
);
// wallet_id par login
$walletId = [];
$rows = $pdo->query('SELECT w.id, u.login FROM wallets w JOIN users u ON u.id = w.user_id')->fetchAll();
foreach ($rows as $r) { $walletId[$r['login']] = (int)$r['id']; }

$history = [
    ['tanohyohan@ics-bank.local',   'kassinelly@ics-bank.local',   2500000,  2500, 'Remboursement projet'],
    ['massambafran@ics-bank.local', 'tanohyohan@ics-bank.local',   8000000,  8000, 'Honoraires conseil'],
    ['ebagniniahize@ics-bank.local','malicevictoria@ics-bank.local',15000000,15000,'Acompte dossier'],
    ['kassinelly@ics-bank.local',   'tanohyohan@ics-bank.local',    500000,    500, 'Participation déjeuner'],
    ['malicevictoria@ics-bank.local','massambafran@ics-bank.local', 3200000,  3200, 'Frais mission'],
];
$i = 0;
foreach ($history as $h) {
    [$src,$dst,$amount,$fee,$label] = $h;
    $ref = 'TX' . date('ymd') . str_pad((string)++$i, 5, '0', STR_PAD_LEFT);
    $date = date('Y-m-d H:i:s', strtotime("-{$i} days"));
    $txStmt->execute([$ref, $walletId[$src], $walletId[$dst], $amount, $fee, 'transfer', $label, $date]);
}

echo "Terminé. Comptes de démonstration prêts.\n";
