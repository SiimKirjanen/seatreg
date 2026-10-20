# SeatReg

WordPress plugin for creating and managing online registrations with a custom seat/spot layout.

Conventions for the Playwright suite are in `tests/e2e/CLAUDE.md` — read them before adding or
changing a test.

## Registration page

The public registration page is `registration/index.php` and `registration/js/registration.js`.
Its data reaches the JS as globals from the inline script in `php/enqueue_public.php`, and its
translated strings come from `php/seatreg_strings.php`, already escaped with `esc_html__()`.

## Escaping

- Layout values are validated when saved, by `SeatregDataValidation::layoutDataIsCorrect`. The
  registration page relies on that.
- In JS, put data into the page with `.text()`, `.attr()`, `.val()` or `escapeHtml()`, never by
  concatenating it into an HTML string.

## Seat and room words

Admins can rename "seat" and "room". User-facing text takes the words from
`SeatregTerminologyService` in PHP and `seatregSeatNouns()` / `seatregRoomNouns()` in JS, never
hardcoded.

## Versions

After changing a JS or CSS file, bump its version where it is enqueued, in
`php/enqueue_admin.php` or `php/enqueue_public.php`.

## Trying things out

To test or verify a change, use the wp-env site in Docker (see README.md) and drive it with the
Playwright MCP browser tools.

## Comments

The code is expected to explain itself. Write a comment only where reading the code cannot tell
you what you need to know, and keep it to a line or two.

Style files take no comments at all. Where a rule needs explaining, name the selector or the
custom property so it explains itself.
