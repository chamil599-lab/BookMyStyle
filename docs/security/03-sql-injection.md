# 03 - SQL injection

Checked 16 October 2026. No exploitable SQL injection path was found in the reviewed application queries. No production code changes were required.

Reviewed authored queries across app and routes, including public directory search/sort/department/pagination, editorial category filters, account credential lookup, services and management analytics. Eloquent binds user values. The directory's raw LIKE expression binds its search value and interpolates only three hardcoded column names. The favorite subquery's raw selection is the constant `1`. Editorial column selection comes from a fixed route-mode choice. No authored direct PDO, unprepared SQL execution or request-derived raw SQL was found.

Directory sorting maps validated choices to fixed columns. Analytics validates period, numeric salon IDs and date ranges. Installed Filament table code resolves sorting through configured sortable visible columns and limits direction to asc/desc. Its default search helper wraps configured column identifiers and binds search patterns. These dashboard conclusions are source review, not dashboard injection penetration tests.

Added 12 regression cases covering boolean/UNION/statement payloads in all four directory modes, query SQL versus bindings, legitimate apostrophes, literal percent/underscore/escape characters, malformed sort/department/page/array filters, API department expressions and editorial category attack values. Attack searches return no unrelated rows; fixture row counts remain intact. Together with the existing public-page suite: **41 tests passed, 186 assertions**.

Validation used SQLite in memory with synthetic fixtures. The imported MySQL database was not modified. MySQL-specific behavior, deployed database account privileges and production-host penetration testing are outside this result. Least-privilege database configuration remains part of deployment review.

Reference: [OWASP SQL injection prevention guidance](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html). Next category: IDOR / broken access control.
