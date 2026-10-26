<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Services\CustomerService;
use App\Models\User;
use App\Services\UserService;
use DateTimeZone;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedPerPage = [10, 15, 25, 50, 100];

        $usersPerPage = (int) $request->input('users_per_page', 15);
        if (!in_array($usersPerPage, $allowedPerPage, true)) {
            $usersPerPage = 15;
        }

        $customersPerPage = (int) $request->input('customers_per_page', 15);
        if (!in_array($customersPerPage, $allowedPerPage, true)) {
            $customersPerPage = 15;
        }

        $filters = $request->only(['search', 'status', 'role_id']);
        $users = $this->userService->getPaginated($filters, $usersPerPage, 'users_page');
        $users->appends($request->except('users_page'));
        $roles = $this->userService->getRolesForSelect();

        $customersFilters = $request->only(['search', 'status', 'customer_group_id']);
        $customers = $this->customerService->getPaginated($customersFilters, $customersPerPage, 'customers_page');
        $customers->appends($request->except('customers_page'));

        return view('admin.users.index', compact('users', 'roles', 'filters', 'customers', 'customersFilters', 'usersPerPage', 'customersPerPage'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = $this->userService->getRolesForSelect();
        $timezones = DateTimeZone::listIdentifiers();
        return view('admin.users.create', compact('roles', 'timezones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'userGroups']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load(['roles', 'userGroups']);
        $roles = $this->userService->getRolesForSelect();
        $timezones = DateTimeZone::listIdentifiers();
        return view('admin.users.edit', compact('user', 'roles', 'timezones'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->userService->delete($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

