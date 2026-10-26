<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLES = [
        'superadmin' => ['label' => 'Super Admin', 'icon' => '🛡️', 'color' => 'red'],
        'chief' => ['label' => 'Boshliq',      'icon' => '👔', 'color' => 'purple'],
        'accountant' => ['label' => 'Buxgalter',    'icon' => '💼', 'color' => 'green'],
        'executor' => ['label' => 'Ijrochi',      'icon' => '👷', 'color' => 'orange'],
    ];

    private function gate(): void
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403);
        }
    }

    public function index(): View
    {
        $this->gate();
        $users = User::orderByRaw("CASE \"role\"
            WHEN 'superadmin' THEN 1
            WHEN 'chief'      THEN 2
            WHEN 'accountant' THEN 3
            ELSE 4 END")->orderBy('name')->get();

        return view('users.index', ['users' => $users, 'roles' => self::ROLES]);
    }

    public function create(): View
    {
        $this->gate();

        return view('users.create', ['roles' => self::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        if ($data['role'] === 'superadmin' && ! auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Faqat super admin boshqa super admin yarata oladi.']);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        $label = self::ROLES[$data['role']]['label'];

        return redirect()->route('users.index')
            ->with('success', "{$data['name']} ({$label}) qo'shildi.");
    }

    public function edit(User $user): View
    {
        $this->gate();

        return view('users.edit', ['user' => $user, 'roles' => self::ROLES]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->gate();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($data['role'] === 'superadmin' && ! auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Faqat super admin bu rolni bera oladi.']);
        }

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return redirect()->route('users.index')->with('success', "{$user->name} yangilandi.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->gate();

        if ($user->id === auth()->id()) {
            return back()->withErrors(["O'zingizni o'chira olmaysiz."]);
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['Super adminni faqat super admin o\'chira oladi.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "{$name} o'chirildi.");
    }
}
