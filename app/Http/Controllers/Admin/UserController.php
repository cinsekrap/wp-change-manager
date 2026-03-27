<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(25);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User()]);
    }

    public function store(StoreUserRequest $request)
    {
        User::create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function editPassword()
    {
        return view('admin.users.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        auth()->user()->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Password updated.');
    }

    public function resetMfa(User $user)
    {
        $user->update([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_confirmed_at' => null,
        ]);

        return back()->with('success', 'Two-factor authentication has been reset for ' . $user->name . '. They will be required to set it up again on their next login.');
    }
}
