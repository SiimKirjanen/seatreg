# SeatReg

WordPress plugin for creating and managing online registrations with a custom seat/spot layout.

Conventions for the Playwright suite are in `tests/e2e/CLAUDE.md` — read them before adding or
changing a test.

Nothing may generate a booking PDF against the wp-env site: tFPDF caches an absolute font path
under `php/libs/tfpdf/` with whichever environment generated it, and the container and the host
share that folder.
