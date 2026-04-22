<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('division');

        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('userid', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $users = $query->paginate(10)->withQueryString();
        $divisions = Division::all();

        return view('admin.user.index', compact('users', 'divisions'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        if ($user->id == auth()->id()) {
            abort(403);
        }

        $divisions = Division::all();

        return view('admin.user.edit', compact('user', 'divisions'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id == auth()->id()) {
            abort(403);
        }

        $request->merge([
            'userid' => substr(strtoupper(trim((string) $request->userid)), 0, 3),
        ]);

        $request->validate([
            'name' => 'required',
            'userid' => [
                'required',
                'string',
                'max:3',
                Rule::unique('users', 'userid')->ignore($user->id),
            ],
            'email' => 'required|email',
            'division_id' => 'required',
            'role' => 'required',
            'password' => 'nullable|confirmed|min:6',
        ], [
            'userid.unique' => 'User ID telah terpakai, silakan gunakan User ID lain.',
        ]);

        $data = [
            'name' => ucwords(strtolower($request->name)),
            'userid' => $request->userid,
            'email' => $request->email,
            'division_id' => $request->division_id,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('user.index')
            ->with('success', 'User berhasil diupdate');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id == auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus');
    }
}
