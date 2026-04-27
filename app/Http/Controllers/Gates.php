<?php

/*
|--------------------------------------------------------------------------
| Laravel Gates - Complete Code Examples
|--------------------------------------------------------------------------
| This file contains examples of:
| 1. Gate definitions
| 2. Gate with model
| 3. Gate with additional parameters
| 4. Using Gate in routes
| 5. Using Gate in controllers
| 6. Using Gate in Blade
| 7. Authorize resource actions
| 8. Allow / Deny responses
*/

namespace App\Providers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Access\Response;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Simple Gate
        Gate::define('is-admin', function (User $user) {
            return $user->role === 'admin';
        });

        // Gate with model
        Gate::define('update-post', function (User $user, Post $post) {
            return $user->id === $post->user_id;
        });

        // Gate with Response
        Gate::define('delete-post', function (User $user, Post $post) {
            return $user->id === $post->user_id
                ? Response::allow()
                : Response::deny('You do not own this post.');
        });

        // Gate with additional parameter
        Gate::define('publish-post', function (User $user, Post $post, string $category) {
            return $user->role === 'admin' || $category === 'general';
        });
    }
}

/*
|--------------------------------------------------------------------------
| Routes Example
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Models\Post;

Route::get('/admin', function () {
    if (Gate::allows('is-admin')) {
        return 'Welcome Admin';
    }

    abort(403);
});

Route::get('/post/{post}/edit', function (Post $post) {
    Gate::authorize('update-post', $post);

    return 'Edit Post Page';
});

Route::delete('/post/{post}', function (Post $post) {
    Gate::authorize('delete-post', $post);

    $post->delete();

    return 'Post Deleted';
});

/*
|--------------------------------------------------------------------------
| Middleware Usage
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return 'Admin Dashboard';
})->middleware('can:is-admin');

Route::get('/posts/{post}/edit', function (Post $post) {
    return 'Edit Post';
})->middleware('can:update-post,post');

/*
|--------------------------------------------------------------------------
| Controller Example
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function edit(Post $post)
    {
        Gate::authorize('update-post', $post);

        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update-post', $post);

        $post->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete-post', $post);

        $post->delete();

        return back();
    }
}

/*
|--------------------------------------------------------------------------
| Blade Examples
|--------------------------------------------------------------------------
*/
?>

@can('is-admin')
    <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
@endcan

@cannot('is-admin')
    <p>You are not an admin.</p>
@endcannot

@can('update-post', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@can('delete-post', $post)
    <form action="{{ route('posts.destroy', $post) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endcan

@canany(['update-post', 'delete-post'], $post)
    <p>You can manage this post.</p>
@endcanany

<?php

/*
|--------------------------------------------------------------------------
| Manual Gate Checks
|--------------------------------------------------------------------------
*/

if (Gate::allows('is-admin')) {
    // Allowed
}

if (Gate::denies('is-admin')) {
    // Denied
}

if (Gate::check('is-admin')) {
    // Allowed
}

if (Gate::any(['is-admin', 'update-post'], $post)) {
    // Any one permission is enough
}

if (Gate::none(['is-admin', 'update-post'], $post)) {
    // No permissions granted
}

$response = Gate::inspect('delete-post', $post);

if ($response->allowed()) {
    // Allowed
}

echo $response->message();

/*
|--------------------------------------------------------------------------
| Before / After Hooks
|--------------------------------------------------------------------------
*/

Gate::before(function (User $user, string $ability) {
    if ($user->role === 'super-admin') {
        return true;
    }
});

Gate::after(function (User $user, string $ability, ?bool $result, array $arguments) {
    // Log authorization result
});
