<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Deep-selector and internal-class audit for `@nextcloud/vue` 9

Mail styles `@nextcloud/vue` internals in 152 places. Some of those class names disappear in v9.
This document lists every one of them, so the Wave B CSS repair pass is a checklist rather than
archaeology.

Audited: `@nextcloud/vue` **8.41.0** (installed) against **9.11.0** (published), plus the
transitive libraries whose class names the components re-expose.

## 1. Method

A selector was counted when it appears inside a `<style>` block or an `.scss` file, or in a
`querySelector` / `classList` / `closest()` call, **and** the class name is one the library or one
of its dependencies actually emits. Class names Mail puts on its own markup are tracked separately
(§5) — they cannot break, but they can drift.

To re-run the comparison when v9 is installed, diff the class-name vocabulary of the two `dist`
trees:

```
grep -rohE '[A-Za-z][A-Za-z0-9]*([-_]{1,2}[A-Za-z0-9]+)+' node_modules/@nextcloud/vue/dist \
  --include='*.mjs' --include='*.css' | sort -u
```

## 2. Breaks

Of the 152 sites, **11** lose their class in v9. Six of them are fixed here (§3); five need v9
installed before they can be repaired.

| Class | v9 replacement | Sites | State |
| --- | --- | --- | --- |
| `.button-vue--icon-only` | none — v9 uses `.button-vue:has(.button-vue__text:empty):not(.button-vue--wide)` | `SearchMessages.vue` (2 rules) | fixed |
| `.button-vue--vue-tertiary` | `.button-vue--tertiary` | `MessageHTMLBody.vue:257` | fixed |
| `.information-icon` | unchanged in `NcNoteCard`, but the rule never applied | `MailboxThread.vue` (2 rules) | fixed |
| `.mx-datepicker` | not applicable — component uses `NcDateTimePickerNative` | `SearchMessages.vue` | fixed |
| `.button-vue--text-only` | none — nearest is `.button-vue:not(:has(.button-vue__icon))` | `EnvelopeList.vue:692` | open |
| `.button-vue--vue-secondary` | `.button-vue--secondary` | `Outbox.vue:127` | open |
| `.mx-datepicker`, `.mx-datepicker-popup` | `.dp__*` (`@vuepic/vue-datepicker` 11) | `TaskModal.vue:290`, `EventModal.vue:387`, `OutOfOfficeForm.vue:393` | open |

`NcButton` builds its variant class as `button-vue--vue-${variant}` in v8.41 and as
`button-vue--${variant}` in v9; `--icon-only` / `--text-only` / `--icon-and-text` are dropped
entirely. `--tertiary` is the one variant class present in both versions, because v8.41 adds it
alongside `--vue-tertiary` for every tertiary flavour — which is why `MessageHTMLBody.vue` could be
moved now and `Outbox.vue` could not.

The datepicker sites need real work, not a rename: `@vuepic/vue-datepicker` has a different DOM
shape, not just a different prefix. All three set width or popup positioning on the wrapper, so a
`NcDateTimePicker` visual check belongs in the Wave B QA pass.

## 3. Fixed in this PR

Ten rules across five files were removed or rewritten. Every one is verified inert against 8.41 —
either the class is not in the shipped library at all, or the change does not alter which elements
match. Four of them target a class name the library does not emit today, so they are dead already
and never appeared in the 152: the audit turned them up as a side effect.

| Site | Rule | Why it was safe to touch |
| --- | --- | --- |
| `SearchMessages.vue` | `.multiselect-search-tags .multiselect__tags .multiselect__tags-wrap` | `.multiselect__*` is `vue-multiselect`; `NcSelect` has never emitted it. Dead since `NcMultiselect` was retired. |
| `SearchMessages.vue` | `.checkbox-radio-switch__label` | 8.41 emits `__content` / `__text` / `__input` / `__icon`, no `__label`. |
| `SearchMessages.vue` | `.mx-datepicker` | The component uses `NcDateTimePickerNative` (a native `<input type="date">`), so no `.mx-*` element exists here. |
| `SearchMessages.vue` ×2 | `.button-vue.search-messages--filter.button-vue--icon-only` → `…--filter` | The modifier only padded specificity. The library's competing rule sets `width` with `!important`, which wins either way; nothing else in the block overlaps. Verified: computed geometry and every declared property are byte-identical before and after. |
| `Thread.vue` | `.user-bubble__title { cursor: pointer }` | 8.41 emits `.user-bubble__name`. |
| `RecipientBubble.vue` | `.user-bubble__title { max-width: 30vw }` | Same — and doubly inert, since the block is `scoped` without `:deep()`. |
| `MailboxThread.vue` | `.information-icon` (2 rules) | The component's `IconInfo` renders `.information-outline-icon`; `.information-icon` belongs to `NcNoteCard`, which this component does not use. |
| `MessageHTMLBody.vue` | `.button-vue--vue-tertiary` → `.button-vue--tertiary` | Both classes sit on the same element in 8.41, at equal specificity. |

Three of these carried a visible intent that the wrong class name silenced — the pointer cursor on a
user bubble, the recipient-bubble width cap, the dimmed info icon. They are removed rather than
repointed at the current class name, because reviving a rule that has not applied for several
releases is a visual change and belongs in its own PR.

## 4. Stable

55 class families over 98 sites are unchanged between 8.41 and 9.11 and need no action:

- **Buttons and actions** — `.button-vue`, `.button-vue__icon`, `.button-vue__text`,
  `.action-item`, `.action-item--single`, `.action-item__menutoggle`, `.action-button`,
  `.action-button__icon`, `.action-button__name`, `.action-button__text`
- **App shell** — `.app-content-list`, `.app-content-details`, `.app-content-wrapper` and its
  `--mobile` / `--no-split` / `--show-details` modifiers, `.app-details-toggle`,
  `.app-navigation-entry-link`, `.app-settings-section`
- **Inputs and list items** — `.input-field`, `.checkbox-radio-switch`,
  `.checkbox-radio-switch__content`, `.list-item-content__details`,
  `.list-item-content__subname`, `.list-item-details__details`, `.select__label`
- **Overlays** — `.modal-container`, `.modal-wrapper`, `.modal-wrapper--normal`, `.dialog__content`,
  `.empty-content__icon`, `.counter-bubble__counter`, `.avatardiv--unknown`

Three of these deserve a note, because they were expected to break and do not:

- **`.v-select` and `.vs__*`** (17 sites, mostly `Composer.vue`) are safe. `@nextcloud/vue-select`
  4.1 keeps every class name 3.26 uses; the only addition is the `vs__fade-enter-from` /
  `-leave-to` pair that Vue 3 renames, and Mail targets none of those.
- **`.splitpanes__pane-details`** is safe, including the `querySelector` in
  `ThreadSummary.vue:86`. The class is added by `NcAppContent`, not by `splitpanes`, so the
  2.x → 4.x bump does not reach it.
- **`.v-popper__inner`** and **`.v-popper__popper--shown`** are safe. `floating-vue` 5.2.2 keeps
  both names.

## 5. Library class names on Mail's own markup

16 families over 43 sites where Mail puts a `@nextcloud/vue` class on an element it renders itself.
These cannot break — Mail owns both the markup and the rule — but they stop matching the library's
own styling, so they drift.

- `EnvelopeSkeleton.vue` reimplements `NcListItem`'s markup for list performance and sets
  `.list-item`, `.list-item__wrapper`, `.list-item-content*`, `.list-item--compact`,
  `.list-item--multiline`, `.list-item__actions`. Two of those (`--multiline`, `__actions`) already
  do not exist in 8.41, so the skeleton has been drifting from the real component for a while. v9
  renames more of them (`.list-item-content__actions`, `.list-item--one-line`), so the skeleton and
  the live envelope will need a side-by-side check in Wave B.
- `TagItem.vue` borrows `.app-navigation-entry-bullet` and `.app-navigation-entry-bullet-wrapper`.
  Neither is in 8.41 either — v9's equivalent is `.app-navigation-entry__icon-bullet`. The tag
  colour bullet is styled entirely by Mail, so it renders correctly; only the intent of matching the
  navigation styling is lost.
- `Envelope.vue:1055` and `Composer.vue:1156` reach for `.list-item-content` and `.vs__search`
  through `querySelector`. Both class names survive v9, and both call sites already handle a `null`
  result, so they need nothing.

`EnvelopePrimaryActions.vue`, `EnvelopeSingleClickActions.vue` and `EnvelopeSkeleton.vue` also use
names shaped like the library's — `.list-item-content__actions--primary`, `.action--primary`,
`.list-item-content__quick-actions`, `.list-item-content__inner__details*` — that the library has
never emitted. They are Mail's own class names on Mail's own markup and are unaffected by v9;
they are listed here only so a future grep does not mistake them for library reaches.

## 6. Reaches owned by other Wave A items

Not counted above; they disappear with the component they target.

| Class family | Sites | Item |
| --- | --- | --- |
| `.vue-treeselect__*`, `.vue-treeselect--*` | `MailboxInlinePicker.vue` (11 rules), `mailFilter/ActionFileinto.vue:57,61` | A14 |
| `.tabs-component-*` | `AccountForm.vue:761,765,772,781,785,791,792` | A15 |

`vue-dndrop` in `quickActions/Settings.vue` (A16) styles no library internals — its CSS is all
Mail's own class names.

## 7. Not class renames

Two v9 changes will move pixels without any selector going stale, so they will not show up in a
grep:

- **`box-sizing: border-box` becomes the default** for `NcDialog` and popovers. Mail's six
  `:deep(.modal-container)` rules and the `.dialog__content` rule set explicit padding and width on
  those elements; the same numbers will produce different boxes.
- **`@linusborg/vue-simple-portal` is replaced by Vue 3's `<Teleport>`.** The class names on the
  modal wrapper survive, but the element's position in the DOM tree changes, which matters for the
  descendant selectors in `TaskModal.vue:252` and `EventModal.vue:346`
  (`:deep(.modal-wrapper .modal-container)`).
