<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function current(Request $request): JsonResponse
    {
        $conversation = $this->support->findForRequest($request);
        $user = $request->user();

        if ($conversation) {
            $this->support->markRead($conversation, SupportMessage::SENDER_AGENT);
        }

        return response()->json([
            'conversation' => $conversation?->toVisitorArray(),
            'messages' => $conversation
                ? $this->support->messagesAfter($conversation, 0, 'visitor')
                : [],
            'agents_online' => $this->support->agentsOnline(),
            'visitor' => [
                'name' => $conversation?->visitor_name ?: $user?->name,
                'email' => $conversation?->visitor_email ?: $user?->email,
                'logged_in' => (bool) $user,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $existing = $this->support->findForRequest($request);
        $needIdentity = ! $user && ! $existing;

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'name' => [$needIdentity ? 'required' : 'nullable', 'string', 'max:120'],
            'email' => [$needIdentity ? 'required' : 'nullable', 'email', 'max:160'],
        ]);

        $conversation = $this->support->findOrCreate($request, [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ]);

        $message = $this->support->addMessage(
            $conversation,
            SupportMessage::SENDER_VISITOR,
            trim($data['body']),
            $user,
        );
        $message->setRelation('conversation', $conversation);
        $message->setRelation('user', $user);

        return response()->json([
            'conversation' => $conversation->fresh()->toVisitorArray(),
            'message' => $message->toFeedArray('visitor'),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $conversation = $this->support->findForRequest($request);
        $afterId = (int) $request->query('after_id', 0);

        if (! $conversation) {
            return response()->json([
                'messages' => [],
                'agents_online' => $this->support->agentsOnline(),
            ]);
        }

        $messages = $this->support->messagesAfter($conversation, $afterId, 'visitor');

        if ($messages->isNotEmpty()) {
            $this->support->markRead($conversation, SupportMessage::SENDER_AGENT);
        }

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
            'agents_online' => $this->support->agentsOnline(),
        ]);
    }
}
