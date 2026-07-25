<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Incident $incident)
    {
        $this->authorize('view', $incident);

        $messages = $incident->messages()
            ->with(['user', 'attachment'])
            ->oldest()
            ->paginate(20);

        // Marquer les messages comme lus
        $incident->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json($messages);
    }

public function store(Request $request, Incident $incident)
    {
        $user = Auth::user();
        $isCompanyOwner = $user->isCompany() && $incident->company_id === $user->company->id;
        $isAssignedTechnician = $user->isTechnician() && $incident->technician_id === $user->id;

        if (!$user->isAdmin() && !$isCompanyOwner && !$isAssignedTechnician) {
            abort(403, 'Vous ne pouvez pas envoyer de message sur cet incident.');
        }

        $request->validate([
            'content' => 'required|string',
            'attachment' => 'nullable|file|max:5120', // 5 Mo max
        ]);

        $message = Message::create([
            'incident_id' => $incident->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('message-attachments', 'public');

            $message->attachment()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);
        }

        $recipientId = Auth::id() === $incident->company->user_id
            ? $incident->technician_id
            : $incident->company->user_id;

        if ($recipientId) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'new_message',
                'message' => 'Nouveau message concernant l\'incident : ' . $incident->title,
                'data' => [
                    'incident_id' => $incident->id,
                    'message_id' => $message->id,
                ],
            ]);
        }

        return response()->json($message->load(['user', 'attachment']), 201);
    }
}