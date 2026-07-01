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

        $this->email = strtolower(trim($this->email));

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

    public function changeRole(int $id, string $role): void
    {
        $this->authorizeOwner();

        $member = $this->member($id);
        $role = Role::from($role);

        if ($role === Role::Staff && $member->isOwner() && $this->ownerCount() === 1) {
            $this->dispatch('notify', message: 'A shop needs at least one owner.');

            return;
        }

        $member->update(['role' => $role]);
        $this->notify("{$member->name} is now {$role->label()}.");
    }

    public function remove(int $id): void
    {
        $this->authorizeOwner();

        $member = $this->member($id);

        if ($member->is($this->user())) {
            $this->dispatch('notify', message: "You can't remove yourself. Delete your account from your profile instead.");

            return;
        }

        if ($member->isOwner() && $this->ownerCount() === 1) {
            $this->dispatch('notify', message: 'A shop needs at least one owner.');

            return;
        }

        $member->delete();
        $this->notify("{$member->name} removed. Their stock history stays.");
    }

    /**
     * @return Collection<int, User>
     */
    public function members(): Collection
    {
        return $this->shop()->users()->orderBy('role')->orderBy('name')->get();
    }

    private function member(int $id): User
    {
        return $this->shop()->users()->findOrFail($id);
    }

    private function ownerCount(): int
    {
        return $this->shop()->owners()->count();
    }

    public function render()
    {
        return view('livewire.team.index');
    }
}
