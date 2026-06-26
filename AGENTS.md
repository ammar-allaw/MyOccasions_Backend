# MyOccasions — Agent Instructions

Laravel 10+ REST API backend for an occasions marketplace (Syria). Connects **Clients** with **Service Providers** (halls, restaurants, photographers, etc.). Flutter/mobile is the primary consumer.

---

## Project Overview

### Actors

| Actor | Guard | Description |
|-------|-------|-------------|
| **Owner** | `auth:owner` | Platform admin — CRUD providers, approve/reject content, RBAC |
| **Client** | `auth:api` | End user — browse/search accepted providers by governorate & role |
| **Service Provider** | `auth:api` | Business account — manage profile, rooms, services, food |

### Core Domain Concepts

- **Polymorphic User**: `User` → `userable` (`Client` | `ServiceProvider`)
- **OrderStatus workflow**: `under_review` → `accepted` | `rejected` (polymorphic on halls, rooms, services, food)
- **TracksChanges**: Provider edits trigger `under_review` with change log
- **Geography**: `Government` / `Region` — clients filtered by governorate
- **Types**: Occasion categories per role (weddings, condolences, …)
- **Main Keys**: Service classification/filter keys
- **Media**: Spatie Media Library — images, gallery, YouTube links
- **i18n**: `Accept-Language: ar|en` header

### Roles (seeded)

`owner`, `client`, `halls`, `clothing stores`, `banquet coordinators`, `aradas`, `photographers`, `restaurants`, `chocolate stores`, `singers`, `makeup saloons`, `flower stores`

---

## Architecture (Mandatory)

```
Request → Route → Controller → Service → Repository → Model
                     ↓              ↓
                 FormRequest    Resource (response)
```

### Layer Responsibilities

| Layer | Responsibility | Must NOT |
|-------|----------------|----------|
| **Controller** | HTTP: validate via FormRequest, call Service, return Resource via Handler | Direct Eloquent, business logic, DB transactions |
| **Service** | Business logic, transactions, authorization checks, orchestration | Raw HTTP, response formatting |
| **Repository** | Data access only: queries, filters, eager loads, CRUD | Business rules, HTTP |
| **Resource** | Transform models/collections to consistent JSON | Business logic |
| **FormRequest** | Input validation & authorization | Database access |

---

## Folder Structure (Target Standard)

When adding or refactoring code, use this structure:

```
app/
├── Http/
│   ├── Controllers/Api/{Entity}Controller.php
│   ├── Requests/{Entity}/{Action}{Entity}Request.php
│   └── Resources/{Entity}/{Entity}Resource.php
├── Repositories/
│   └── {ModelName}/
│       ├── Interface/
│       │   └── {ModelName}RepositoryInterface.php
│       └── Implementation/
│           └── {ModelName}Repository.php
├── Services/
│   └── {ModelName}/
│       ├── Interface/
│       │   └── {ModelName}ServiceInterface.php
│       └── Implementation/
│           └── {ModelName}Service.php
├── Models/
└── Providers/AppServiceProvider.php   ← bind all interfaces
```

### Namespaces

```php
App\Repositories\Food\Interface\FoodRepositoryInterface
App\Repositories\Food\Implementation\FoodRepository
App\Services\Food\Interface\FoodServiceInterface
App\Services\Food\Implementation\FoodService
```

### Legacy Code (migrate gradually)

Existing flat structure (`Repositories/Food/FoodRepository.php` at same level as interface) is **legacy**. Do not copy this pattern for new features. Refactor touched modules to the target structure.

---

## Repository Pattern

```php
// Interface — data access contract only
interface FoodRepositoryInterface
{
    public function query(array $filters = []): Builder;
    public function findById(int $id): Food;
    public function create(array $data): Food;
    public function update(Food $food, array $data): Food;
    public function delete(Food $food): bool;
}

// Implementation — Eloquent + eager loading lives here
class FoodRepository implements FoodRepositoryInterface
{
    private const DEFAULT_WITH = ['mainKey', 'orderStatusAble.status', 'media'];

    public function query(array $filters = []): Builder
    {
        return Food::with(self::DEFAULT_WITH)
            ->when($filters['accepted_only'] ?? false, fn ($q) =>
                $q->whereHas('orderStatusAble.status', fn ($s) => $s->where('name_en', 'accepted'))
            );
    }
}
```

**Rules:**
- All Eloquent queries belong in Repository — never in Controller or Service
- Define default `$with` / `$withCount` arrays as class constants
- Return `Builder` for lists; let Service handle pagination
- Use `findOrFail` for single records

---

## Service Pattern

```php
class FoodService implements FoodServiceInterface
{
    public function __construct(
        private FoodRepositoryInterface $foodRepository,
        private Handler $handler,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        return $this->foodRepository->query($filters)->paginate($perPage);
    }
}
```

**Rules:**
- Inject Repository **interfaces** only (Dependency Inversion)
- One Service per aggregate/domain entity
- DB transactions (`DB::beginTransaction`) in Service, not Controller
- Reusable logic → shared Service or Trait — never duplicate across Controllers

---

## Controller Pattern

```php
class FoodController extends Controller
{
    public function __construct(
        private Handler $handler,
        private FoodServiceInterface $foodService,
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->foodService->list($request->all());

        return $this->handler->successResponse(
            [
                'foods' => FoodResource::collection($paginator),
                'pagination' => $this->paginationMeta($paginator),
            ],
            true,
            $this->message('foods_retrieved'),
            200
        );
    }
}
```

**Rules:**
- Thin controllers — max ~15 lines per action
- Always use FormRequest for POST/PUT/PATCH
- Always return via `$this->handler->successResponse()` / `errorResponse()`
- Always wrap collections in Resource classes
- Extract repeated pagination meta to Controller base or trait

---

## API Response Format

```json
// Success
{ "success": true, "message": "...", "data": { ... } }

// Error
{ "success": false, "message": "...", "data": null }
```

Use `$this->handler->successResponse($data, $success, $message, $statusCode)`.

Localized messages: read `Accept-Language` header (`ar` default, `en` optional).

---

## RESTful API Conventions

| Action | Method | URI Example | Status |
|--------|--------|-------------|--------|
| List | GET | `/api/foods` | 200 |
| Show | GET | `/api/foods/{id}` | 200 |
| Create | POST | `/api/foods` | 201 |
| Update | PUT/PATCH | `/api/foods/{id}` | 200 |
| Delete | DELETE | `/api/foods/{id}` | 200 |

**Route files:** `routes/Api/{Entity}.php` — registered in `routes/api.php`.

**Naming:**
- URIs: kebab-case plural nouns (`/service-providers`, `/main-keys`)
- Route names: `{entity}.{action}` (`foods.index`, `foods.store`)
- Controller methods: REST verbs (`index`, `show`, `store`, `update`, `destroy`)

**Auth middleware:**
- `auth:api` — clients & providers
- `auth:owner` — admin panel
- `auth.provider.or.owner` — both provider and owner

Prefer resourceful routes over action-based names (`/add-food` → `POST /foods`).

---

## N+1 Query Prevention

1. **Repository**: eager load all relationships needed by Resource
2. **Service**: call `loadMissing()` before returning if relationships added dynamically
3. **Resource**: never call `$this->relation` without prior eager load — document required `$with` in Repository
4. **Lists**: never lazy-load inside Resource loops

```php
// ✅ GOOD — eager load in Repository
Food::with(['mainKey', 'orderStatusAble.status', 'media'])->paginate(10);

// ❌ BAD — N+1 in Resource
$this->mainKey->name; // without with('mainKey')
```

Use `$model->loadMissing([...])` in Service when composing data from multiple sources.

---

## SOLID Application

| Principle | Application |
|-----------|-------------|
| **S** — Single Responsibility | Controller=HTTP, Service=business, Repository=data |
| **O** — Open/Closed | Extend via new Service/Repository implementations, not modifying controllers |
| **L** — Liskov Substitution | All implementations honor their interface contracts |
| **I** — Interface Segregation | Small focused interfaces per entity |
| **D** — Dependency Inversion | Controllers/Services depend on interfaces; bind in `AppServiceProvider` |

---

## OrderStatus Workflow

Every content entity (ServiceProvider, Room, Service, Food) uses polymorphic `OrderStatus`:

```
Create → under_review
Owner approves → accepted  (visible to clients)
Owner rejects  → rejected  (+ rejection_reason)
Provider edits → under_review (via TracksChanges trait)
```

Clients see **accepted + active** content only.

---

## Adding a New Feature — Checklist

1. **Migration** — table/columns if needed
2. **Model** — relationships, casts, media collections
3. **Repository** — `Interface/` + `Implementation/`
4. **Service** — `Interface/` + `Implementation/`
5. **FormRequest(s)** — validation rules
6. **Resource(s)** — JSON transformation
7. **Controller** — thin, RESTful
8. **Routes** — `routes/Api/{Entity}.php`
9. **Bind** — register in `AppServiceProvider::register()`
10. **Seeder** — if lookup data needed
11. **Verify** — no N+1, correct HTTP status codes, i18n messages

---

## DI Registration (AppServiceProvider)

```php
$this->app->bind(FoodRepositoryInterface::class, FoodRepository::class);
$this->app->bind(FoodServiceInterface::class, FoodService::class);
```

Every new Interface/Implementation pair **must** be registered here.

---

## Commands

```bash
# Install
composer install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Development
php artisan serve

# Clear caches
php artisan optimize:clear
```

---

## Code Quality Rules

- Match existing naming conventions in touched files
- No duplicated logic — extract to Service, Trait, or base class
- No direct `Model::` calls in Controllers
- No raw `response()->json()` — use Handler
- No business logic in Resources
- Comments only for non-obvious business rules
- Minimize scope — focused diffs, no unrelated changes
- Do not commit `.env` or secrets

---

## Key Paths

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Api/` | API controllers |
| `app/Http/Resources/` | JSON transformers |
| `app/Http/Requests/` | Validation |
| `routes/Api/` | Route definitions |
| `app/Repositories/` | Data access layer |
| `app/Services/` | Business logic layer |
| `app/Traits/TracksChanges.php` | Auto under_review on edits |
| `app/Exceptions/Handler.php` | Response helper + image management |
| `database/seeders/` | Roles, statuses, governments, types |
