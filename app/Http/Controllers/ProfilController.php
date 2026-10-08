<?php

namespace App\Http\Controllers;

use App\Http\Requests\GantiPasswordRequest;
use App\Http\Requests\ProfilRequest;
use Illuminate\Http\Request;

class ProfilController extends Controller
{
    public function index(Request $request)
    {
        return view('profil.index', ['user' => $request->user()->load('guru')]);
    }

    public function update(ProfilRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(GantiPasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success_password', 'Password berhasil diganti.');
    }
}
