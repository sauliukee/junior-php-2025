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
- Add or remove IPs from a dedicated blacklist entity.
- When an IP is blacklisted:
  - Any request for its information returns 403.
  - The API does not contact ipstack.
- Blacklisted IPs may relate to stored IpAddress entities.

### Bulk Endpoints
- Bulk IP lookup.
- Bulk blacklist add/remove.
- Each IP returns an individual success or error result.

### OpenAPI Documentation
All endpoints are documented with PHP attributes.  
Available at: http://localhost:8080/api/doc

### Test Coverage
Functional tests verify:
- API health check,
- blacklist blocking behavior,
- cache deletion and refresh logic.

External ipstack calls are fully mocked.

---

## Running the Project

### Requirements
- Docker  
- Docker Compose  

### Setup

```bash
# 1. Clone repository
git clone <your-repo-url>
cd junior-php-2025

# 2. Start Docker stack
docker compose up -d

# 3. Install PHP dependencies
docker compose exec php composer install

# 4. Run migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# 5. Access documentation
open http://localhost:8080/api/doc
```

---

## Environment Variables

A minimal `.env` file is used for development:

```
APP_ENV=dev
APP_SECRET=your_secret
DATABASE_URL=mysql://user:pass@db:3306/app
API_KEY_IPSTACK=your_api_key_here
```

---

## Endpoints

### Health Check
```
GET /api/health
```

### IP Information
```
GET    /api/ip/{ip}
DELETE /api/ip/{ip}
POST   /api/ip/bulk
```

### Blacklist
```
POST   /api/blacklist
DELETE /api/blacklist/{ip}
POST   /api/blacklist/bulk
DELETE /api/blacklist/bulk
```

---

## Running Tests

```bash
docker compose exec php php bin/phpunit
```

Tests:
- Database reset per run
- External API mocked
- Blacklist enforcement tested
- Cache logic verified

---

## Design Decisions

- Business logic centralized in `IpService`.
- External API calls contained in `IpstackClient` for testability.
- Cache uses `updatedAt` timestamp to determine refresh necessity.
- Blacklist is checked before any external API call.
- Doctrine entities:
  - `IpAddress` stores IP info, metadata, timestamps.
  - `BlacklistedIp` stores blacklisted entries.
- Bulk endpoints reuse existing service logic.

---

## What I Learned

- Writing clean Symfony service structures.
- Proper use of HttpClient and handling API responses.
- Returning correct HTTP error codes in REST APIs.
- Using OpenAPI attributes for API documentation.
- Writing functional tests with mocks and test environments.

---

## Evaluation Criteria Checklist

REST API implemented: yes  
Cache logic implemented: yes  
External API usage: yes  
Blacklist logic: yes  
Bulk endpoints implemented: yes  
OpenAPI documentation: yes  
Docker environment: yes  
Tests included: yes  
Code readability: yes  
