<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Models\Category;
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
$tagMissing = fn () => response()->json(['message' => 'Tag not found'], 404);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show'])->missing($postMissing);

// --- POSTS (write = auth) -----------------------------------------------

Route::middleware('auth:sanctum')->group(function () use ($postMissing, $commentMissing, $tagMissing) {

    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update'])->missing($postMissing);
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->missing($postMissing);

    // --- COMMENTS (create/delete = auth) --------------------------------

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->missing($postMissing);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->missing($commentMissing);

    // --- LIKES (toggle = auth) ------------------------------------------

    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->missing($postMissing);

    // --- TAGS (create/update/delete = auth) -----------------------------

    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update'])->missing($tagMissing);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->missing($tagMissing);
});

// --- TAGS & CATEGORIES (read = public) -----------------------------------

Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{tag}', [TagController::class, 'show'])->missing($tagMissing);

Route::get('/categories', function () {
    return Category::orderBy('name')->get();
});

Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->missing($postMissing);
