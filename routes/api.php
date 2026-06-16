<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Models\Category;
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

// --- POSTS (read = public) ----------------------------------------------

$postMissing = fn () => response()->json(['message' => 'Post not found'], 404);
$commentMissing = fn () => response()->json(['message' => 'Comment not found'], 404);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show'])->missing($postMissing);

// --- POSTS (write = auth) -----------------------------------------------

Route::middleware('auth:sanctum')->group(function () use ($postMissing, $commentMissing) {

    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update'])->missing($postMissing);
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->missing($postMissing);

    // --- COMMENTS (create/delete = auth) --------------------------------

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->missing($postMissing);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->missing($commentMissing);

    // --- LIKES (toggle = auth) ------------------------------------------

    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->missing($postMissing);
});

// --- TAGS & CATEGORIES (read only — NO create/update/delete) -------------

Route::get('/tags', function () {
    return Tag::orderBy('name')->get();
});

Route::get('/categories', function () {
    return Category::orderBy('name')->get();
});

Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->missing($postMissing);
