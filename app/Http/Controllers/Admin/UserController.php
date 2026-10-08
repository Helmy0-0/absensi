<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('guru')
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('nama', 'like', $kata)->orWhere('username', 'like', $kata));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', ['user' => null]);
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);
        $this->service->buat($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('admin.users.form', ['user' => $user->load('guru')]);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $this->service->ubah($user, $request->validated(), $request->user());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);
        $this->service->hapus($user, $request->user());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus (dinonaktifkan).');
    }
}
