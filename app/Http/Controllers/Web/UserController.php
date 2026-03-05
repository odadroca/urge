<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::withCount('apiKeys')->latest()->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('viewAny', User::class);
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:admin,editor,viewer'],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $isSelf             = auth()->id() === $user->id;
        $adminEditingOther  = auth()->user()->isAdmin() && !$isSelf;

        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'email'                    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'                     => $adminEditingOther ? ['required', 'in:admin,editor,viewer'] : ['nullable'],
            'new_password'             => ['nullable', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['nullable', 'string'],
            'current_password'         => ['nullable', 'string'],
        ]);

        // Self-editing: verify current password before allowing a change
        if ($isSelf && $request->filled('new_password')) {
            if (!Hash::check($request->input('current_password', ''), $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput();
            }
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if ($adminEditingOther) {
            $user->role = $validated['role'];
        }

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        if ($isSelf) {
            return redirect()->route('users.edit', $user)->with('success', 'Account updated.');
        }

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
