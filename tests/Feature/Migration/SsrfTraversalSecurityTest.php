<?php

use App\Models\ImageCycle;
use App\Models\User;
use Illuminate\Filesystem\ServeFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('rejects non-HTTP source URL schemes without creating media', function ($url) {
    $this->actingAs($this->admin)->postJson('/api/image-cycles', ['src_url' => $url])->assertUnprocessable();
    expect(ImageCycle::count())->toBe(0)->and(Storage::disk('public')->allFiles())->toBe([]);
})->with(['file:///fixture-secret', 'php://filter/resource=fixture-secret', 'gopher://example.test/fixture', 'phar://fixture/archive']);

it('treats an external source URL as stored metadata rather than a Laravel HTTP fetch', function () {
    Http::preventStrayRequests();
    Http::fake();
    $this->actingAs($this->admin)->postJson('/api/image-cycles', ['src_url' => 'https://example.test/fixture.png'])->assertCreated();
    expect(ImageCycle::firstOrFail()->src_url)->toBe('https://example.test/fixture.png')
        ->and(Storage::disk('public')->allFiles())->toBe([]);
    Http::assertNothingSent();
});

it('ignores caller supplied storage directories and paths on upload', function () {
    $this->actingAs($this->admin)->postJson('/api/image-cycles', ['file' => UploadedFile::fake()->image('fixture.png'),
        'upload_path' => '../outside-fixture', 'directory' => '../outside-fixture', 'disk' => 'local'])->assertCreated();
    $path = ImageCycle::firstOrFail()->upload_path;
    expect($path)->toStartWith('image_cycles/')->not->toContain('..');
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('denies unsigned private storage downloads', function () {
    Storage::disk('local')->put('private-fixture.txt', 'synthetic private value');
    $handler = new ServeFile('local', ['visibility' => 'private'], true);
    try {
        $handler(Request::create('/storage/private-fixture.txt'), 'private-fixture.txt');
        $this->fail('Unsigned private download was allowed.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }
});

it('rejects traversal at the storage serving boundary', function ($path) {
    $handler = new ServeFile('local', ['visibility' => 'public'], true);
    try {
        $handler(Request::create('/storage/fixture'), $path);
        $this->fail('Traversal was allowed.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }
})->with(['../outside-fixture.txt', '..\\outside-fixture.txt', 'nested/../../outside-fixture.txt']);
