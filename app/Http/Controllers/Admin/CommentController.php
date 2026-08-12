<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(Request $request): Response
    {
        $visibility = (string) $request->query('visibility', '');
        $q = trim((string) $request->query('q', ''));
        $perPage = $this->perPage($request);

        $comments = Comment::query()
            ->with('article:id,title,slug')
            ->when($visibility === 'visible', fn ($query) => $query->where('is_visible', true))
            ->when($visibility === 'hidden', fn ($query) => $query->where('is_visible', false))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhereHas('article', fn ($a) => $a->where('title', 'like', $like));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Comment $comment) => $comment->toAdminArray());

        return Inertia::render('Admin/Comments/Index', [
            'comments' => $comments,
            'filters' => [
                'q' => $q,
                'visibility' => $visibility,
                'per_page' => $perPage,
            ],
            'stats' => [
                'all' => Comment::query()->count(),
                'visible' => Comment::query()->visible()->count(),
                'hidden' => Comment::query()->where('is_visible', false)->count(),
            ],
        ]);
    }

    public function toggle(Comment $comment): RedirectResponse
    {
        $comment->is_visible = ! $comment->is_visible;
        $comment->save();

        $label = $comment->is_visible ? 'ditampilkan' : 'disembunyikan';

        return back()->with('success', "Komentar {$label}.");
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'Komentar dihapus.');
    }

    protected function perPage(Request $request): int
    {
        $allowed = [10, 15, 25, 50, 100];
        $perPage = (int) $request->query('per_page', 15);

        return in_array($perPage, $allowed, true) ? $perPage : 15;
    }
}
