<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'required|string',
        ]);

        Contact::create($validated);

        return response()->json([
            'message' => 'Thank you! Your message has been sent successfully.',
        ], 200);
    }

    /**
     * Display a listing of the resource (Admin).
     */
    public function index()
    {
        // Return all contacts, newest first
        return response()->json(Contact::orderBy('created_at', 'desc')->get(), 200);
    }
}
