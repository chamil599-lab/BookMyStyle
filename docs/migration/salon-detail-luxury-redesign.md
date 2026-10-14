# Luxury salon detail page

The salon detail page now shares the homepage's ivory/deep-green/gold theme and black/charcoal dark surfaces. It adds an editorial cover/introduction, real treatment/review counts, section navigation, treatment cards, a desktop booking sidebar, gallery, contact details and a review summary. Mobile layouts keep the same anchors and native forms. Missing opening hours are described as unavailable rather than displaying invented hours; a salon with no reviews is not presented as having a zero-star customer rating.

Shared luxury CSS now targets the home and salon-detail body classes; other routes retain their existing styling. Salon names, descriptions, reviews and contact values remain Blade-escaped. Existing safe URL helpers, image fallbacks, price-visibility rules, Livewire booking, availability validation, favourites and review eligibility are retained. Booking remains a request subject to salon approval. No backend service/model, source database, dependency or environment setting changed.

Validation:

- Native Vite build passed. Final public-page suite: 29 passed, 137 assertions (4.77 seconds), including hidden-price and unsafe-content coverage.
- Cached synthetic SQLite verification: 94 HTTP checks and 56 Chrome checks passed, zero uncaught JavaScript errors, 26 screenshots. New checks cover salon sections, the computed black dark background and 320px overflow. Existing real browser booking, favourites/customer and owner/admin journeys still pass.
- Private evidence: `.local/migration-baseline/auth-smoke-20261014_173849_efab9f/`. Desktop, dark and full-page/mobile salon previews are stored there. A preview-only pass refreshed these captures after fixing Windows-written icon characters with HTML entities; it passed without JavaScript errors or booking submission.
- Initial tests caught the changed hidden-price wording; the established “Contact salon for pricing” message was restored. PHP view rendering, Node syntax and authored Git whitespace checks pass. Original image deletions remain untouched. No production deployment or imported-data write/migration occurred.

Checkpoint: `salon-detail-clean-luxury`. Private rollback bundle: `.local/migration-baseline/salon-detail-clean-luxury.bundle`. Revert the view/theme/browser-check changes without restoring database records. Actual salon-photo completeness and host/integration verification remain release gates.
