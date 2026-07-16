## Context

HarvestHaul is a Laravel 12 app (PHP 8.2+, MySQL, Blade + Tailwind, Leaflet maps, PWA) at C:\Users\ADMIN\HarvestHaul0.0.1. We're improving codebase quality based on a Laravel Patterns Compliance Report (score 5/10). Tests use SQLite in-memory with PHPUnit 11.5.55. There are zero production tests — all verification is manual + `php artisan test`.

## Completed

- PoolingJob `$fillable` (was `$guarded`)
- Route-Model Binding for Harvest routes (`{id}` → `{harvest}`)
- Policy classes: `PoolingJobPolicy` (view/update/manageHarvests), `NegotiationPolicy` (view/update)
- FormRequests: `StoreHarvestRequest`, `UpdateHarvestRequest`
- CropCategory factory, TruckFactory fix (added `truck_name`)
- Base Controller: added `AuthorizesRequests` + `ValidatesRequests` traits
- Policy wiring: PoolingJobController and NegotiationController now use `$this->authorize()` instead of manual auth checks
- 129 tests all passing

## Remaining

- **FormRequests for PoolingJobController** (plan, confirm have inline Validator::make blocks)
- **FormRequests for NegotiationController** (already uses FormRequests but has inline validation in agreeTerms)
- **FormRequests for AdminController** (large inline validation blocks)
- Status constants (P13) — deferred until more test coverage exists

## Instructions

- Run `php artisan test` after every change
- Run `php -l` on all modified files
- Every approach should be stress-tested for new issues before implementation
- Don't apply fixes that are too risky without test coverage
- HarvestTest (14 tests), NegotiationTest (22 tests), PoolingJobPolicyTest (13 tests) are the three new test files — all currently pass
