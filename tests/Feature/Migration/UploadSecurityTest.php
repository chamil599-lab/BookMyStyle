<?php

use App\Filament\Admin\Resources\ImageCycles\Pages\CreateImageCycle;
use App\Http\Requests\Api\StoreImageCycleRequest;
use App\Models\ImageCycle;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('accepts a raster upload with a generated storage name', function () {
    $this->actingAs($this->admin)->postJson('/api/image-cycles', [
        'file' => UploadedFile::fake()->image('portrait.png', 100, 100),
    ])->assertCreated();
    $path = ImageCycle::firstOrFail()->upload_path;
    expect($path)->toStartWith('image_cycles/')->not->toContain('portrait');
    Storage::disk('public')->assertExists($path);
});

it('rejects active content and oversized image dimensions without storing files', function () {
    foreach ([UploadedFile::fake()->createWithContent('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
        UploadedFile::fake()->createWithContent('attack.png', '<?php echo 1;'),
        UploadedFile::fake()->image('wide.png', 6001, 1)] as $file) {
        $this->actingAs($this->admin)->postJson('/api/image-cycles', ['file' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
    }
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('image_cycles', 0);
});

it('rejects unsafe image URL protocols and allows HTTPS', function () {
    foreach (['ftp://example.test/image.png', 'javascript:alert(1)', 'data:image/svg+xml,test'] as $url) {
        $this->actingAs($this->admin)->postJson('/api/image-cycles', ['src_url' => $url])
            ->assertUnprocessable()->assertJsonValidationErrors('src_url');
    }
    $this->actingAs($this->admin)->postJson('/api/image-cycles', ['src_url' => 'https://example.test/image.png'])
        ->assertCreated();
});

it('rejects oversized raster files without storing them', function () {
    $this->actingAs($this->admin)->postJson('/api/image-cycles', [
        'file' => UploadedFile::fake()->image('large.png')->size(10241),
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('image_cycles', 0);
});

it('rejects executable extensions even when the content is a valid raster', function () {
    $raster = UploadedFile::fake()->image('portrait.png', 10, 10);
    $this->actingAs($this->admin)->postJson('/api/image-cycles', [
        'file' => UploadedFile::fake()->createWithContent('portrait.php', file_get_contents($raster->getRealPath())),
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('image_cycles', 0);
});

it('rejects non-admin uploads without persisting files', function ($role) {
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create(['is_approved' => true]);
    $user->assignRole($role);
    $this->actingAs($user)->postJson('/api/image-cycles', [
        'file' => UploadedFile::fake()->image('denied.png'),
    ])->assertForbidden();
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertDatabaseCount('image_cycles', 0);
})->with(['customer', 'owner']);

it('rejects SVG content through the Filament upload form', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(CreateImageCycle::class)->fillForm(['title' => 'Rejected SVG', 'order' => 0, 'status' => 1,
        'upload_path' => [UploadedFile::fake()->createWithContent('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')]])
        ->call('create')->assertHasFormErrors();
    $this->assertDatabaseCount('image_cycles', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects a cached admin whose role has been revoked', function () {
    $this->admin->load('roles');
    User::findOrFail($this->admin->id)->removeRole('admin');
    $request = StoreImageCycleRequest::create('/api/image-cycles', 'POST');
    $request->setUserResolver(fn () => $this->admin);
    expect($request->authorize())->toBeFalse();
});

it('stores a raster image through the native Filament upload form', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(CreateImageCycle::class)
        ->fillForm(['title' => 'Native image', 'order' => 0, 'status' => 1,
            'upload_path' => [UploadedFile::fake()->image('native.png', 100, 100)]])
        ->call('create')->assertHasNoFormErrors();
    $image = ImageCycle::where('title', 'Native image')->firstOrFail();
    expect($image->upload_path)->toStartWith('image_cycles/');
    Storage::disk('public')->assertExists($image->upload_path);
});
