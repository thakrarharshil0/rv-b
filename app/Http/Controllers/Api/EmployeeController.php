<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', '!=', 'customer');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('lower(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('lower(email) like ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('status') && $request->status !== 'All Status') {
            // Assuming status might be a column later, for now we filter by role or something
            // If status is not in DB, we ignore it or add it
        }

        $employees = $query->latest()->get()->map(fn ($emp) => [
            'id' => 'EMP' . str_pad($emp->id, 3, '0', STR_PAD_LEFT),
            'rawId' => $emp->id,
            'name' => $emp->name,
            'email' => $emp->email,
            'role' => $emp->role,
            'status' => 'Active', // Default
            'joined' => $emp->created_at->format('Y-m-d'),
            'phone' => 'Not Provided', // From profile or orders? For now blank
        ]);

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Employee onboarded successfully',
            'item' => $user
        ], 201);
    }

    public function destroy($id)
    {
        // Extract numeric ID from EMP00x if needed, but let's assume raw ID is passed
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        if ($user->role === 'super_admin') {
            return response()->json(['message' => 'Cannot delete super admin'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Employee removed']);
    }
}
