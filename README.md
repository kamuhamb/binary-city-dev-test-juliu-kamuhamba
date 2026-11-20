# Binary City: Clients & Contacts Management System

**Binary City Development Practical Test – November 2026**

A web-based Client and Contact Management application built using vanilla PHP, Object-Oriented Programming (OOP), MVC architecture, PostgreSQL, and JavaScript/AJAX.

## 1. Requirements

- PHP 8.1 or later
- PostgreSQL
- PHP PDO PostgreSQL extension (`pdo_pgsql`)
- Modern web browser

## 2. Installation and Setup

Create the database:

```bash
createdb binary_city
```

Import the database schema:

```bash
psql -d binary_city -f database/schema.pgsql.sql
```

Configure database credentials using the environment variables:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
```

Refer to `config/config.php` for configuration details.

Start the PHP development server:

```bash
php -S localhost:8000 -t public public/index.php
```

Open the application:

```text
http://localhost:8000
```

For Apache deployment, configure the document root to point to the `public/` directory. An `.htaccess` file is included.

## 3. Project Structure

```text
public/
    index.php
    assets/app.js

src/
    Core/
    Models/
    Controllers/
    Services/

views/

database/
    schema.pgsql.sql
    schema.dbml

tests/
    code_generator_test.php

Dev/
    Test Cases/
```

### Architecture

- **Core:** Routing, database connectivity, validation, views, and HTTP responses.
- **Models:** Client and Contact data operations.
- **Controllers:** Client and Contact application workflows and JSON API endpoints.
- **Services:** Client code generation.
- **Views:** Application layouts and page structures.
- **Public:** Front controller and frontend JavaScript/AJAX functionality.
- **Database:** PostgreSQL schema and ER diagram definition.
- **Tests:** Automated tests and functional testing documentation.

## 4. QA and Testing

The functional test documentation is included at:

`Dev/Test Cases/Binary_City_Clients_Contacts_Test_Script.docx`

The test script covers:

- Client and contact management workflows.
- Automatic client code generation.
- Input validation.
- Email uniqueness.
- Many-to-many client/contact relationships.
- Database integrity.
- Basic security and reliability checks.

Run the automated client-code generator tests:

```bash
php tests/code_generator_test.php
```

Database-backed tests require the PHP PDO PostgreSQL extension and access to a PostgreSQL test database.

Use a disposable QA database when running tests that insert or modify records.

## 5. Database Technology

The application uses PostgreSQL for persistent data storage and PDO for database access.

The database schema is provided in:

`database/schema.pgsql.sql`

An ER diagram definition is available in:

`database/schema.dbml`

## 6. Developer

**Juliu Kamuhamba**

Binary City Development Practical Test – November 2026