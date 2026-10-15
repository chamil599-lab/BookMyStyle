# Salon collection cards

Salon cards now follow the deals collection's editorial design: larger landscape photography, a review badge, serif salon name, location, a short description, compact service/team facts, opening hours, starting price and an Explore & book action. Hover effects respect reduced-motion preferences. Missing hours and reviews use honest fallback text.

Published service counts and minimum published prices are selected as aggregate subqueries through `PublicCatalogue::cardSalons()`, shared by directory and saved salons. This avoids per-card queries. Starting prices render only when the salon enables published pricing. Existing owner scoping, public visibility, media URL handling and escaped content are retained.

Validation: production Vite build; 46 public/customer feature tests, 287 assertions, including hidden prices on directory cards; 20 browser checks confirming instant filters and image fallbacks across all directory modes; three focused preview checks for published service details, 320px bounds and zero JavaScript errors. Desktop and dark/mobile screenshots were reviewed in the private synthetic fixture.
