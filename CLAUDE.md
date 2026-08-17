# SeatReg

WordPress plugin for creating and managing online registrations with a custom seat/spot layout.

## E2E tests

Playwright tests live in `tests/e2e`. Each admin screen gets a folder under `tests/e2e/pages/`
holding its page object and its specs. See README.md for how to run them.

Keep the suite small — it has to cover every screen of the plugin eventually, so tests are
added sparingly:

- One test per user-visible behaviour, not per assertion. Two tests that walk the same flow
  and differ only in what they assert at the end are one test.
- Do not assert static markup that no code decides — headings, labels, button captions,
  `maxlength` and the like. They only break on copy changes. Asserting a value the plugin
  computes (a registration code, a generated URL, a shortcode) is worth it.
- A dialog with a confirm and a cancel branch is one test walking both, not two tests.
- Never assert list size or emptiness. Registrations are global and tests do not clean up
  (see `tests/e2e/utils/registrations.js`).
- Cover a screen from its own spec. Asserting an href is enough to prove a link works; the
  destination screen is the other spec's job.
- Shared setup, and any workaround for the plugin's markup or timing quirks, belongs in the
  page object — not repeated in specs.
