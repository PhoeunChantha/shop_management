<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

it('renders the About page from admin-managed content', function () {
    Page::create([
        'title' => 'About Us', 'slug' => 'about', 'page_key' => 'about',
        'content' => '<p>UNIQUE ABOUT BODY XYZ</p>', 'status' => true,
    ]);

    $this->get(route('frontend.pages.about'))
        ->assertOk()
        ->assertSee('UNIQUE ABOUT BODY XYZ', false);
});

it('renders Privacy and Terms from admin-managed content', function () {
    Page::create(['title' => 'Privacy', 'slug' => 'privacy', 'page_key' => 'privacy', 'content' => '<p>PRIVACY BODY QRS</p>', 'status' => true]);
    Page::create(['title' => 'Terms', 'slug' => 'terms', 'page_key' => 'terms', 'content' => '<p>TERMS BODY LMN</p>', 'status' => true]);

    $this->get(route('frontend.pages.privacy'))->assertOk()->assertSee('PRIVACY BODY QRS', false);
    $this->get(route('frontend.pages.terms'))->assertOk()->assertSee('TERMS BODY LMN', false);
});

it('resolves a system page by key even after its slug changes', function () {
    Page::create([
        'title' => 'Our Story', 'slug' => 'about-renamed-slug', 'page_key' => 'about',
        'content' => '<p>KEY RESOLVED BODY</p>', 'status' => true,
    ]);

    $this->get(route('frontend.pages.about'))
        ->assertOk()
        ->assertSee('KEY RESOLVED BODY', false);
});

it('does not render an unpublished system page (falls back to the designed page)', function () {
    Page::create([
        'title' => 'About', 'slug' => 'about', 'page_key' => 'about',
        'content' => '<p>HIDDEN DRAFT BODY</p>', 'status' => false,
    ]);

    $this->get(route('frontend.pages.about'))
        ->assertOk()
        ->assertDontSee('HIDDEN DRAFT BODY', false);
});

it('flags system vs custom pages', function () {
    expect(Page::make(['page_key' => 'about'])->isSystem())->toBeTrue()
        ->and(Page::make(['title' => 'Custom'])->isSystem())->toBeFalse();
});

it('prevents deleting a system page from admin but allows custom pages', function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $system = Page::create(['title' => 'About', 'slug' => 'about', 'page_key' => 'about', 'status' => true]);
    $custom = Page::create(['title' => 'Lookbook', 'slug' => 'lookbook', 'status' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.pages.destroy', $system->id))
        ->assertSessionHasErrors();
    $this->assertDatabaseHas('pages', ['id' => $system->id]);

    $this->actingAs($admin)
        ->delete(route('admin.pages.destroy', $custom->id))
        ->assertRedirect(route('admin.pages.index'));
    $this->assertDatabaseMissing('pages', ['id' => $custom->id]);
});
