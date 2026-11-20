<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Client code = 3 uppercase letters + 3 digits (e.g. FNB001).
 * The numeric part is sequential (001, 002, ...) and tested against SQL until a free code is found.
 */
final class ClientCodeGenerator
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Alpha prefix rules:
     *  - 3+ words        -> initials of the first three words ("First National Bank" => FNB)
     *  - otherwise       -> first three letters of the name   ("Protea" => PRO)
     *  - fewer than 3    -> padded with A, B, C ...           ("IT" => ITA)
     */
    public static function prefix(string $name): string
    {
        $clean = preg_replace('/[^A-Z\s]/', '', strtoupper($name)) ?? '';
        $words = preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $prefix = count($words) >= 3
            ? $words[0][0] . $words[1][0] . $words[2][0]
            : substr(implode('', $words), 0, 3);

        for ($i = 0; strlen($prefix) < 3; $i++) {
            $prefix .= chr(65 + ($i % 26));
        }
        return $prefix;
    }

    /** Lowest unused code for this prefix, checked against the clients table. */
    public function nextCode(string $prefix): string
    {
        $stmt = $this->pdo->prepare('SELECT client_code FROM clients WHERE client_code LIKE :p');
        $stmt->execute(['p' => $prefix . '%']);
        $used = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        for ($n = 1; $n <= 999; $n++) {
            $code = $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            if (!isset($used[$code])) {
                return $code;
            }
        }
        throw new \RuntimeException("No client codes left for prefix $prefix.");
    }
}
