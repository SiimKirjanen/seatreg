# SeatReg e2e tests

Playwright tests live in `tests/e2e`. Each admin screen gets a folder under `tests/e2e/pages/`
holding its page object and its specs. Where a screen is split into tabs the specs follow that
split, one file per tab (`settings-general.spec.js`, `settings-payments.spec.js`), plus a
`-screen` spec for what belongs to the screen rather than to any one tab. The repo's README.md
says how to run them.

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

The site cannot send mail, so `tests/e2e/mu-plugins/mail-log.php` (mapped in by `.wp-env.json`)
captures `wp_mail()` instead of sending it. Read what the plugin emailed with
`tests/e2e/utils/mail.js`, always filtering by a `uniqueBookerEmail()` — the log is shared by
every worker. Capturing stops `wp_mail()` before PHPMailer is built, so it is off unless a run
asks for it: `auth.setup.js` turns it on and the `cleanup` project turns it back off, leaving
the site sending its mail the usual way for anyone developing against real SMTP. Set
`SEATREG_E2E_MAIL_LOG` in `.wp-env.json` to hold it on to read mail by hand.

Site state no SeatReg screen makes — a post to put a shortcode in, a user who is not an
administrator — comes from `tests/e2e/mu-plugins/fixtures.php` (mapped in the same way)
through `tests/e2e/utils/fixtures.js`. Nothing it makes is cleaned up either, so name it
uniquely.

A setting that belongs to the site rather than to a registration — the Companion app switch is
the only one so far — is raced by the four workers. Cover it in a `test.describe.serial` block
that puts the option back when it is done, as `pages/companion/companion.spec.js` does.
