# Copilot Instructions for bookmystyle

## Project Overview
- **Framework:** Laravel (PHP 8.5+), Livewire, Filament
- **Domain:** Salon booking system
- **Key Models:** `User`, `Salon`, `Service`, `Booking`, `Category`, `Department`
- **Livewire Components:** Used for interactive forms and frontend logic (e.g., `BookingForm`, `SearchBar`)

## Architecture & Data Flow
- **Bookings** are created via Livewire (`app/Livewire/BookingForm.php`), which loads available `Service` models for a given `Salon`.
- **Services** are filtered by `salon_id` and passed to the view as `$services`.
- **Views** are in `resources/views/livewire/` and expect variables (e.g., `$services`) to be set in the Livewire component.
- **Models** are in `app/Models/` and follow standard Eloquent conventions.
- **Enums** for roles/permissions are in `app/Enums/` and `app/PermissionsEnum.php`, `app/RolesEnum.php`.

## Developer Workflows
- **Run the app:**
  - `php artisan serve` (local dev server)
- **Run tests:**
  - `vendor\bin\pest` or `vendor\bin\phpunit` (Windows)
- **Migrate DB:**
  - `php artisan migrate`
- **Seed DB:**
  - `php artisan db:seed`
- **Install dependencies:**
  - `composer install` (PHP), `npm install` (JS)
- **Build assets:**
  - `npm run build` (uses Vite)

## Project-Specific Conventions
- **Livewire:** Always initialize and pass required data (e.g., `$services`) in the component, not the view.
- **Validation:** Use `$rules` property in Livewire components for form validation.
- **Session Flash:** Use `session()->flash()` for user feedback after actions.
- **Frontend:** Blade templates use Bootstrap classes and expect Livewire variables.
- **Testing:** Pest is available for tests in `tests/Feature` and `tests/Unit`.

## Integration Points
- **Filament:** Admin panel logic is under `app/Filament/Admin/`.
- **Middleware:** HTTP middleware in `app/Http/Middleware/`.
- **Providers:** Service providers in `app/Providers/`.
- **Database:** SQLite by default (`database/database.sqlite`).

## Examples
- **BookingForm loads services:**
  ```php
  // app/Livewire/BookingForm.php
  public function mount($salon_id) {
      $this->services = Service::where('salon_id', $salon_id)->get();
  }
  ```
- **Blade expects $services:**
  ```blade
  @foreach($services as $service)
      <option value="{{ $service->id }}">{{ $service->name }}</option>
  @endforeach
  ```

## Key Files & Directories
- `app/Livewire/` — Livewire components
- `app/Models/` — Eloquent models
- `resources/views/livewire/` — Blade views for Livewire
- `database/migrations/` — DB schema
- `tests/` — Pest/PHPUnit tests

---

**For AI agents:**
- Always check Livewire components for variables expected in Blade views.
- Follow Laravel and Livewire conventions unless project-specific patterns are documented above.
- Use Windows paths and commands for local development.
