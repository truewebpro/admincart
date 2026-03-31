<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function getBlogComments(Request $request,$shopname,$blogId)
    {
        $shopId = $request->shop_id;
        $comments = Comment::query()
            ->where('blog_id', $blogId)
            ->where('shop_id', $shopId)
            ->root()
            ->with(['replies.customer'])
            ->latest()
            ->get();


        return response()->json($comments);
    }
}
