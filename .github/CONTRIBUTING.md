<!--
  - SPDX-FileCopyrightText: 2016-2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
  - SPDX-License-Identifier: AGPL-3.0-only
-->
## Submitting issues

### Guidelines
* Please search the existing issues first, it's likely that your issue was already reported or even fixed.
  - Go to one of the repositories, click "issues" and type any word in the top search/command bar.
  - You can also filter by appending e. g. "state:open" to the search string.
  - More info on [search syntax within github](https://help.github.com/articles/searching-issues)
* Report issues using our [issue templates](ISSUE_TEMPLATE/); they include all the information we need to track down the issue.

If your issue appears to be a bug, and hasn't been reported, open a new issue.

Help us to maximize the effort we can spend fixing issues and adding new features, by not reporting duplicate issues.

## Contributing to Source Code

Thanks for wanting to contribute source code to the Mail app. That's great!

### Commit messages

This repository uses [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/#summary). The commit types used are

* **feat**: for new features
* **fix**: for bug fixes and production dependency updates
* **perf**: for performance improvements
* **refactor**: for structural code changes with no changed features nor fixed bugs
* **test**: for modified test files
* **docs**: for documentation changes
* **style**: for coding style changes (formatting only)
* **ci**: for changes to our CI setup, like workflow files
* **chore**: for any change that doesn't fit the rest, like development dependency updates

You can also use scopes. Try to have them broad and not specific to a file or ticket. Here are common scopes used:

* **imap**: for changes in the IMAP client
* **ui**: for changes to the web UI

Usage of AI agents has to be made transparent. Therefore, agent-assisted commits have to carry an `Assisted-by: <agent>:<model-id>` trailer before the sign-off. For example: `Assisted-by: Claude Code:claude-sonnet-4-6`

### Documentation

Admin and user documentation for this app lives in the separate
[nextcloud/documentation](https://github.com/nextcloud/documentation) repository
(`admin_manual/groupware/mail.rst`, `user_manual/groupware/mail.rst`) — not in this
repository's `doc/` directory, which is reserved for contributor/developer-facing
content (local setup guides, architecture notes).

Not every change needs a doc update — plenty of small fixes and internal changes don't
touch anything a user or admin would need explained. But a change substantial enough to
need one (a new integration, a new settings area, a new concept users have to learn)
should get it written right away, alongside your feature PR, not deferred to "later":
open a draft PR in `nextcloud/documentation` with the manual change. Keep it in draft
and don't merge it until your feature PR here has merged — the feature can still change
shape during review, and docs merged early drift out of sync with what actually ships.

## Translations

See [our website](https://nextcloud.com/translation/) for how translations of this repository work and how you can get started.

