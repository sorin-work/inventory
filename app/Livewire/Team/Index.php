<?php

namespace App\Livewire\Team;

use App\Enums\Role;
use App\Livewire\Concerns\WithShop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Team')]
class Index extends Component
{
    use WithShop;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'staff';

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function add(): void
    {
        $this->authorizeOwner();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        User::create([...$validated, 'shop_id' => $this->shop()->id]);

        $this->notify("{$validated['name']} can now sign in with {$validated['email']}.");
        $this->reset('name', 'email', 'password', 'role');
    }

    /**
     * @return Collection<int, User>
     */
    public function members(): Collection
    {
        return $this->shop()->users()->orderBy('role')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.team.index');
    }
}
