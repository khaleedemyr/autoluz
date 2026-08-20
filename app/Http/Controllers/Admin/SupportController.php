<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request): Response
    {
        $request->user()?->touchPresence();

        $status = (string) $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));
        $activeId = (int) $request->query('conversation', 0);

        $conversations = SupportConversation::query()
            ->with('user:id,name,email')
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_type', SupportMessage::SENDER_VISITOR)
                ->whereNull('read_at')])
            ->when($status === 'open', fn ($query) => $query->where('status', SupportConversation::STATUS_OPEN))
            ->when($status === 'closed', fn ($query) => $query->where('status', SupportConversation::STATUS_CLOSED))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('visitor_name', 'like', $like)
                        ->orWhere('visitor_email', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(fn (SupportConversation $row) => $row->toAdminArray())
            ->values();

        $active = null;
        $messages = [];

        if ($activeId > 0) {
            $found = SupportConversation::query()->with('user:id,name,email')->find($activeId);
            if ($found) {
                $this->support->markRead($found, SupportMessage::SENDER_VISITOR);
                $active = $found->toAdminArray();
                $messages = $this->support->messagesAfter($found, 0, 'agent');
            }
        }

        return Inertia::render('Admin/Support/Index', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $messages,
            'filters' => [
                'status' => in_array($status, ['open', 'closed', 'all'], true) ? $status : 'open',
                'q' => $q,
            ],
            'unread' => $this->support->unreadCount(),
        ]);
    }

    public function store(Request $request, SupportConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $request->user()?->touchPresence();

        $message = $this->support->addMessage(
            $conversation,
            SupportMessage::SENDER_AGENT,
            trim($data['body']),
            $request->user(),
        );
        $message->setRelation('user', $request->user());
        $message->setRelation('conversation', $conversation);

        return response()->json([
            'message' => $message->toFeedArray('agent'),
        ]);
    }

    public function update(Request $request, SupportConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $conversation->update(['status' => $data['status']]);

        return back();
    }

    public function poll(Request $request, SupportConversation $conversation): JsonResponse
    {
        $request->user()?->touchPresence();

        $afterId = (int) $request->query('after_id', 0);
        $messages = $this->support->messagesAfter($conversation, $afterId, 'agent');

        if ($messages->isNotEmpty()) {
            $this->support->markRead($conversation, SupportMessage::SENDER_VISITOR);
        }

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
            'unread' => $this->support->unreadCount(),
        ]);
    }
}
