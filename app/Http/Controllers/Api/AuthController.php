<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and issue API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Handle user logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        // Hapus token Sanctum jika ada
        if ($request->user()->currentAccessToken() && method_exists($request->user()->currentAccessToken(), 'delete')) {
            $request->user()->currentAccessToken()->delete();
        }

        // Logout dari session juga
        \Illuminate\Support\Facades\Auth::guard('web')->logout();

        return response()->json([
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * Get authenticated user profile (returns dashboard view).
     */
    public function me(Request $request)
    {
        // Tampilkan dashboard yang sama dengan web route
        $totalCourses = \App\Models\Course::count();
        $activeCourses = \App\Models\Course::where('status', 'active')->count();
        $draftCourses = \App\Models\Course::where('status', 'draft')->count();
        $totalLecturers = User::where('role', 'dosen')->count();
        $totalStudents = User::where('role', 'mahasiswa')->count();
        $totalMaterials = \App\Models\Material::count();
        $totalAssignments = \App\Models\Assignment::count();

        $recentCourses = \App\Models\Course::with('lecturer')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalCourses',
            'activeCourses',
            'draftCourses',
            'totalLecturers',
            'totalStudents',
            'totalMaterials',
            'totalAssignments',
            'recentCourses'
        ));
    }
}
