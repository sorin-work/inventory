<?php

use App\Enums\Role;
use App\Livewire\Team\Index;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->owner = owner($this->shop));
});

test('adds a staff member who can then sign in', function () {
    Livewire::test(Index::class)
        ->set('name', 'Maya Chen')
        ->set('email', 'maya@example.com')
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
