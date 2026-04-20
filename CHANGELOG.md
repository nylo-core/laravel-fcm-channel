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
