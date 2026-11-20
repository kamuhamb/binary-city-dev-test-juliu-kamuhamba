# Binary City: Clients & Contacts (vanilla PHP, MVC, PostgreSQL)

## Run it
```bash
createdb binary_city
psql -d binary_city -f database/schema.pgsql.sql

# optional overrides: DB_HOST DB_PORT DB_NAME DB_USER DB_PASS (see config/config.php)
php -S localhost:8000 -t public public/index.php
```
Open http://localhost:8000. Requires PHP 8.1+ with `pdo_pgsql`. On Apache, point the document root at `public/` (`.htaccess` is included).

## Structure
```
public/index.php          front controller, routes, autoloader
public/assets/app.js      AJAX calls, tabs, client-side validation (mirrors PHP rules)
src/Core/                 Router, Database (PDO), Validator, View, Response
src/Models/               Client, Contact (all SQL lives here)
src/Controllers/          ClientController, ContactController (pages + JSON API)
src/Services/             ClientCodeGenerator
views/                    layout + page shells (data loaded via AJAX)
database/                 schema.pgsql.sql, schema.dbml (ER diagram for dbdiagram.io)
tests/                    php tests/code_generator_test.php
```

## QA / Testing
A functional test script is included at `Dev/Test Cases/Binary_City_Clients_Contacts_Test_Script.docx`.
It covers client and contact workflows, generated client codes, validation, email uniqueness,
many-to-many relationships, database integrity, and basic security/reliability checks.

Run the automated client-code generator tests with:
```bash
php tests/code_generator_test.php
```
The database-backed portion requires PHP PDO PostgreSQL support (`pdo_pgsql`) and a reachable
PostgreSQL test database. Use a disposable QA database when executing tests that insert or modify data.
