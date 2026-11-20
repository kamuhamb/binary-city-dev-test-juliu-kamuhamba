-- Binary City Practical Test: PostgreSQL schema
-- Usage:  createdb binary_city && psql -d binary_city -f database/schema.pgsql.sql

CREATE TABLE clients (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    client_code CHAR(6)      NOT NULL,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_clients_code UNIQUE (client_code),                 -- code must be unique
    CONSTRAINT ck_clients_code CHECK (client_code ~ '^[A-Z]{3}[0-9]{3}$')  -- 3 letters + 3 digits
);
-- Clients list is ordered by Name ascending (case-insensitive)
CREATE INDEX ix_clients_name ON clients (LOWER(name));

CREATE TABLE contacts (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    surname    VARCHAR(100) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);
-- Email unique across all contacts, case-insensitive (A@x.com == a@x.com)
CREATE UNIQUE INDEX uq_contacts_email ON contacts (LOWER(email));
-- Contacts list / pickers are ordered by Full Name "{Surname} {Name}"
CREATE INDEX ix_contacts_fullname ON contacts (LOWER(surname || ' ' || name));

-- Many-to-many junction table
CREATE TABLE client_contact (
    client_id  INTEGER     NOT NULL REFERENCES clients(id)  ON DELETE CASCADE,
    contact_id INTEGER     NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    linked_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (client_id, contact_id)             -- no duplicate links; serves client -> contacts
);
CREATE INDEX ix_cc_contact ON client_contact (contact_id, client_id);  -- serves contact -> clients
