<?php

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form')
        ->assertSeeVolt('profile.delete-user-form');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $component
        ->assertHasErrors('password')
        ->assertNoRedirect();

    $this->assertNotNull($user->fresh());
});

test('the last owner deleting their account deletes the shop and its team', function () {
    $owner = User::factory()->create();
    $staff = User::factory()->staff()->create(['shop_id' => $owner->shop_id]);
    variant($owner->shop);

    $this->actingAs($owner);

    Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertRedirect('/');

    expect(Shop::find($owner->shop_id))->toBeNull()
        ->and($staff->fresh())->toBeNull()
        ->and(Product::withoutGlobalScopes()->count())->toBe(0);
});

test('staff deleting their account leaves the shop alone', function () {
    $owner = User::factory()->create();
    $staff = User::factory()->staff()->create(['shop_id' => $owner->shop_id]);

    $this->actingAs($staff);

    Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    expect($staff->fresh())->toBeNull()
        ->and($owner->fresh())->not->toBeNull()
        ->and(Shop::find($owner->shop_id))->not->toBeNull();
});
