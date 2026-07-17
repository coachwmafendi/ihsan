<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Settings — Account')]
class Account extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?string $current_password = null;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public $avatar = null;

    public ?string $existing_avatar = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->existing_avatar = $user->avatar_url;
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->name = $validated['name'];

        if (! $user->isSuperAdmin() && $validated['email'] !== $user->email) {
            $this->processAvatar($user);
            $user->save();
            $this->addError('email', 'Organization admin email cannot be changed.');
            $this->email = $user->email;

            return;
        }

        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            if (! Hash::check($validated['current_password'] ?? '', $user->password)) {
                $this->addError('current_password', 'The current password is incorrect.');

                return;
            }

            $user->password = Hash::make($validated['password']);
        }

        $this->processAvatar($user);

        $user->save();

        $this->existing_avatar = $user->avatar_url;
        $this->reset(['current_password', 'password', 'password_confirmation', 'avatar']);

        $this->dispatch('account-updated');
        $this->dispatch('notify', message: 'Account details updated.', variant: 'success');
    }

    public function removeAvatar(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->avatar_url) {
            return;
        }

        Storage::disk('public')->delete($user->avatar_url);

        $user->avatar_url = null;
        $user->save();

        $this->existing_avatar = null;
        $this->avatar = null;

        $this->dispatch('account-updated');
        $this->dispatch('notify', message: 'Avatar removed.', variant: 'success');
    }

    private function processAvatar(User $user): void
    {
        if (! $this->avatar) {
            return;
        }

        /** @var UploadedFile $avatar */
        $avatar = $this->avatar;

        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        $path = $avatar->store('avatars', 'public');
        $user->avatar_url = $path;
    }

    public function render()
    {
        return view('livewire.app.settings.account');
    }
}
