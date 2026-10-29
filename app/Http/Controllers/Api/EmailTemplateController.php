<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return response()->json(EmailTemplate::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'tone' => 'nullable|string',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $template = EmailTemplate::create($validated);
        return response()->json($template);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json($emailTemplate);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'trigger' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'tone' => 'nullable|string',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $emailTemplate->update($validated);
        return response()->json($emailTemplate);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return response()->json(['message' => 'Template deleted']);
    }
}
