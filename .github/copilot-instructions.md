<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Copilot Code Review Instructions

## Scope
Only review for the following:
- **Bugs**
- **Security**: Injection vulnerabilities (SQL, command, XSS), hardcoded
  secrets or credentials, insecure deserialization, broken auth,
  path traversal, unsafe use of eval or dynamic code execution.

## Strict exclusions — do not comment on these
- Code style, formatting, or whitespace
- Naming conventions (variables, functions, classes, files)
- Suggestions to refactor or restructure working code
- Performance micro-optimizations unless they cause a measurable performance regression or issue
- Alternative ways to write functionally equivalent code

## If no bugs or security issues are found
Leave a short positive review. Example:
> "No bugs or security issues found. Looks good to me."

## Review format
Do not include a summary or overview of the changes at the start of the review.
Go directly to findings, or if there are none, leave only the approval line.

## Comment format (when issues are found)
For each issue, state:
1. **Type**: Bug or Security
2. **Severity**: Critical / High / Medium
3. **Problem**: What is wrong and why it matters
4. **Suggestion**: A concrete fix, not a vague recommendation

Do not leave comments that don't fit this format.
