<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with(['messages'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Store in 'public/attachments'
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'ticket_number' => 'TK-' . strtoupper(Str::random(8)),
            'subject' => $request->subject,
            'priority' => $request->priority,
            'status' => 'Open',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'attachments' => $attachmentPaths,
        ]);

        return response()->json([
            'message' => 'Support ticket created successfully.',
            'ticket' => $ticket->load('messages'),
        ]);
    }

    public function show($id, Request $request)
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->with(['messages.user'])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $message = TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'attachments' => $attachmentPaths,
        ]);

        $ticket->status = 'Replied';
        $ticket->last_reply = now();
        $ticket->save();

        return response()->json([
            'message' => 'Reply sent successfully.',
            'ticket_message' => $message,
        ]);
    }

    public function close($id, Request $request)
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $ticket->status = 'Closed';
        $ticket->save();

        return response()->json(['message' => 'Ticket closed successfully.']);
    }
    // --- ADMIN METHODS ---

    public function adminIndex(Request $request)
    {
        // Admin sees ALL tickets
        $tickets = SupportTicket::with(['user', 'messages'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    public function adminShow($id)
    {
        $ticket = SupportTicket::with(['user', 'messages.user'])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    public function adminReply(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $message = TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id, // The Admin's ID
            'message' => $request->message,
            'attachments' => $attachmentPaths,
        ]);

        $ticket->status = 'Answered'; // Valid status from enum
        $ticket->last_reply = now();
        $ticket->save();

        return response()->json([
            'message' => 'Admin reply sent successfully.',
            'ticket_message' => $message,
        ]);
    }

    public function adminClose($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = 'Closed';
        $ticket->save();
        return response()->json(['message' => 'Ticket closed by admin.']);
    }
}
