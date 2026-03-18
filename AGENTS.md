<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# AGENTS.md

This file provides guidance to AI coding assistants working with code in this repository.

## Commands

### Setup
```bash
composer i
npm ci
```

### JavaScript
See `package.json` scripts for all available commands (build, dev, watch, lint, stylelint, test:unit, test:e2e, etc.).

### PHP
See `composer.json` scripts for all available commands (cs:check, cs:fix, psalm, test:unit, test:integration, openapi, etc.).

## Architecture

### Stack
- **Backend**: PHP (see `appinfo/info.xml` for version requirements), Nextcloud app framework, Horde IMAP/MIME/SMTP libraries. Namespace: `OCA\Mail\`.
- **Frontend**: Vue 2, Pinia, Vue Router 3, CKEditor 5, bundled with webpack.

### PHP Backend (`lib/`)
Layered: Controllers → Services → DB Mappers.

- **`Controller/`** — Thin HTTP handlers; business logic lives in services.
- **`Service/`** — Core logic. Key areas: account management, IMAP sync, mail sending (SMTP), drafts/outbox, S/MIME encryption, ML-based importance classification, AI integrations (thread summaries, follow-up detection).
- **`Db/`** — Nextcloud `QBMapper`-based mappers and entity models.
- **`IMAP/`** — Low-level IMAP via Horde. `IMAPClientFactory` creates authenticated clients; `MessageMapper` fetches raw messages.
- **`BackgroundJob/`** — Nextcloud background jobs for IMAP sync, ML training, outbox sending, etc.
- **`Listener/`** — Event listeners hooked to domain events from `lib/Events/`.
- **`Contracts/`** — Interfaces defining main service boundaries (`IMailManager`, `IMailTransmission`, etc.).
- **`Migration/`** — Database migrations.

### JavaScript Frontend (`src/`)
Single-page Vue 2 app. All routes render through `views/Home.vue`.

- **`store/mainStore.js`** — Central Pinia store (accounts, mailboxes, messages, preferences), split into `actions.js` and `getters.js`. Separate stores for outbox and mail filters.
- **`service/`** — JS services that call the PHP REST API.
- **`components/`** — Vue components (composer, envelope list, thread view, settings, etc.).
- **`router.js`** — Routes for mailbox, thread, outbox, and setup views.

### Key Conventions
- **Registration**: `appinfo/info.xml` registers background jobs, CLI commands, settings pages, navigation entries, and repair steps. `AppInfo/Application.php` registers event listeners and other services via the Nextcloud bootstrap API.
- **Events**: Domain events in `lib/Events/` are dispatched after state changes; `lib/Listener/` reacts to them.
- **Mozart**: Some vendor packages are namespaced into `lib/Vendor/` to avoid conflicts.
- **REUSE**: Every file requires an SPDX license header (`AGPL-3.0-or-later` for new files).
- **OpenAPI**: `ResponseDefinitions.php` documents API types; run `composer openapi` to regenerate the spec.

## Testing

### Unit Tests
Located in `tests/Unit/` with structure mirroring `lib/`.

#### Pattern
- Use **arrange-act-assert** structure with blank lines separating each phase (no literal comments)
- Mock dependencies via `$this->createMock(Interface::class)`
- Setup mocks in `setUp()` for common fixtures

#### Running Tests
```bash
composer test:unit                                    # Run all unit tests
composer test:unit -- tests/Unit/Service/HtmlTest.php # Run specific test file
composer test:unit -- --filter="TestClassName"        # Run tests matching filter
```
