<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Models\Client;
use App\Models\Contact;

final class ContactController
{
    private Contact $contacts;
    private Client $clients;

    public function __construct()
    {
        $this->contacts = new Contact();
        $this->clients = new Client();
    }

    /* ---------- Pages ---------- */

    public function index(): void
    {
        View::render('contacts/index', ['title' => 'Contacts', 'nav' => 'contacts']);
    }

    public function form(?int $id = null): void
    {
        View::render('contacts/form', ['title' => $id ? 'Edit contact' : 'New contact', 'nav' => 'contacts', 'id' => $id]);
    }

    /* ---------- JSON API ---------- */

    public function apiList(): void
    {
        Response::json(['contacts' => $this->contacts->all()]);
    }

    public function apiShow(int $id): void
    {
        $contact = $this->mustFind($id);
        Response::json(['contact' => $contact] + $this->linkPayload($id));
    }

    public function apiCreate(): void
    {
        [$name, $surname, $email] = $this->validated(null);
        try {
            $contact = $this->contacts->create($name, $surname, $email);
        } catch (\PDOException $e) {            // lost a race against the unique email index
            $this->handleDuplicate($e);
        }
        Response::json(['contact' => $contact], 201);
    }

    public function apiUpdate(int $id): void
    {
        $this->mustFind($id);
        [$name, $surname, $email] = $this->validated($id);
        try {
            $contact = $this->contacts->update($id, $name, $surname, $email);
        } catch (\PDOException $e) {
            $this->handleDuplicate($e);
        }
        Response::json(['contact' => $contact]);
    }

    public function apiLink(int $id): void
    {
        $this->mustFind($id);
        $clientId = (int) (Response::body()['client_id'] ?? 0);
        if ($clientId <= 0 || $this->clients->find($clientId) === null) {
            Response::invalid(['client_id' => 'Select a client to link.']);
        }
        $this->contacts->link($id, $clientId);
        Response::json($this->linkPayload($id));
    }

    public function apiUnlink(int $id, int $clientId): void
    {
        $this->mustFind($id);
        $this->contacts->unlink($id, $clientId);
        Response::json($this->linkPayload($id));
    }

    /* ---------- helpers ---------- */

    private function mustFind(int $id): array
    {
        return $this->contacts->find($id) ?? Response::json(['error' => 'Contact not found.'], 404);
    }

    private function linkPayload(int $id): array
    {
        return [
            'clients'   => $this->contacts->linkedClients($id),
            'available' => $this->contacts->availableClients($id),
        ];
    }

    /** @return array{string,string,string} trimmed name, surname, email */
    private function validated(?int $exceptId): array
    {
        $b = Response::body();
        $name    = trim((string) ($b['name'] ?? ''));
        $surname = trim((string) ($b['surname'] ?? ''));
        $email   = trim((string) ($b['email'] ?? ''));

        $v = (new Validator())
            ->required('name', $name, 'Name')->maxLength('name', $name, 100, 'Name')
            ->required('surname', $surname, 'Surname')->maxLength('surname', $surname, 100, 'Surname')
            ->required('email', $email, 'Email')->maxLength('email', $email, 190, 'Email')
            ->email('email', $email);

        if (!$v->fails() && $this->contacts->emailExists($email, $exceptId)) {
            $v->addError('email', 'This email address is already in use.');
        }
        if ($v->fails()) {
            Response::invalid($v->errors());
        }
        return [$name, $surname, $email];
    }

    private function handleDuplicate(\PDOException $e): never
    {
        if (Database::isUniqueViolation($e)) {
            Response::invalid(['email' => 'This email address is already in use.']);
        }
        throw $e;
    }
}
