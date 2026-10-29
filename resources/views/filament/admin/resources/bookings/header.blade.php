<div class="bms-bookings-header">
    <x-filament-panels::header
        :heading="$this->getHeading()"
        :subheading="$this->getSubheading()"
        :breadcrumbs="filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : []"
    />

    <section class="bms-bookings-intro" aria-label="Your appointment desk">
        <p class="bms-eyebrow">Your appointment desk</p>
        <h2>Good days start with a full calendar.</h2>
        <p class="bms-bookings-intro-description">Keep track of every appointment, welcome your clients, and give each booking the attention it deserves.</p>
    </section>

    <div class="bms-bookings-header-actions">
        <x-filament::actions :actions="$this->getCachedHeaderActions()" />
    </div>
</div>
