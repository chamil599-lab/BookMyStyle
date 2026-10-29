# Salon map links

For the combined feature history, local preview status, commits and hosted upgrade checklist, see [Salon closed dates and map links](salon-features-2026-10-29.md).

In Salon Management > Salons > Edit > Basic Information, paste the salon's shared map link into **Map URL** and save. The field is optional and accepts HTTP/HTTPS URLs up to 2048 characters, including short shared links.

The public salon detail page displays a map icon and **View on map** below the address. Both this link and **Get directions** open the saved destination in a new tab. If the field is empty, directions use a Google Maps search for the existing address. If neither a valid destination nor an address exists, map links are hidden. The public salon API includes the resolved destination in `map_url`; Android client changes are separate from this website update.

The field uses the existing salon ownership permissions. Invalid URL schemes are rejected when saving and are never rendered as public links.

## Hosted upgrade

Review and release this feature through the repository's normal Git workflow. Back up the database, preserve the production environment/key and uploads, and review pending migrations before applying the approved set. This feature adds the nullable `salons.map_url` column through `2026_10_29_000002_add_map_url_to_salons_table`. No dependency or environment changes are required. Include the normal asset build and refresh compiled views. Verify owner save/clear behavior and the public map link after deployment.

Source rollback can retain the nullable column and its saved data. Reversing the migration removes all saved map URLs, so do not drop the column as a routine rollback.

The feature is available on the local Herd site; it has not been pushed or deployed to production.

## Local verification

On 29 October 2026, the full Pest suite passed with 492 tests and 2,881 assertions; one Windows directory-symlink test was skipped. The production asset build and formatting check for this feature's PHP files passed. Repository-wide Pint reports existing formatting issues in PublicContentController.php, ServicePage.php and deployment/cpanel/backup.php; those unrelated edits were preserved. The local salon/53 page returned HTTP 200 and rendered the map link. The additive migration was applied only to the verified local Herd database.
