# Clean luxury homepage redesign

The user selected a clean luxury salon concept. The native Blade homepage now uses ivory surfaces, deep green typography/actions, muted gold accents and serif editorial headings. A split hero pairs the discovery message with the existing local beauty film and configured image-cycle photographs. Failed photographs are hidden without removing Alpine-managed elements; the local film remains behind them. A pause control and reduced-motion handling cover the film, and photo rotation is gated by its playing state.

A native GET discovery form routes salon/location and department choices to the existing validated salon directory. The live catalogue and its filters/pagination remain below the introduction. New sections explain the actual request-and-confirm booking process and invite salon owners to register. Copy does not imply immediate booking confirmation, verified providers or invented customer counts/reviews.

Homepage CSS variables and header/catalogue/footer refinements are scoped to the home route. Other public pages, authentication and dashboards retain their existing styling and behavior. Existing Blade URL validation and escaping remain in place. No React, new package, generated photograph, data migration or external message was added. Existing deleted image files remain untouched.

Validation:

- Native Vite build passed; final public-page regressions passed (29 tests, 137 assertions).
- Real cached synthetic SQLite HTTP/Chrome runner passed 94 HTTP checks and 53 browser checks, zero uncaught JavaScript errors, 23 screenshots. New checks cover the actual film control, discovery form submission and 320px overflow alongside existing 390px navigation/theme, booking and customer/owner/admin workflows.
- Evidence: `.local/migration-baseline/auth-smoke-20261014_172458_43501e/`. Desktop/mobile public previews are `desktop-home.png` and `mobile-home.png`. The final small-label gold was deepened to approximately 4.77:1 against the ivory background; screenshot colors may precede that final contrast adjustment. Rotation was subsequently gated by playing state and final Blade regressions rerun.
- Initial imported-copy browser run exposed Alpine scope errors when failed images removed themselves; that behavior was corrected. A subsequent MySQL rehearsal stopped on its source-change guard; no source migration was requested. Final cached workflow verification used completely synthetic SQLite records, so this redesign does not claim a new passing MySQL source-integrity rehearsal.
- No imported or production database was migrated or seeded, no deployment occurred, and no source environment settings changed. Rebuild/review staging handoff evidence for this new source checkpoint before release; the previous private handoff report refers to the older checkpoint.

Checkpoint: `homepage-clean-luxury`. Private full-history rollback bundle: `.local/migration-baseline/homepage-clean-luxury.bundle`. Revert the homepage views/styles and browser-verification changes without restoring database records. Real host/integration and original-photo completeness gates remain open.
