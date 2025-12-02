# IP Information API - Saulė Einikytė

This project is a solution for the Junior PHP Developer (Symfony focus) assignment.
The goal of the API is to retrieve IP information from ipstack.com, cache it locally for one day, manage a blacklist, and provide bulk operations.

The project is built with Symfony 6, PHP 8.4, Doctrine ORM, Docker and is documented using OpenAPI.

---

## Features

### Retrieve IP Information
- Checks the local database first.
- If data is younger than 1 day, returns the cached entity.
- If data is older than 1 day, fetches fresh data from ipstack and updates the entity.
- If the IP does not exist in the database, data is fetched from ipstack and stored automatically.

### Delete IP Information
- Deletes a cached IP entity from the database.
- Returns a success message when the IP is deleted.
- Returns 404 if the IP is not found.

### Blacklist Management
- Add or remove IPs from a dedicated blacklist entity (BlacklistedIp).
- When an IP is in the blacklist:
  - any attempt to retrieve its information is blocked,
  - the API returns 403 and does not call ipstack.
- Blacklisted IPs can be related to existing IpAddress entities.

### Bulk Endpoints
- Bulk IP lookup.
- Bulk blacklist add/remove.
- Each IP returns a separate success or error status.

### OpenAPI Documentation
All endpoints are documented with PHP attributes.
Available at: http://localhost:8080/api/doc

### Test Coverage
Functional tests cover:
- API health check,
- blacklist blocking behavior,
- deleting cached IP information.

External ipstack calls are mocked.

---

## Running the Project

### Requirements
- Docker
- Docker Compose

### Setup

1. Clone the repository:
   git clone <your-repo-url>
   cd junior-php-2025

2. Start Docker:
   docker compose up -d

3. Install dependencies:
   docker compose exec php composer install

4. Run migrations:
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

5. Open Swagger documentation:
   http://localhost:8080/api/doc

---

## Environment Variables

### .env
A minimal `.env` file is used for development (APP_ENV, APP_SECRET, DATABASE_URL, etc.).

## Endpoints

### Health Check
GET /api/health

### IP Information
GET    /api/ip/{ip}
DELETE /api/ip/{ip}
POST   /api/ip/bulk

### Blacklist
POST   /api/blacklist
DELETE /api/blacklist/{ip}
POST   /api/blacklist/bulk
DELETE /api/blacklist/bulk

---

## Running Tests

docker compose exec php php bin/phpunit

Tests:
- reset DB between runs,
- mock ipstack,
- verify blacklist behavior and cache logic.

---

## Design Decisions

- Business logic placed inside IpService to keep controllers clean.
- Entity structure:
  - IpAddress stores IP information and timestamp.
  - BlacklistedIp stores blacklisted addresses with optional relation.
- Cache implemented via updatedAt timestamp (simple and efficient for this use-case).
- Blacklist is checked before any external API call.
- External API isolated in IpstackClient for better testability.
- Bulk endpoints reuse the same logic but wrap results individually.
- OpenAPI attributes used for clear, code-based API documentation.

---

## What I Learned

- Better understanding of Symfony service structure and separating responsibilities.
- Working with Symfony HttpClient and handling API responses.
- Returning accurate HTTP error codes.
- Documenting API endpoints using OpenAPI attributes.
- Writing functional tests, mocking external services, and preparing test environments.

---

## Data Flow Overview

User Request (GET /api/ip/{ip})
        |
        v
    IpController
        |
        v
     IpService
        |
        |-- 1. Validate IP format
        |
        |-- 2. Check blacklist table
        |       If blacklisted -> return 403
        |
        |-- 3. Check local database (IpAddress entity)
        |       If exists and updatedAt < 1 day -> return cached entity
        |
        |-- 4. Call ipstack via IpstackClient
        |
        |-- 5. Save or update IpAddress entity
        |
        v
Return JSON response

---

## Evaluation Criteria Checklist

REST API implemented: yes  
Cache logic: yes  
External API usage: yes  
Blacklist logic: yes  
Bulk endpoints: yes  
OpenAPI documentation: yes  
Docker environment: yes  
Tests included: yes  
Code readability and comments: yes  
