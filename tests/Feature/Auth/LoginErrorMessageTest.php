<?php

use App\Models\User;

test('failed admin login shows a readable message instead of the translation key', function () {
    $user = User::factory()->create();

    $response = $this->from('/admin/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'The email address or password you entered is incorrect.',
    ]);
});

test('failed storefront login shows a readable message instead of the translation key', function () {
    $user = User::factory()->create();

    $response = $this->from('/login')->post(route('frontend.login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'The email address or password you entered is incorrect.',
    ]);
});

test('failed login message is translated for the khmer locale', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['locale' => 'km'])->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'អាសយដ្ឋានអ៊ីមែល ឬពាក្យសម្ងាត់ដែលអ្នកបញ្ចូលមិនត្រឹមត្រូវទេ។',
    ]);
});

test('json translation files do not shadow php translation groups', function () {
    foreach (['en', 'km'] as $locale) {
        $lines = json_decode(file_get_contents(lang_path("{$locale}.json")), true, 512, JSON_THROW_ON_ERROR);

        $dotted = array_filter(
            array_keys($lines),
            fn (string $key) => preg_match('/^(auth|pagination|passwords|validation)\./', $key) === 1,
        );

        expect($dotted)->toBe([], "{$locale}.json shadows PHP translation groups: ".implode(', ', $dotted));
    }
});
