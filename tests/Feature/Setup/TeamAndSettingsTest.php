<?php

use App\Enums\Role;
use App\Livewire\Settings\ShopSettings;
use App\Livewire\Team\Index;
use App\Models\Shop;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->owner = owner($this->shop));
});

test('adds a staff member who can then sign in', function () {
    Livewire::test(Index::class)
        ->set('name', 'Maya Chen')
        ->set('email', 'Maya@Example.com')
        ->set('password', 'shelf-stacker')
        ->call('add')
        ->assertHasNoErrors();

    $maya = User::where('email', 'maya@example.com')->sole();
    expect($maya->shop_id)->toBe($this->shop->id)
        ->and($maya->role)->toBe(Role::Staff)
        ->and(Auth::validate(['email' => 'maya@example.com', 'password' => 'shelf-stacker']))->toBeTrue();
});

test('emails must be unique across all shops', function () {
    User::factory()->create(['email' => 'maya@example.com']);

    Livewire::test(Index::class)
        ->set('name', 'Maya Chen')
        ->set('email', 'maya@example.com')
        ->set('password', 'shelf-stacker')
        ->call('add')
        ->assertHasErrors('email');
});

test('changes roles but always keeps an owner', function () {
    $maya = staff($this->shop);

    Livewire::test(Index::class)->call('changeRole', $maya->id, 'owner');
    expect($maya->fresh()->isOwner())->toBeTrue();

    Livewire::test(Index::class)->call('changeRole', $maya->id, 'staff');
    Livewire::test(Index::class)->call('changeRole', $this->owner->id, 'staff')->assertDispatched('notify');

    expect($this->owner->fresh()->isOwner())->toBeTrue();
});

test('removes team members but not yourself', function () {
    $maya = staff($this->shop);

    Livewire::test(Index::class)
        ->call('remove', $this->owner->id)
        ->call('remove', $maya->id);

    expect(User::pluck('id')->all())->toBe([$this->owner->id]);
});

test('can not touch users of another shop', function () {
    $stranger = owner();

    expect(fn () => Livewire::test(Index::class)->call('remove', $stranger->id))
        ->toThrow(ModelNotFoundException::class);

    expect($stranger->fresh())->not->toBeNull();
});

test('updates the shop name and currency', function () {
    Livewire::test(ShopSettings::class)
        ->set('name', 'Harbour Lane Goods')
        ->set('currency', 'EUR')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->shop->fresh()->name)->toBe('Harbour Lane Goods')
        ->and(Money::format(1250, $this->shop->fresh()->currency))->toBe('€12.50');
});
