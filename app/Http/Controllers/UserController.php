<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    private const ROLES = ['owner', 'supervisor', 'admin', 'gudang', 'akuntan', 'kasir', 'procurement'];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select('*');

            return DataTables::of($users)
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function ($row) {
                    $editBtn = '<a href="'.route('users.edit', $row->id).'" class="btn btn-warning btn-sm btn-icon-sm mr-1"><i class="fas fa-edit"></i></a>';

                    $deleteBtn = '';
                    if ($row->id !== auth()->id()) {
                        $deleteBtn = '<form action="'.route('users.destroy', $row->id).'" method="POST" style="display:inline" data-confirm="Yakin hapus user '.e($row->name).'?">
                                '.csrf_field().method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-sm btn-icon-sm"><i class="fas fa-trash"></i></button>
                            </form>';
                    }

                    return $editBtn.$deleteBtn;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('pages.user.index');
    }

    public function create()
    {
        $roles = self::ROLES;

        return view('pages.user.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => 'required|string|min:6',
            'is_active'=> 'nullable|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = self::ROLES;

        return view('pages.user.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email'    => 'required|email|max:255|unique:users,email,'.$user->id,
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => 'nullable|string|min:6',
            'is_active'=> 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($user->id === auth()->id() && !$validated['is_active']) {
            return back()->withInput()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
