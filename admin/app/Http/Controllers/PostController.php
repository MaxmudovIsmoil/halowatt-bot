<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\BotClient;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $perPage = in_array((int) $request->get('per_page'), [50, 100, 500])
                   ? (int) $request->get('per_page')
                   : 100;

        $posts = Post::with('channels')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('content', 'ilike', "%{$q}%")
                        ->orWhere('source_type', 'ilike', "%{$q}%")
                        ->orWhereRaw("TO_CHAR(created_at, 'DD.MM.YYYY HH24:MI') LIKE ?", ["%{$q}%"])
                        ->orWhereHas('channels', fn($ch) => $ch->where('title', 'ilike', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->only(['q', 'per_page']));

        return view('posts.index', compact('posts', 'q', 'perPage'));
    }

    public function show(Post $post)
    {
        $post->load('channels');
        return view('posts.show', ['post' => $post]);
    }

    /** Matnni tahrirlash. Post allaqachon yuborilgan bo'lsa, Telegram kanallaridagi xabarlar ham yangilanadi. */
    public function update(Request $request, Post $post, BotClient $bot)
    {
        $data = $request->validate(['content' => ['required', 'string']]);

        if ($post->status === 'sent') {
            $res = $bot->editPost($post->id, $data['content']);

            if (! array_key_exists('errors', $res)) {
                return back()->with('error', 'Bot bilan bog\'lanib bo\'lmadi, matn yangilanmadi: ' . ($res['error'] ?? 'nomaʼlum xato'));
            }

            return back()->with(
                $res['ok'] ? 'success' : 'error',
                $res['ok']
                    ? 'Matn saqlandi va Telegram kanallarida yangilandi.'
                    : ('Matn saqlandi, lekin ba\'zi kanallarda yangilanmadi: ' . implode('; ', $res['errors']))
            );
        }

        $post->update(['content' => $data['content']]);
        return back()->with('success', 'Matn saqlandi.');
    }

    /** Tasdiqlab yuborish */
    public function approve(Post $post, BotClient $bot)
    {
        if ($post->status === 'sent') {
            return back()->with('error', 'Bu post allaqachon yuborilgan.');
        }
        $res = $bot->sendPost($post->id);
        return back()->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Post kanallarga yuborildi.' : ('Xato: ' . ($res['error'] ?? 'nomaʼlum'))
        );
    }

    /** Rad etish */
    public function reject(Post $post)
    {
        if ($post->status !== 'sent') {
            $post->update(['status' => 'rejected']);
        }
        return back()->with('success', 'Post rad etildi.');
    }

    /** O'chirish. Post allaqachon yuborilgan bo'lsa, Telegram kanallaridagi xabarlar ham o'chiriladi. */
    public function destroy(Post $post, BotClient $bot)
    {
        if ($post->status === 'sent') {
            $res = $bot->deletePost($post->id);

            if (! array_key_exists('errors', $res)) {
                return back()->with('error', 'Bot bilan bog\'lanib bo\'lmadi, post o\'chirilmadi: ' . ($res['error'] ?? 'nomaʼlum xato'));
            }

            return redirect()->route('posts.index')->with(
                $res['ok'] ? 'success' : 'error',
                $res['ok']
                    ? 'Post o\'chirildi (kanallardan ham).'
                    : ('Post o\'chirildi, lekin ba\'zi kanallardan Telegram xabari o\'chmadi: ' . implode('; ', $res['errors']))
            );
        }

        $post->delete();
        return redirect()->route('posts.index')->with('success', 'Post o\'chirildi.');
    }
}
