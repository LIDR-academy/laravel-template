<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Blog API (intentionally messy — refactor me)
|--------------------------------------------------------------------------
*/

// --- AUTH ---------------------------------------------------------------

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken('api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $token = $user->createToken('api')->plainTextToken;

    return response()->json(['token' => $token, 'user' => $user], 201);
});

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'logged out']);
})->middleware('auth:sanctum');

// --- POSTS ---------------------------------------------------------------

use App\Http\Controllers\PostController;

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // --- COMMENTS (create/delete = auth) --------------------------------

    Route::post('/posts/{id}/comments', function (Request $request, $id) {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment = new Comment;
        $comment->post_id = $post->id;
        $comment->user_id = $request->user()->id;
        $comment->body = $request->body;
        $comment->save();

        return response()->json($comment->load('user'), 201);
    });

    Route::delete('/comments/{id}', function (Request $request, $id) {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // author of the comment can delete it
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'deleted']);
    });

    // --- LIKES (toggle = auth) ------------------------------------------

    Route::post('/posts/{id}/like', function (Request $request, $id) {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existing = Like::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => Like::where('post_id', $post->id)->count(),
        ]);
    });
});

// --- TAGS & CATEGORIES (read only — NO create/update/delete) -------------

Route::get('/tags', function () {
    return Tag::orderBy('name')->get();
});

Route::get('/categories', function () {
    return Category::orderBy('name')->get();
});

Route::get('/posts/{id}/comments', function ($id) {
    $post = Post::find($id);

    if (! $post) {
        return response()->json(['message' => 'Post not found'], 404);
    }

    return $post->comments()->with('user')->orderByDesc('id')->get();
});
