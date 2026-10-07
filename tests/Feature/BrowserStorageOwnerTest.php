<?php

use App\Models\User;

it('tags browser storage with the signed-in account so it is wiped on logout or account change', function () {
    // The owner-check script runs on every storefront page, before main.js.
    $guest = $this->get(route('frontend.home'))->assertOk()->getContent();
    expect($guest)->toContain("localStorage.getItem('ut_owner')")
        ->and(strpos($guest, 'ut_owner'))->toBeLessThan(strpos($guest, 'assets/frontend/js/main.js'));

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('id: '.$user->id, false)
        ->assertSee("['ut_wish', 'ut_cart', 'ut_coupon']", false);
});
