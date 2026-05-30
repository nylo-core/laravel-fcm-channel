## [1.8.2] - 2026-05-30

### Changed
- `AppApiRequestMiddleware` only stages a device metadata field (`uuid`, `model`, `display_name`, `platform`, `version`) for update when the incoming `X-DMETA` value actually differs from the stored value. When every field matches, the update array stays empty and the `update()` call is skipped entirely — avoiding a no-op `fill()`/`save()` and its `saving`/`saved` model events. Mirrors the existing `fcm_token` change-detection check

## [1.8.1] - 2026-05-15

### Added
- 5 new feature tests for `LaravelFcmController` covering: cross-user isolation on `PUT /device`, validation rejection of non-boolean `is_active` (`422`), validation rejection of non-string meta fields (`422`), enforcement of the `updateMeta` allowlist (unsupported body keys silently ignored), and field-preservation on partial updates. Test suite: 83 → 88 tests, 175 → 191 assertions

### Changed
- Renamed `test_update_meta_returns_403_when_device_belongs_to_different_user` to `test_update_meta_cannot_modify_another_users_device`. The previous name implied a `403` response, but the test asserts `200` because `AppApiRequestMiddleware` re-resolves the device to the attacker rather than blocking the request
- Removed an unused `$device` assignment in `test_update_returns_400_when_no_payload_provided` (the row is created for its side effect; no need to retain the reference)

## [1.8.0] - 2026-05-14

### Added
- `PATCH /device/meta` endpoint via `LaravelFcmController::updateMeta` for updating a device's `uuid`, `model`, `display_name`, `platform`, and `version`. Returns `200` on success, `400` when no fields are provided
- `FcmUpdateMetaRequest` form request enforcing the same device-ownership authorization as `FcmUpdateRequest`
- Migration `add_unique_index_to_fcm_devices_table` adds a unique index on `(fcm_token, notifyable_id, notifyable_type)`. Runs a pre-flight duplicate check and aborts with a diagnostic `SELECT` query if existing data would violate the constraint, rather than letting `ALTER TABLE` fail mid-deploy. **Operators: if the migration aborts, run the SQL it prints to identify duplicates and deduplicate before re-running.**
- 18 new tests covering middleware lookup paths, soft-delete restore, controller endpoints, and request authorization (test suite: 65 → 83 tests, 127 → 175 assertions)

### Changed
- **`AppApiRequestMiddleware` rewrite** — replaces the legacy `firstOrCreate` flow with an explicit `fcm_token → uuid → create` lookup chain. The token lookup is now scoped by `notifyable_type`, preventing device leakage across notifyable models that share an id. Existing devices have their meta fields refreshed on every authenticated request when those fields are present in `X-DMETA`
- **Soft-deleted devices now auto-restore on re-registration.** Middleware uses `withTrashed()` on both lookups and calls `restore()` + `is_active = 1` when a match is trashed, so the new unique index does not block legitimate reinstall flows. **Behavior change**: if your app relies on soft-delete as a "device disabled" signal, an incoming request matching the trashed row will revive it
- **`X-DMETA` now requires a non-empty `uuid`.** Requests without a valid uuid return `400` immediately with a dedicated log line instead of failing later at the DB layer. **Behavior change**: previously, malformed requests with NULL uuid could sometimes match unrelated rows
- `FcmUpdateRequest::authorize()` now reads `device` via `$this->input()` instead of `$this->get()` — reads JSON bodies in addition to form/query data

### Fixed
- New devices now persist `fcm_token` on initial creation. The old `firstOrCreate` did not write `fcm_token` on insert, requiring a follow-up `PUT /device` call to set it
- An existing device's `is_active` is now set to `1` when its `fcm_token` changes, ensuring re-registered devices are immediately notifiable

### Style
- Apply Laravel Pint formatting across `FcmAppServiceProvider`, jobs, model, trait, and several test files (constructor parens removed, FQN annotations normalised to short imports)

## [1.7.1] - 2026-04-20

### Security
- Bump `phpunit/phpunit` dev dependency to `^12.5.22` to pick up the patch for [GHSA-qrr6-mg7r-m243](https://github.com/advisories/GHSA-qrr6-mg7r-m243) (argument injection via newline in PHP INI values forwarded to child processes, CVSS 7.8). Dev-only dependency — does not affect package consumers at runtime.

## [1.7.0] - 2026-04-20

### Added
- `FcmMessage::sendToTokens(array $tokens)` — dispatch a single multicast job to an arbitrary list of FCM tokens
- `FcmMessage::sendToNotifiables(iterable $notifiables)` — pool every active token across a set of notifiables into one multicast job
- `FcmSendToTokensJob` — new queued job that chunks tokens into batches of 500 (Firebase multicast limit) with token de-duplication and auto-deactivation on invalid/unknown tokens
- `FcmCloudMessagingService::sendToTokens()` — multicast send path mirroring existing per-user behavior
- `PriorityLevel` enum (`HIGHEST`, `LOWEST`) — replaces stringly-typed priority match
- Indexes on `fcm_devices`: `(notifyable_id, notifyable_type)` and `is_active` (applies to fresh installs only — see Changed below)
- Expanded test suite (65 tests, 127 assertions) including middleware, update request authorization, controller endpoints, enum, multicast broadcast, and FirebaseService error paths

### Changed
- **BREAKING:** Minimum PHP version raised from `>=8.0` to `>=8.1` (required by the new `PriorityLevel` enum). PHP 8.0 reached end-of-life in November 2023.
- **BREAKING (fresh installs only):** Migration `create_fcm_devices_table` column `notifyable_id` changed from `integer` to `unsignedBigInteger`. Existing installs are unaffected (Laravel won't re-run an applied migration); to pick up the new indexes and column type on an existing install, ship a follow-up migration.
- `FcmMessage` properties and methods now fully typed (nullable types, return types, `array`/`iterable` parameters)
- `FcmSendNotificationJob::__construct` parameter `$device` is now `?FcmDevice` (was untyped)
- `AppApiRequestMiddleware::handle` now declares `: mixed` return type
- README: added "Broadcasting to Many Tokens" section documenting `sendToTokens` and `sendToNotifiables`

### Fixed
- Untracked `.claude/settings.local.json` and `.phpunit.cache/test-results` so they no longer pollute commits

## [1.6.1] - 2026-01-12

* Fix null FCM token error in FcmSendNotificationJob

## [1.6.0] - 2026-01-12

* Fix sendMessage() bug - now correctly passes array to sendMulticast
* Add device authorization check in FcmUpdateRequest
* Refactor FcmCloudMessagingService - extract buildCloudMessage() and handleFailures() methods
* Add complete test suite (36 tests)
* Update dev dependencies (orchestra/testbench, phpunit) for PHP 8+ compatibility
* Add CLAUDE.md documentation

## [1.5.1] - 2026-01-12

* Update .gitignore to include CLAUDE.md

## [1.5.0] - 2026-01-12

* Fix Closure serialization error in queued FCM jobs
* Auto-deactivate invalid/unregistered FCM tokens

## [1.4.1] - 2025-05-21

* Composer.json update

## [1.4.0] - 2025-05-21

* Add logging to jobs

## [1.3.0] - 2025-03-17

* Fix logic in middleware

## [1.2.0] - 2025-02-26

* Laravel 12 support
* update readme file

## [1.1.1] - 2025-02-02

* update readme file

## [1.1.0] - 2025-02-02

* Small tweaks and improvements

## [1.0.5] - 2024-06-12

* Update push_token column in the fcm_devices table

## [1.0.4] - 2024-06-10

* Update Middleware

## [1.0.3] - 2024-06-08

* Update composer.json file
* Add funding.yml file

## [1.0.2] - 2024-06-08

* Update readme file
* Fix FcmMessage class
* Add postman collection example

## [1.0.1] - 2024-06-07

* Update web.php file

## [1.0.0] - 2024-06-06

* Initial release
