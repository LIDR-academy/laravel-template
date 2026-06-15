<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;

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

// --- POSTS (read = public) ----------------------------------------------

Route::get('/posts', function (Request $request) {
    $query = Post::query()->with(['user', 'tags', 'categories'])->withCount(['comments', 'likes']);

    // ad-hoc filters, all inline
    if ($request->has('published')) {
        $query->where('published', $request->boolean('published'));
    }

    if ($request->filled('category')) {
        $query->whereHas('categories', function ($q) use ($request) {
            $q->where('categories.id', $request->category);
        });
    }

    if ($request->filled('tag')) {
        $query->whereHas('tags', function ($q) use ($request) {
            $q->where('tags.id', $request->tag);
        });
    }

    if ($request->filled('q')) {
        $query->where('title', 'like', '%'.$request->q.'%');
    }

    return $query->orderByDesc('id')->paginate(10);
});

Route::get('/posts/{id}', function ($id) {
    $post = Post::with(['user', 'tags', 'categories', 'comments.user'])
        ->withCount('likes')
        ->find($id);

    if (! $post) {
        return response()->json(['message' => 'Post not found'], 404);
    }

    return $post;
});

// --- POSTS (write = auth) -----------------------------------------------

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/posts', function (Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        $post = new Post();
        $post->user_id = $request->user()->id;
        $post->title = $data['title'];
        $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $post->body = $data['body'];
        $post->published = $request->input('published', false);
        $post->save();

        // attach EXISTING tags / categories via pivots
        if ($request->filled('tags')) {
            $post->tags()->sync($request->tags);
        }
        if ($request->filled('categories')) {
            $post->categories()->sync($request->categories);
        }

        return response()->json($post->load(['tags', 'categories']), 201);
    });

    Route::put('/posts/{id}', function (Request $request, $id) {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // only the author can edit
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'published' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        if (isset($data['title'])) {
            $post->title = $data['title'];
        }
        if (isset($data['body'])) {
            $post->body = $data['body'];
        }
        if ($request->has('published')) {
            $post->published = $request->boolean('published');
        }
        $post->save();

        if ($request->has('tags')) {
            $post->tags()->sync($request->input('tags', []));
        }
        if ($request->has('categories')) {
            $post->categories()->sync($request->input('categories', []));
        }

        return $post->load(['tags', 'categories']);
    });

    Route::delete('/posts/{id}', function (Request $request, $id) {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'deleted']);
    });

    // --- COMMENTS (create/delete = auth) --------------------------------

    Route::post('/posts/{id}/comments', function (Request $request, $id) {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment = new Comment();
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
