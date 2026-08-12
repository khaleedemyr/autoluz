<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        // Honeypot — bots fill this hidden field.
        if (filled($request->input('website'))) {
            return back();
        }

        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        Comment::query()->create([
            'article_id' => $article->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'body' => $data['body'],
            'is_visible' => false,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return back()->with('success', 'Komentar terkirim dan menunggu moderasi admin.');
    }
}
