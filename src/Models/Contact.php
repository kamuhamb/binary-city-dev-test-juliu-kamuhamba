<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Contact
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** All contacts ordered by Full Name "{Surname} {Name}", with linked client count. */
    public function all(): array
    {
        return $this->db->query(
            'SELECT ct.id, ct.name, ct.surname, ct.email,
                    (SELECT COUNT(*) FROM client_contact cc WHERE cc.contact_id = ct.id)::int AS client_count
               FROM contacts ct
              ORDER BY LOWER(ct.surname || \' \' || ct.name), ct.id'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, surname, email FROM contacts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Case-insensitive email check; $exceptId lets a contact keep its own email on edit. */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM contacts WHERE LOWER(email) = LOWER(:email) AND id <> :id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'id' => $exceptId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, string $surname, string $email): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contacts (name, surname, email) VALUES (:n, :s, :e)
             RETURNING id, name, surname, email'
        );
        $stmt->execute(['n' => $name, 's' => $surname, 'e' => $email]);
        return $stmt->fetch();
    }

    public function update(int $id, string $name, string $surname, string $email): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE contacts SET name = :n, surname = :s, email = :e WHERE id = :id
             RETURNING id, name, surname, email'
        );
        $stmt->execute(['n' => $name, 's' => $surname, 'e' => $email, 'id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Clients linked to this contact, ordered by Name ascending. */
    public function linkedClients(int $contactId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.client_code
               FROM client_contact cc
               JOIN clients c ON c.id = cc.client_id
              WHERE cc.contact_id = :id
              ORDER BY LOWER(c.name), c.id'
        );
        $stmt->execute(['id' => $contactId]);
        return $stmt->fetchAll();
    }

    public function availableClients(int $contactId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.client_code
               FROM clients c
              WHERE NOT EXISTS (SELECT 1 FROM client_contact cc
                                 WHERE cc.client_id = c.id AND cc.contact_id = :id)
              ORDER BY LOWER(c.name), c.id'
        );
        $stmt->execute(['id' => $contactId]);
        return $stmt->fetchAll();
    }

    public function link(int $contactId, int $clientId): void
    {
        $this->db->prepare(
            'INSERT INTO client_contact (client_id, contact_id) VALUES (:c, :ct) ON CONFLICT DO NOTHING'
        )->execute(['c' => $clientId, 'ct' => $contactId]);
    }

    public function unlink(int $contactId, int $clientId): void
    {
        $this->db->prepare(
            'DELETE FROM client_contact WHERE client_id = :c AND contact_id = :ct'
        )->execute(['c' => $clientId, 'ct' => $contactId]);
    }
}
