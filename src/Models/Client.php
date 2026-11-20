<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\ClientCodeGenerator;
use PDO;

final class Client
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** All clients ordered by Name ascending, with linked contact count. */
    public function all(): array
    {
        return $this->db->query(
            'SELECT c.id, c.name, c.client_code,
                    (SELECT COUNT(*) FROM client_contact cc WHERE cc.client_id = c.id)::int AS contact_count
               FROM clients c
              ORDER BY LOWER(c.name), c.id'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, client_code FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Insert with a generated code; retries if another request grabbed the same code first. */
    public function create(string $name): array
    {
        $generator = new ClientCodeGenerator($this->db);
        $prefix = ClientCodeGenerator::prefix($name);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = $generator->nextCode($prefix);
            try {
                $stmt = $this->db->prepare(
                    'INSERT INTO clients (name, client_code) VALUES (:name, :code)
                     RETURNING id, name, client_code'
                );
                $stmt->execute(['name' => $name, 'code' => $code]);
                return $stmt->fetch();
            } catch (\PDOException $e) {
                if (!Database::isUniqueViolation($e)) {
                    throw $e;
                }
                // code taken in the meantime: loop and test the next number against SQL
            }
        }
        throw new \RuntimeException('Could not generate a unique client code.');
    }

    public function updateName(int $id, string $name): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE clients SET name = :name WHERE id = :id RETURNING id, name, client_code'
        );
        $stmt->execute(['name' => $name, 'id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Contacts linked to this client, ordered by "{Surname} {Name}". */
    public function linkedContacts(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ct.id, ct.surname || ' ' || ct.name AS full_name, ct.email
               FROM client_contact cc
               JOIN contacts ct ON ct.id = cc.contact_id
              WHERE cc.client_id = :id
              ORDER BY LOWER(ct.surname || ' ' || ct.name), ct.id"
        );
        $stmt->execute(['id' => $clientId]);
        return $stmt->fetchAll();
    }

    /** Contacts not yet linked (for the "link contact" picker). */
    public function availableContacts(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ct.id, ct.surname || ' ' || ct.name AS full_name, ct.email
               FROM contacts ct
              WHERE NOT EXISTS (SELECT 1 FROM client_contact cc
                                 WHERE cc.contact_id = ct.id AND cc.client_id = :id)
              ORDER BY LOWER(ct.surname || ' ' || ct.name), ct.id"
        );
        $stmt->execute(['id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function link(int $clientId, int $contactId): void
    {
        $this->db->prepare(
            'INSERT INTO client_contact (client_id, contact_id) VALUES (:c, :ct) ON CONFLICT DO NOTHING'
        )->execute(['c' => $clientId, 'ct' => $contactId]);
    }

    public function unlink(int $clientId, int $contactId): void
    {
        $this->db->prepare(
            'DELETE FROM client_contact WHERE client_id = :c AND contact_id = :ct'
        )->execute(['c' => $clientId, 'ct' => $contactId]);
    }
}
