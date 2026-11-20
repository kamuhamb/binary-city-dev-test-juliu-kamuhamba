<?php
declare(strict_types=1);

// Run: DB_PASS=... php tests/code_generator_test.php   (needs the binary_city database)
require __DIR__ . '/../src/Services/ClientCodeGenerator.php';

use App\Services\ClientCodeGenerator as G;

$fail = 0;
function check(string $label, mixed $actual, mixed $expected): void
{
    global $fail;
    $ok = $actual === $expected;
    $fail += $ok ? 0 : 1;
    printf("%s %s => %s%s\n", $ok ? 'PASS' : 'FAIL', $label, var_export($actual, true), $ok ? '' : ' (expected ' . var_export($expected, true) . ')');
}

// Prefix rules from the spec
check('First National Bank', G::prefix('First National Bank'), 'FNB');
check('Protea',              G::prefix('Protea'), 'PRO');
check('IT',                  G::prefix('IT'), 'ITA');
check('symbols stripped, not word breaks', G::prefix('  b&b co-op ltd '), 'BCL');  // words: BB, COOP, LTD
check('digits only',         G::prefix('123'), 'ABC');
check('single letter',       G::prefix('a'), 'AAB');

// Sequential numbering against SQL, filling gaps (uses an isolated temp table)
$pdo = new PDO('pgsql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';dbname=' . (getenv('DB_NAME') ?: 'binary_city'),
    getenv('DB_USER') ?: 'postgres', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TEMP TABLE clients (client_code CHAR(6))');   // shadows the real table in this session
$gen = new G($pdo);
check('empty table', $gen->nextCode('ZZZ'), 'ZZZ001');
$pdo->exec("INSERT INTO clients VALUES ('ZZZ001'),('ZZZ002'),('ZZZ004')");
check('fills first gap', $gen->nextCode('ZZZ'), 'ZZZ003');
$pdo->exec("INSERT INTO clients VALUES ('ZZZ003')");
check('continues after gap', $gen->nextCode('ZZZ'), 'ZZZ005');
check('other prefix unaffected', $gen->nextCode('ABC'), 'ABC001');

exit($fail ? 1 : 0);
