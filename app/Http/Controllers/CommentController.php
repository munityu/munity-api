<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Tymon\JWTAuth\Facades\JWTAuth;

class CommentController extends Controller
{
    private $error_404 = [['message' => 'Comment not found'], 404];
    private $error_403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function show(int $id)
    {
        if (!$comment = Comment::find($id))
            return response(...$this->error_404);
        return $comment;
    }

    public function update(\Illuminate\Http\Request $request, int $id)
    {
        if (!$comment = Comment::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $comment->user() != $this->user->id)
            return response(...$this->error_403);
        return $comment->update($request->all());
    }

    public function destroy(int $id)
    {
        if (!$comment = Comment::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $comment->user() != $this->user->id)
            return response(...$this->error_403);
        return $comment->delete();
    }
}
