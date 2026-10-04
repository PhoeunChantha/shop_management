<?php

use App\Models\User;
use App\Services\Admin\EnvService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Point EnvService at a scratch .env so the real one is never touched.
    $this->envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($this->envPath, "APP_NAME=Shop\nMEDIA_DISK=local\n");
    app()->loadEnvironmentFrom(basename($this->envPath));
    app()->useEnvironmentPath(dirname($this->envPath));
});

afterEach(function () {
    @unlink($this->envPath);
});

function r2Payload(array $overrides = []): array
{
    return array_merge([
        'media_disk' => 'r2',
        'r2_bucket' => 't-shirt-shop',
        'r2_endpoint' => 'https://abc123.r2.cloudflarestorage.com',
        'r2_url' => 'https://media.example.com',
        'r2_access_key_id' => 'key-id',
        'r2_secret_access_key' => 'secret-value',
    ], $overrides);
}

it('shows the Media Storage tab with a test button', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.index', ['tab' => 'storage']))
        ->assertOk()
        ->assertSee('Media Storage')
        ->assertSee('r2_access_key_id', false)
        ->assertSee('settings\/storage-test', false)
        ->assertDontSee('secret-value');
});

it('saves R2 credentials to .env and keeps the secret when left blank', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), r2Payload())
        ->assertRedirect(route('admin.settings.index'));

    $env = app(EnvService::class);
    expect($env->get('MEDIA_DISK'))->toBe('r2')
        ->and($env->get('R2_BUCKET'))->toBe('t-shirt-shop')
        ->and($env->get('R2_SECRET_ACCESS_KEY'))->toBe('secret-value');

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), r2Payload(['r2_secret_access_key' => '']))
        ->assertRedirect(route('admin.settings.index'));

    expect($env->get('R2_SECRET_ACCESS_KEY'))->toBe('secret-value');
});

it('accepts a bare domain for the public URL and normalizes it', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), r2Payload(['r2_url' => 'media.example.com/']))
        ->assertRedirect(route('admin.settings.index'));

    expect(app(EnvService::class)->get('R2_URL'))->toBe('https://media.example.com');
});

it('strips the bucket path Cloudflare shows on the S3 endpoint', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), r2Payload(['r2_endpoint' => 'https://abc123.r2.cloudflarestorage.com/t-shop']))
        ->assertRedirect(route('admin.settings.index'));

    expect(app(EnvService::class)->get('R2_ENDPOINT'))->toBe('https://abc123.r2.cloudflarestorage.com');
});

it('requires the R2 fields and a secret when switching to R2', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.settings.index'))
        ->put(route('admin.settings.update'), ['media_disk' => 'r2'])
        ->assertSessionHasErrors(['r2_bucket', 'r2_endpoint', 'r2_url', 'r2_access_key_id', 'r2_secret_access_key']);

    expect(app(EnvService::class)->get('MEDIA_DISK'))->toBe('local');
});

it('does not blank .env credentials on a partial save', function () {
    app(EnvService::class)->set(['R2_BUCKET' => 'kept']);

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), ['site_name' => 'Shop'])
        ->assertRedirect(route('admin.settings.index'));

    expect(app(EnvService::class)->get('R2_BUCKET'))->toBe('kept');
});

it('reports missing fields from the connection test', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.storage-test'))
        ->assertStatus(422)
        ->assertJson(['ok' => false]);
});

it('passes the connection test when write, public read and delete work', function () {
    app(EnvService::class)->set([
        'R2_BUCKET' => 't-shirt-shop',
        'R2_ENDPOINT' => 'https://abc123.r2.cloudflarestorage.com',
        'R2_URL' => 'https://media.example.com',
        'R2_ACCESS_KEY_ID' => 'key-id',
        'R2_SECRET_ACCESS_KEY' => 'secret-value',
    ]);

    $fake = Storage::fake('r2-probe');
    Storage::shouldReceive('build')->once()->andReturn($fake);
    Http::fake(['media.example.com/*' => Http::response('ok')]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.storage-test'))
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($fake->allFiles())->toBe([]);
});

it('flags a bucket that is not publicly readable', function () {
    app(EnvService::class)->set([
        'R2_BUCKET' => 't-shirt-shop',
        'R2_ENDPOINT' => 'https://abc123.r2.cloudflarestorage.com',
        'R2_URL' => 'https://media.example.com',
        'R2_ACCESS_KEY_ID' => 'key-id',
        'R2_SECRET_ACCESS_KEY' => 'secret-value',
    ]);

    $fake = Storage::fake('r2-probe');
    Storage::shouldReceive('build')->once()->andReturn($fake);
    Http::fake(['media.example.com/*' => Http::response('', 404)]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.settings.storage-test'))
        ->assertStatus(422)
        ->assertJsonFragment(['ok' => false]);
});

it('forbids the connection test without settings permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('admin.settings.storage-test'))
        ->assertForbidden();
});
