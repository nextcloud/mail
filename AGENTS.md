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
Available composer commands:
```bash
composer cs:check                # Check code style
composer cs:fix                  # Fix code style
composer psalm                   # Run static analysis
composer test:unit               # Run unit tests
composer test:integration        # Run integration tests
composer openapi                 # Generate OpenAPI spec
```
See `composer.json` for all available commands.

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
- **REUSE & SPDX**: Every file requires an SPDX license header. **New files must use `AGPL-3.0-or-later`, never `AGPL-3.0-only`**. Header format:
  ```php
  /*
   * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
   * SPDX-License-Identifier: AGPL-3.0-or-later
   */
  ```
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

### Integration Tests
Located in `tests/Integration/`.

#### Running Tests
```bash
composer test:integration                                           # Run all integration tests
composer test:integration -- tests/Integration/IMAP/MessageMapperTest.php # Run specific test file
composer test:integration -- --filter="TestClassName"               # Run tests matching filter
composer test:integration:dev                                       # Run and stop on first failure
```

## Git Workflow

Do NOT commit changes unless explicitly asked to do so.

After completing code changes:
1. Verify your work is complete and tests pass
2. Never push directly to `main` — always create a feature branch with a descriptive name (e.g. `perf/imap-selective-headers`, `fix/sync-token`, `chore/update-agents`).
3. Worktree branches must use descriptive feature-branch names, not generated names like `agent-xxxx`.
4. Make sure there is no trailing whitespace
5. Leave changes in working directory or staged (do not commit)
6. Provide a summary of what was changed and why
7. Suggest a commit message using Conventional Commits format
   - There is a [contributing doc](./.github/CONTRIBUTING.md) with suggestions
8. The user will review and commit when ready

### PR Review Workflow

Once a branch is pushed and under review, **do not force-push**. Reviewers track changes incrementally — a force-push destroys that history and forces them to re-read the full diff from scratch.

Instead, address feedback with **fixup commits**:
```bash
git commit --fixup=<sha>   # targets the specific commit being corrected
```

The branch will be rebased and squashed into a clean history before merge (CI enforces this). The failing "clean history" CI check is intentional and expected during review — ignore it until the PR has a positive review, then rebase to clean up.

### Commit Message Format

All commits must include two trailers at the end:
1. Agent/model attribution: `Assisted-by: <AgentName>:<model-id>`
2. DCO sign-off: Use `git commit -s` to add automatically

When committing, use: `git commit -m "message" -s`

This ensures the sign-off includes your configured Git user email.

Example:
```
fix(deps): Update package dependencies

- Updated package X to latest stable version
- Verified all tests pass

Assisted-by: Claude:claude-sonnet-4-6
Signed-off-by: Name <email>
```

### Styling

For all CSS colors, spacing, and dimensions, you must use the standard Nextcloud CSS variables.

Do not leave any magic numbers. If you need more specific control over dimensions use `calc(x*var)` when necessary.

You can find the CSS variables already in use in this repository, and the full documentation available at this link: https://docs.nextcloud.com/server/latest/developer_manual/html_css_design/css.html.
