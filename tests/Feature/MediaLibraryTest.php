<?php

use App\Helpers\ImageManager;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\MediaUsageService;
use App\Services\Admin\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

/**
 * Local uploads land in public/uploads; clean up whatever a test wrote so the
 * real library folder is never polluted by the suite.
 */
function forgetLocalMedia(MediaAsset $asset): void
{
    $path = public_path('uploads/'.$asset->folder.'/'.$asset->filename);

    if (File::exists($path)) {
        File::delete($path);
    }
}

// -- Metadata -----------------------------------------------------------------------

it('stores editable metadata with an upload', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.media.store'), [
        'folder' => 'media',
        'title' => 'Summer hero',
        'alt_text' => 'Model wearing the summer tee',
        'tags' => 'summer, hero , hero',
        'files' => [UploadedFile::fake()->image('hero.jpg', 40, 30)],
    ]);

    $response->assertRedirect();

    $asset = MediaAsset::firstOrFail();

    expect($asset->title)->toBe('Summer hero')
        ->and($asset->alt_text)->toBe('Model wearing the summer tee')
        ->and($asset->tags)->toBe(['summer', 'hero'])
        ->and($asset->checksum)->not->toBeNull()
        ->and($asset->disk)->toBe('local');

    forgetLocalMedia($asset);
});

it('updates alt text, title, tags and folder after upload', function () {
    $asset = MediaAsset::create([
        'folder' => 'media', 'disk' => 'local', 'filename' => 'x.jpg', 'size' => 10,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.media.update', $asset), [
            'title' => 'Lookbook cover',
            'alt_text' => 'Autumn lookbook cover',
            'tags' => 'autumn,lookbook',
            'folder' => 'banners',
        ])
        ->assertRedirect();

    $asset->refresh();

    expect($asset->title)->toBe('Lookbook cover')
        ->and($asset->alt_text)->toBe('Autumn lookbook cover')
        ->and($asset->tags)->toBe(['autumn', 'lookbook'])
        ->and($asset->folder)->toBe('banners');
});

// -- Duplicates ---------------------------------------------------------------------

it('skips a duplicate upload in the same folder instead of storing it twice', function () {
    $file = UploadedFile::fake()->image('dup.jpg', 40, 30);
    $copy = clone $file;

    $this->actingAs($this->admin)->post(route('admin.media.store'), [
        'folder' => 'media',
        'files' => [$file, $copy],
    ])->assertRedirect();

    expect(MediaAsset::count())->toBe(1);

    forgetLocalMedia(MediaAsset::firstOrFail());
});

// -- Usage --------------------------------------------------------------------------

it('counts references in one batched pass and blocks deleting used media', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);

    $used = MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'used.jpg', 'size' => 10]);
    $free = MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'free.jpg', 'size' => 10]);

    Product::create([
        'name' => ['en' => 'Tee'], 'slug' => 'tee', 'category_id' => $category->id,
        'price' => 10, 'thumbnail' => 'used.jpg', 'status' => 1,
    ]);

    $map = app(MediaUsageService::class)->summaryMap(collect([$used, $free]));

    expect($map[$used->id]['count'])->toBe(1)
        ->and($map[$free->id]['count'])->toBe(0);

    $this->actingAs($this->admin)
        ->delete(route('admin.media.destroy', $used))
        ->assertSessionHasErrors('error');

    expect(MediaAsset::whereKey($used->id)->exists())->toBeTrue();
});

it('caches usage counts onto the rows when the index renders', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
    $used = MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'cached.jpg', 'size' => 10]);

    Product::create([
        'name' => ['en' => 'Tee'], 'slug' => 'tee', 'category_id' => $category->id,
        'price' => 10, 'thumbnail' => 'cached.jpg', 'status' => 1,
    ]);

    $this->actingAs($this->admin)->get(route('admin.media.index'))->assertOk();

    expect($used->refresh()->usage_count)->toBe(1)
        ->and($used->usage_synced_at)->not->toBeNull();
});

// -- Bulk actions -------------------------------------------------------------------

it('bulk deletes only the unused assets', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);

    $used = MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'keep.jpg', 'size' => 10]);
    $free = MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'drop.jpg', 'size' => 10]);

    Product::create([
        'name' => ['en' => 'Tee'], 'slug' => 'tee', 'category_id' => $category->id,
        'price' => 10, 'thumbnail' => 'keep.jpg', 'status' => 1,
    ]);

    $this->actingAs($this->admin)->post(route('admin.media.bulk'), [
        'action' => 'delete',
        'ids' => [$used->id, $free->id],
    ])->assertRedirect();

    expect(MediaAsset::whereKey($used->id)->exists())->toBeTrue()
        ->and(MediaAsset::whereKey($free->id)->exists())->toBeFalse();
});

it('bulk moves assets between library folders', function () {
    $one = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'a.jpg', 'size' => 10]);
    $two = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'b.jpg', 'size' => 10]);

    $this->actingAs($this->admin)->post(route('admin.media.bulk'), [
        'action' => 'move',
        'ids' => [$one->id, $two->id],
        'folder' => 'banners',
    ])->assertRedirect();

    expect($one->refresh()->folder)->toBe('banners')
        ->and($two->refresh()->folder)->toBe('banners');
});

it('rejects a move without a target folder', function () {
    $asset = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'c.jpg', 'size' => 10]);

    $this->actingAs($this->admin)->post(route('admin.media.bulk'), [
        'action' => 'move',
        'ids' => [$asset->id],
    ])->assertSessionHasErrors('folder');

    expect($asset->refresh()->folder)->toBe('media');
});

// -- Filters ------------------------------------------------------------------------

it('filters the library down to unused assets', function () {
    MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'used-one.jpg', 'size' => 10, 'usage_count' => 3, 'usage_synced_at' => now()]);
    MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'unused-one.jpg', 'size' => 10, 'usage_count' => 0, 'usage_synced_at' => now()]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.media.index', ['usage' => 'unused']))
        ->assertOk();

    // Assert on the queried page, not the HTML: the notification bell also
    // renders media filenames, which would mask a broken filter.
    expect($response->viewData('assets')->pluck('filename')->all())->toBe(['unused-one.jpg']);
});

// -- Remote storage (Cloudflare R2) -------------------------------------------------

it('writes uploads to the configured remote disk and stores a public url', function () {
    Storage::fake('r2');
    config()->set('media.disk', 'r2');
    config()->set('media.prefix', 'media');

    $this->actingAs($this->admin)->post(route('admin.media.store'), [
        'folder' => 'products',
        'files' => [UploadedFile::fake()->image('remote.jpg', 40, 30)],
    ])->assertRedirect();

    $asset = MediaAsset::firstOrFail();

    expect($asset->disk)->toBe('r2')
        ->and($asset->object_key)->toStartWith('media/products/')
        ->and($asset->filename)->toStartWith('http')
        ->and($asset->isRemote())->toBeTrue()
        // The stored filename is an absolute URL, so every consumer resolves it untouched.
        ->and($asset->url)->toBe($asset->filename);

    Storage::disk('r2')->assertExists($asset->object_key);
});

it('deletes the remote object when a remote asset is removed', function () {
    Storage::fake('r2');
    config()->set('media.disk', 'r2');

    $this->actingAs($this->admin)->post(route('admin.media.store'), [
        'folder' => 'products',
        'files' => [UploadedFile::fake()->image('gone.jpg', 40, 30)],
    ])->assertRedirect();

    $asset = MediaAsset::firstOrFail();
    $key = $asset->object_key;

    $this->actingAs($this->admin)
        ->delete(route('admin.media.destroy', $asset))
        ->assertRedirect();

    Storage::disk('r2')->assertMissing($key);
    expect(MediaAsset::count())->toBe(0);
});

// -- One image, used anywhere -------------------------------------------------------

it('offers the whole library to a picker no matter which folder it asks for', function () {
    MediaAsset::create(['folder' => 'products', 'disk' => 'local', 'filename' => 'p.jpg', 'size' => 10]);
    MediaAsset::create(['folder' => 'banners', 'disk' => 'local', 'filename' => 'b.jpg', 'size' => 10]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.media.picker', ['folder' => 'brands']))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('filename')->sort()->values()->all())
        ->toBe(['b.jpg', 'p.jpg']);
});

it('picks a library image into any feature and resolves it from that feature folder', function () {
    // Filed under "media", picked as a BRAND image — the classic cross-folder case.
    $asset = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'shared.jpg', 'size' => 10]);

    expect($asset->reference)->toBe('uploads/media/shared.jpg');

    // The consuming field resolves it without knowing the library folder.
    expect(ImageManager::path($asset->reference, 'brands'))->toBe('uploads/media/shared.jpg')
        ->and(ImageManager::url($asset->reference, 'banners'))->toBe(asset('uploads/media/shared.jpg'))
        ->and(ImageManager::url($asset->reference, 'products'))->toBe(asset('uploads/media/shared.jpg'));
});

it('keeps a shared library file when a consumer clears its own field', function () {
    File::ensureDirectoryExists(public_path('uploads/media'));
    File::put(public_path('uploads/media/keepme.jpg'), 'x');

    // A consumer replacing/clearing its image must not delete library-owned files.
    ImageManager::delete('uploads/media/keepme.jpg', 'brands');

    expect(File::exists(public_path('uploads/media/keepme.jpg')))->toBeTrue();

    File::delete(public_path('uploads/media/keepme.jpg'));
});

it('counts a cross-folder reference as usage so the file cannot be deleted', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
    $asset = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'cross.jpg', 'size' => 10]);

    // Product stores the portable reference, not a bare "products" filename.
    Product::create([
        'name' => ['en' => 'Tee'], 'slug' => 'tee', 'category_id' => $category->id,
        'price' => 10, 'thumbnail' => $asset->reference, 'status' => 1,
    ]);

    $map = app(MediaUsageService::class)->summaryMap(collect([$asset]));

    expect($map[$asset->id]['count'])->toBe(1);

    $this->actingAs($this->admin)
        ->delete(route('admin.media.destroy', $asset))
        ->assertSessionHasErrors('error');
});

it('uploads without choosing a folder', function () {
    $this->actingAs($this->admin)->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->image('nofolder.jpg', 40, 30)],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $asset = MediaAsset::firstOrFail();

    expect($asset->folder)->toBe('media');

    forgetLocalMedia($asset);
});

it('saves a library image picked as the site logo and favicon', function () {
    // Filed under "media" — the settings fields declare folder "settings",
    // which used to make the pick unresolvable and silently save nothing.
    $logo = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'logo.png', 'size' => 10]);
    $icon = MediaAsset::create(['folder' => 'media', 'disk' => 'local', 'filename' => 'icon.png', 'size' => 10]);

    app(SettingService::class)->save([
        'site_logo_media' => $logo->reference,
        'site_favicon_media' => $icon->reference,
    ]);

    expect(Setting::get('site_logo'))->toBe('uploads/media/logo.png')
        ->and(Setting::get('site_favicon'))->toBe('uploads/media/icon.png');

    // …and the sidebar/branding helpers resolve it to a usable URL.
    $branding = app(SettingService::class);

    expect($branding->logoUrl())->toBe(asset('uploads/media/logo.png'))
        ->and($branding->faviconUrl())->toBe(asset('uploads/media/icon.png'));
});
