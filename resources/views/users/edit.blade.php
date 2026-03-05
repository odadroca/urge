<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ auth()->id() === $user->id ? 'Account Settings' : 'Edit User' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Profile details --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Profile</h3>
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-300 @enderror">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-300 @enderror">
                        @error('email')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    @if(auth()->user()->isAdmin() && auth()->id() !== $user->id)
                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="role"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['admin', 'editor', 'viewer'] as $r)
                            <option value="{{ $r }}" {{ old('role', $user->role) === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">admin: full access &bull; editor: create/edit prompts &bull; viewer: read-only</p>
                        @error('role')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    @else
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Role: <span class="font-medium capitalize">{{ $user->role }}</span></p>
                    </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Profile</button>
                    </div>
                </form>
            </div>

            {{-- Password --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-1">
                    {{ auth()->id() === $user->id ? 'Change Password' : 'Reset Password' }}
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    @if(auth()->id() === $user->id)
                        Leave blank to keep your current password.
                    @else
                        Leave blank to keep the user's current password.
                    @endif
                </p>
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PUT')

                    {{-- Pass through name/email/role so the single update() handler works --}}
                    <input type="hidden" name="name"  value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    @if(auth()->user()->isAdmin() && auth()->id() !== $user->id)
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    @endif

                    @if(auth()->id() === $user->id)
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('current_password') border-red-300 @enderror">
                        @error('current_password')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" id="new_password" autocomplete="new-password"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('new_password') border-red-300 @enderror">
                        @error('new_password')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-5">
                        <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" autocomplete="new-password"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Update Password</button>
                    </div>
                </form>
            </div>

            {{-- Back link for non-admins; danger zone for admins --}}
            @if(auth()->user()->isAdmin() && auth()->id() !== $user->id)
            <div class="flex justify-between items-center">
                <a href="{{ route('users.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to Users</a>
                <form method="POST" action="{{ route('users.destroy', $user) }}"
                      onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:underline">Delete user</button>
                </form>
            </div>
            @else
            <div class="text-center">
                <a href="{{ url()->previous() === route('users.edit', $user) ? route('dashboard') : url()->previous() }}"
                   class="text-sm text-indigo-600 hover:underline">← Back</a>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
