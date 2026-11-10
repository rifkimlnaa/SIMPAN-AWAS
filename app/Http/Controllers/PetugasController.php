<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PetugasController extends Controller
{
    public function index(): View
    {
        return view('petugas.index', [
            'petugas' => User::where('role', User::ROLE_PETUGAS)->withCount('arsips')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('petugas.form', ['petugas' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $petugas = new User(['name' => $data['name'], 'username' => $data['username'], 'password' => $data['password']]);
        $petugas->role = User::ROLE_PETUGAS;
        $petugas->save();

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(User $petugas): View
    {
        $this->pastikanPetugas($petugas);

        return view('petugas.form', ['petugas' => $petugas]);
    }

    public function update(Request $request, User $petugas): RedirectResponse
    {
        $this->pastikanPetugas($petugas);

        $data = $this->validated($request, $petugas);

        $petugas->name = $data['name'];
        $petugas->username = $data['username'];
        if (! empty($data['password'])) {
            $petugas->password = $data['password'];
        }
        $petugas->save();

        return redirect()->route('petugas.index')->with('success', 'Data petugas berhasil diperbarui.');
    }

    public function destroy(User $petugas): RedirectResponse
    {
        $this->pastikanPetugas($petugas);

        $jumlah = $petugas->arsips()->count();

        if ($jumlah > 0) {
            return back()->with('error', "Petugas \"{$petugas->name}\" tidak bisa dihapus karena masih memiliki {$jumlah} arsip.");
        }

        if ($petugas->foto) {
            Storage::disk('public')->delete($petugas->foto);
        }

        $petugas->delete();

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }

    /** Halaman ini hanya untuk akun petugas, bukan admin. */
    private function pastikanPetugas(User $user): void
    {
        abort_unless($user->role === User::ROLE_PETUGAS, 404);
    }

    private function validated(Request $request, ?User $petugas = null): array
    {
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'min:4', 'max:50', 'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($petugas?->id),
            ],
            'password' => [$petugas ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);
    }
}
