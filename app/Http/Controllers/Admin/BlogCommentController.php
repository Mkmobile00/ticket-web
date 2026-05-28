<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlogComment;
use Illuminate\Http\Request;

class BlogCommentController extends AdminController
{
    public function index()
    {
        $comments = BlogComment::with('post')->latest()->paginate(20);
        return view('admin.blog-comments.index', compact('comments'));
    }

    public function update(Request $request, $id)
    {
        $comment = BlogComment::findOrFail($id);
        $comment->update(['approved' => $request->boolean('approved')]);
        return back()->with('status', 'Comment ' . ($comment->approved ? 'approved' : 'unapproved') . '.');
    }

    public function destroy($id)
    {
        BlogComment::findOrFail($id)->delete();
        return back()->with('status', 'Comment deleted.');
    }
}
