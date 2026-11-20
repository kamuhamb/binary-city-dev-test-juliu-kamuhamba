<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Models\Client;
use App\Models\Contact;

final class ClientController
{
    private Client $clients;
    private Contact $contacts;

    public function __construct()
    {
        $this->clients = new Client();
        $this->contacts = new Contact();
    }

    /* ---------- Pages (HTML shells; data is loaded by AJAX) ---------- */

    public function index(): void
    {
        View::render('clients/index', ['title' => 'Clients', 'nav' => 'clients']);
    }

    public function form(?int $id = null): void
    {
        View::render('clients/form', ['title' => $id ? 'Edit client' : 'New client', 'nav' => 'clients', 'id' => $id]);
    }

    /* ---------- JSON API ---------- */

    public function apiList(): void
    {
        Response::json(['clients' => $this->clients->all()]);
    }

    public function apiShow(int $id): void
    {
        $client = $this->mustFind($id);
        Response::json(['client' => $client] + $this->linkPayload($id));
    }

    public function apiCreate(): void
    {
        $name = $this->validatedName();
        Response::json(['client' => $this->clients->create($name)], 201);
    }

    public function apiUpdate(int $id): void
    {
        $this->mustFind($id);
        $name = $this->validatedName();
        Response::json(['client' => $this->clients->updateName($id, $name)]);
    }

    public function apiLink(int $id): void
    {
        $this->mustFind($id);
        $contactId = (int) (Response::body()['contact_id'] ?? 0);
        if ($contactId <= 0 || $this->contacts->find($contactId) === null) {
            Response::invalid(['contact_id' => 'Select a contact to link.']);
        }
        $this->clients->link($id, $contactId);
        Response::json($this->linkPayload($id));
    }

    public function apiUnlink(int $id, int $contactId): void
    {
        $this->mustFind($id);
        $this->clients->unlink($id, $contactId);
        Response::json($this->linkPayload($id));
    }

    /* ---------- helpers ---------- */

    private function mustFind(int $id): array
    {
        return $this->clients->find($id) ?? Response::json(['error' => 'Client not found.'], 404);
    }

    private function linkPayload(int $id): array
    {
        return [
            'contacts'  => $this->clients->linkedContacts($id),
            'available' => $this->clients->availableContacts($id),
        ];
    }

    private function validatedName(): string
    {
        $name = trim((string) (Response::body()['name'] ?? ''));
        $v = (new Validator())
            ->required('name', $name, 'Name')
            ->maxLength('name', $name, 150, 'Name');
        if ($v->fails()) {
            Response::invalid($v->errors());
        }
        return $name;
    }
}
