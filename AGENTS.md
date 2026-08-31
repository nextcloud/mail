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

### Code comments

The diff shows *what* changed and *how*; a comment exists only to capture *why* — intent the code and names can't convey on their own.

- **Default to none.** Most changes need zero comments; express intent through clear names and small functions. This codebase uses essentially none.
- **Comment the *why*, never the *how*.** Legitimate reasons: a non-obvious workaround (link the issue/ticket), a subtle business rule, a deliberate deviation from the expected. Never narrate what the next lines do.
- **No multi-line explanatory blocks, AI-style walkthroughs, or section banners.**
- **Remove a stale comment** only when you're already editing that code for the task at hand — no drive-by cleanups.

## Coding conventions

These are the points maintainers raise again and again in review. Following them up
front keeps review focused on design instead of the same recurring notes. CodeRabbit
reads this file automatically and reviews against these conventions, so the bot flags
the same things — but the author should not need the bot to learn them.

### Scope & commits
- **One concern per PR.** Keep unrelated edits, drive-by refactors, code-style churn,
  dependency/lock-file bumps and new runtime-version support out of a feature PR — put
  them in a separate PR so they stay backportable to stable branches. Small,
  single-purpose PRs sail through; large diffs draw change requests.
- **The commit type must match the change** (see `.github/CONTRIBUTING.md`): moving code
  with no behavior change is `refactor:`, not `feat:`. Scopes stay broad (`imap`, `ui`).
- **Reuse before reinventing.** Grep for an existing helper, mapper, constant or pattern
  and mirror it instead of writing a parallel implementation.

### PHP backend
- **Types over PHPDoc.** Prefer native param/return/property types; drop PHPDoc that only
  restates them; keep `@throws` and anything that adds information. Use precise
  `psalm-type` array shapes (reuse existing shape definitions) instead of bare `array`.
  Handle Psalm's possible-null and
  `string|false` (e.g. `file_get_contents`) results — throw a `ServiceException`, don't
  cast the failure away. Use constructor property promotion and strict comparison
  (`===`, `in_array(..., true)`).
- **Exceptions.** Catch narrowly — never a blanket `\Exception`/`\Throwable` for a
  specific failure. When wrapping, pass the original as `$previous` (or rethrow) so the
  stack trace survives. Throw domain exceptions; don't leak abstraction-layer exceptions
  (`DoesNotExistException`, storage exceptions) out of a service. Keep `@throws` in sync
  with the interface contract.
- **Logging.** Inject `LoggerInterface`; log with a meaningful message and
  `['exception' => $e]` context at the right level. Log non-critical conditions at
  info/debug and return rather than throwing or spamming warnings. Never swallow errors.
- **Dependency injection & layering.** Inject collaborators (incl. OCP services) via the
  constructor, not the service locator. Replace `time()` with `ITimeFactory` for
  testability. Don't inject request data such as `userId` into services (controllers
  only) — pass it as an argument so the service stays usable from background jobs. Keep
  controllers thin (request/response only); business logic and DB access belong in
  services; keep constants in their owning class.
- **Nextcloud API boundaries.** Never use another app's private `\OCA\OtherApp\*` API — go
  through a stable OCP interface. Respect the minimum server version in `appinfo/info.xml`;
  guard newer OCP APIs with `method_exists` + a fallback. Store new config via
  `IAppConfig` (no dots in new keys). Controllers use `#[NoAdminRequired]` for non-admin
  routes (omit it on admin-only ones), HTTP 422 for validation errors, `HTTP::STATUS_*`
  constants, and `TrapError` over hand-written try/catch. Keep 400 (client error) vs 404
  (not found) intentional. Prefer kebab-case URLs with the id in the path and a single
  `resource` route.
- **Controller access control.** Check that the current user owns the resource before
  acting on an incoming id — guard against IDOR, don't act on a guessed id. Take a nullable
  `?string $userId` from the predefined core services and return 401 when it is null.

### Mappers & entities
- Let mappers propagate `DoesNotExistException` / `OCP\DB\Exception` to the caller — don't
  catch not-found inside the mapper. Reuse the `QBMapper` `insert`/`update`/`find` helpers
  instead of hand-writing queries.
- Name accessors `findX`/`getX` (not `store`); keep entity `@method` annotations accurate,
  including `int|null` for nullable columns; register column types with `addType` in the
  constructor.

### Database & migrations
- **Re-runnable:** check column/table existence before changing schema so a partially
  failed migration can retry.
- **Version naming:** name the `Version` class after the *next unreleased* minor and bump
  `version` in `appinfo/info.xml`, or the migration won't run. (See the DB index dual
  pattern for adding indices without a blocking `changeSchema`.)
- **Foreign keys** on referencing columns, with the delete action chosen from the
  relationship: cascade delete for rows the parent owns (so account/mailbox deletion leaves
  no orphans), `SET NULL` for an optional reference, `RESTRICT` to prevent deletion. Clean up
  dangling rows pre-schema.
- **Indexes:** composite column order matters (`[a,b]` ≠ `[b,a]`) — match the query's
  WHERE/ORDER BY; add covering indexes on exactly the filtered/joined columns; make the
  index unique when the column is.
- **Portability & size:** sensible column lengths (avoid MariaDB off-row storage), truncate
  before writing fixed-width columns, Oracle needs nullable booleans and treats empty
  strings as `NULL`, Postgres/Oracle are strict about VARCHAR-vs-INT. Batch huge
  UPDATE/DELETE and emit progress. Inline entity constants in migrations (a loaded class
  can hold stale values mid-upgrade).

### Performance
- Push filters/limits/cursors into the DB query; scope by `user_id`; use `WHERE ... IN`
  to avoid N+1; don't `array_merge` in a loop. Reuse one Horde IMAP client across a bulk
  operation instead of reconnecting per message. Stream large result sets via generators
  and guard against OOM — users can have 200+ mailboxes. Prefer a local cache over a
  distributed one for recomputable values and scope cache keys per user.

### Frontend (Vue / JS)
- **Async:** `async`/`await` with `try`/`catch`, never mixed with `.then`; never `await`
  inside `forEach` (use `for...of`); a missing `await` is a real bug. Await sequential
  per-item dispatches instead of flooding the backend, and don't fire one request per
  list item — push it to the backend or preload via initial state.
- **Structure:** HTTP handling in the service layer, mutations inside store actions,
  business logic out of components. Add a loading/disabled state to any control that
  triggers an async action so a double click can't fire it twice. Don't make an element
  look clickable when its action is unavailable.
- **Style of code:** early returns over nested conditions, named constants over magic
  numbers, `const`/`let` never `var`, pure helpers free of side effects and store access.
  Sanitize user-controlled values before they reach the DOM. Use `isDarkTheme` from
  `@nextcloud/vue`, not `window.matchMedia('(prefers-color-scheme: dark)')`. Follow the
  dev-manual naming the linter can't check: multi-word PascalCase component names
  (`SettingsView`, not `Settings`), prefixed sub-components, and acronyms with only the
  first letter capitalised (`callHttpApi`).
- **CSS:** see [Styling](#styling); keep styles scoped, follow BEM, prefer a modifier class
  over manipulating inline style, use grid/spacing/breakpoint CSS variables (no hard-coded
  breakpoints), and remove now-unused styles. Avoid `::v-deep`/`!important` into upstream
  component internals; where a deep selector is genuinely needed, comment why so it isn't
  dropped by mistake.

### Internationalization
- Wrap every user-facing string, **including aria-labels**, in `t('mail', …)`. Use one
  string with placeholders — never concatenate translated fragments (translators reorder
  words). Use `n('mail', …)` with a `%n` placeholder for counts. Never compare against a
  hard-coded English string or use a translated string as a key. No HTML inside a
  translation string — translate the plain parts and HTML-encode the inserts.

### Accessibility
- Real anchor (`<a href … target="_blank">`) for links, not a JS click handler, so screen
  readers and native middle-click work. Use semantically correct elements and let
  `NcButton` inject the required a11y attributes rather than hand-rolling clickable markup.

### Mail-specific gotchas
- **IMAP UIDs are only unique within one mailbox** — never treat them as global
  identifiers; key on the database primary key.
- The app already has building blocks (trusted senders, RFC-2822 address parsing,
  `IMAPClientFactory`, `Horde_Mail_Rfc822_Identification`) — reuse them.

## Testing

### Unit Tests
Located in `tests/Unit/` with structure mirroring `lib/`.

#### Pattern
- Use **arrange-act-assert** structure with blank lines separating each phase (no literal comments)
- Mock dependencies via `$this->createMock(Interface::class)`
- Setup mocks in `setUp()` for common fixtures
- **Cover the error and edge paths**, not just the happy path (empty input, the throwing branch, both sort orders); new classes and changed logic need tests
- Declare typed fixture properties (`private Foo&MockObject $foo;`) to avoid dynamic-property deprecation warnings; test methods return `void`
- Only mock external collaborators, never the class under test; assert arguments with `->with(...)` and, instead of the removed `withConsecutive`, branch on the argument with `match`/`if` so behavior depends on input, not call order
- Use the same constants in tests as in production code (don't hard-code their values)
- Hand-written stubs of another app's API give false safety — Psalm keeps passing when the upstream API changes, so keep them in sync or avoid them

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

Commit messages are the project's durable record — read years later through `git log` and `git blame`. The diff already shows *what* changed and *how*; the message exists to capture **intent**: the *why*.

- **Subject**: conventional-commit, imperative, concise — aim for under 60 characters, or GitHub truncates it.
- **Body**: state the facts plainly — the problem and the reason for this approach. Keep it matter-of-fact; no storytelling, filler, or marketing tone. Omit it only when the subject already conveys the intent.
- **Record what was intentionally left out.** Note deliberate omissions and deferrals (and why) so a later reader can tell a conscious decision from an oversight.
- **Don't restate the diff.** Avoid mechanical bullet lists that echo the changed lines.

All commits must include two trailers at the end:
1. Agent/model attribution: `Assisted-by: <AgentName>:<model-id>`
2. DCO sign-off: Use `git commit -s` to add automatically

When committing, use: `git commit -m "message" -s`

This ensures the sign-off includes your configured Git user email.

Example:
```
fix(imap): tolerate servers that omit UIDVALIDITY on SELECT

Some proxies drop UIDVALIDITY, which made us discard the local
cache and force a full resync on every run. Treat a missing
value as unchanged instead.

Assisted-by: Devstral:devstral-small-2507
Assisted-by: ClaudeCode:claude-sonnet-4-6
Assisted-by: Qwen:qwen3-coder-32b
Assisted-by: Copilot:gpt-4o
Signed-off-by: Name <email>
```

### Styling

For all CSS colors, spacing, and dimensions, you must use the standard Nextcloud CSS variables.

Do not leave any magic numbers. If you need more specific control over dimensions use `calc(x*var)` when necessary.

You can find the CSS variables already in use in this repository, and the full documentation available at this link: https://docs.nextcloud.com/server/latest/developer_manual/html_css_design/css.html.
