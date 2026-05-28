<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::whereNotNull('published_at')
            ->with(['categories', 'author'])
            ->latest('published_at');

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }
        if ($tagSlug = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }
        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $posts = $query->paginate(6)->withQueryString();
        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();
        $tags = BlogTag::take(20)->get();
        $recent = BlogPost::whereNotNull('published_at')->latest('published_at')->take(4)->get();

        return view('blog.index', compact('posts', 'categories', 'tags', 'recent'));
    }

    public function show(BlogPost $post)
    {
        $post->load(['categories', 'tags', 'author', 'images', 'comments' => fn ($q) => $q->where('approved', true)->whereNull('parent_id')->latest()]);
        $post->increment('views');

        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();
        $tags = BlogTag::take(20)->get();
        $recent = BlogPost::where('id', '!=', $post->id)->whereNotNull('published_at')->latest('published_at')->take(4)->get();

        return view('blog.show', compact('post', 'categories', 'tags', 'recent'));
    }

    public function storeComment(Request $request, BlogPost $post)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ]);
        $data['user_id'] = auth()->id();
        $data['approved'] = false;
        $post->comments()->create($data);

        return redirect()->route('blog.show', $post)->with('status', 'Your comment was submitted and is awaiting moderation.');
    }
}
