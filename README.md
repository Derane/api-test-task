# API:users [Symfony]

REST API for user management with Bearer token authentication and role-based access control.

## Requirements

- Docker & Docker Compose

## Quick Start

```bash
make setup            # build containers, start, install dependencies
make migrate          # run database migrations
make fixtures         # load test data (root + user)
```

App available at `http://localhost:8080`.

## Database Dump

Instead of migrations + fixtures you can import the ready-made dump:

```bash
make dump-import      # import dump.sql into database
```

To re-export current DB state:

```bash
make dump             # save dump to dump.sql
```

## API

Base URL: `/v1/api/users`

All requests require `Authorization: Bearer <token>` header.

**Test tokens (from fixtures):**

| Role | Login | Token                                  |
|------|-------|----------------------------------------|
| root | root  | `root-api-token-for-testing-purposes`  |
| user | user  | `user-api-token-for-testing-purposes`  |

**Endpoints:**

| Method | URL                  | Description  |
|--------|----------------------|--------------|
| GET    | `/v1/api/users`      | List users   |
| GET    | `/v1/api/users/{id}` | Show user    |
| POST   | `/v1/api/users`      | Create user  |
| PUT    | `/v1/api/users/{id}` | Update user  |
| DELETE | `/v1/api/users/{id}` | Delete user  |

**Request body (POST / PUT):**

```json
{
  "login": "john",
  "phone": "12345678",
  "pass": "secret"
}
```

All fields: max 8 characters.

## Tests

```bash
make test-setup       # first-time test DB setup
make test             # run all tests
make test-unit        # unit tests only
make test-e2e         # e2e tests only
```
