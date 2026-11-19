<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    private const ROLES = [
        'admin' => 'Admin',
        'customer' => 'Customer',
        'driver' => 'Driver',
        'provider' => 'Provider',
    ];

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request, ['manage users', 'manage platform users', 'view all customers', 'view all drivers']);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(self::ROLES))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim($filters['q'] ?? '');

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $query->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle);
            }))
            ->when(isset($filters['role']), fn (Builder $query) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $filters['role'])))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('status', 'active'))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('status', '!=', 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'roleFilter' => $filters['role'] ?? '',
            'statusFilter' => $filters['status'] ?? '',
            'roleLabels' => self::ROLES,
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin(request());

        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create($validated);
        $user->assignRole('customer');

        return to_route('admin.users.index')->with('status', 'Customer account created.');
    }

    public function edit(User $user): View
    {
        $this->authorizeAdmin(request());
        $user->load('roles');

        return view('admin.users.edit', ['user' => $user, 'roleLabels' => self::ROLES]);
    }

    public function show(Request $request, User $user): View
    {
        $this->authorizeAdmin($request);
        $user->load(['roles', 'providers', 'driver']);

        return view('admin.users.show', ['user' => $user, 'roleLabels' => self::ROLES]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return to_route('admin.users.index')->with('status', 'User account updated.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);
        $actor = $request->user();
        $role = $validated['role'];

        DB::transaction(function () use ($actor, $user, $role): void {
            $activeAdmins = User::query()
                ->where('status', 'active')
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'admin'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $target->load('roles');

            if ($target->is($actor) && $target->hasRole('admin') && $role !== 'admin') {
                throw ValidationException::withMessages(['role' => 'You cannot remove your own Admin access.']);
            }

            if ($target->status === 'active' && $target->hasRole('admin') && $role !== 'admin' && $activeAdmins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'The platform must retain at least one active Admin.']);
            }

            if ($role === 'provider' && ! $target->providers()
                ->wherePivot('is_active', true)
                ->where('providers.status', 'active')
                ->exists()) {
                throw ValidationException::withMessages(['role' => 'A Provider role requires an active provider membership. Provision or reactivate the provider membership first.']);
            }

            if ($role === 'driver' && ! $target->driver()->exists()) {
                throw ValidationException::withMessages(['role' => 'A Driver role requires an existing driver profile.']);
            }

            $target->syncRoles([$role]);
            $target->syncPermissions([]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return to_route('admin.users.show', $user)->with('status', 'User role updated. Previous role permissions were removed.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        $actor = $request->user();
        $status = $validated['status'];

        DB::transaction(function () use ($actor, $user, $status): void {
            $activeAdmins = User::query()
                ->where('status', 'active')
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'admin'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $target->load('roles');

            if ($status === 'inactive' && $target->is($actor)) {
                throw ValidationException::withMessages(['status' => 'You cannot deactivate your own account.']);
            }

            if ($status === 'inactive' && $target->status === 'active' && $target->hasRole('admin') && $activeAdmins->count() <= 1) {
                throw ValidationException::withMessages(['status' => 'The platform must retain at least one active Admin.']);
            }

            if ($status === 'inactive') {
                $target->tokens()->delete();
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
                }
                $target->remember_token = Str::random(60);
            }

            $target->status = $status;
            $target->save();
        });

        return to_route('admin.users.show', $user)->with('status', $status === 'active' ? 'User account reactivated.' : 'User account deactivated.');
    }

    /** @param array<int, string> $permissions */
    private function authorizeAdmin(Request $request, array $permissions = ['manage users', 'manage platform users']): void
    {
        $actor = $request->user();
        abort_unless($actor !== null && $actor->hasRole('admin'), 403);

        foreach ($permissions as $permission) {
            abort_unless($actor->can($permission), 403);
        }
    }
}
