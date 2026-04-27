# 🔐 Laravel Auth Middleware & Gates

> A comprehensive guide to authentication middleware and authorization gates in Laravel — from basics to real-world patterns.

---

## 📚 Table of Contents

- [Overview](#overview)
- [Authentication Middleware](#authentication-middleware)
  - [What is Middleware?](#what-is-middleware)
  - [Built-in Auth Middleware](#built-in-auth-middleware)
  - [Applying Middleware to Routes](#applying-middleware-to-routes)
  - [Middleware Groups](#middleware-groups)
  - [Creating Custom Middleware](#creating-custom-middleware)
  - [Middleware Parameters](#middleware-parameters)
- [Gates](#gates)
  - [What are Gates?](#what-are-gates)
  - [Defining Gates](#defining-gates)
  - [Using Gates](#using-gates)
  - [Gates with Responses](#gates-with-responses)
  - [Gates & Policies Comparison](#gates--policies-comparison)
- [Combining Middleware & Gates](#combining-middleware--gates)
- [Common Patterns](#common-patterns)
- [Quick Reference](#quick-reference)

---

## Overview

Laravel provides two layers of access control:

| Layer | Tool | Purpose |
|---|---|---|
| **Authentication** | Middleware | *Who* is the user? Are they logged in? |
| **Authorization** | Gates & Policies | *What* can the user do? |

Middleware acts as a **gatekeeper at the route level**, while Gates provide **fine-grained action-level control** within your application logic.

---

## Authentication Middleware

### What is Middleware?

Middleware is a mechanism that filters HTTP requests entering your application. Think of it as a series of layers an HTTP request must pass through before reaching your controller.

```
HTTP Request → Middleware Stack → Controller → Response → Middleware Stack → HTTP Response
```

### Built-in Auth Middleware

Laravel ships with several auth-related middleware out of the box, registered in `app/Http/Kernel.php`:

| Middleware Alias | Class | Description |
|---|---|---|
| `auth` | `Authenticate` | Ensures user is logged in |
| `auth.basic` | `AuthenticateWithBasicAuth` | HTTP Basic Authentication |
| `auth.session` | `AuthenticateSession` | Invalidates session on password change |
| `guest` | `RedirectIfAuthenticated` | Redirects logged-in users away |
| `verified` | `EnsureEmailIsVerified` | Requires email verification |
| `can` | `Authorize` | Runs a Gate or Policy check |

### Applying Middleware to Routes

**Single route:**
```php
// routes/web.php

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

**Multiple middleware:**
```php
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'verified']);
```

**Route groups:**
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/settings', [SettingsController::class, 'edit']);
});
```

**In controllers (constructor):**
```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('guest')->only('login');
    }
}
```

### Middleware Groups

Groups bundle multiple middleware under a single key. Defined in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### Creating Custom Middleware

**Generate with Artisan:**
```bash
php artisan make:middleware EnsureUserIsAdmin
```

**Implement the handle method:**
```php
// app/Http/Middleware/EnsureUserIsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            // Option 1: Redirect
            return redirect('/home')->with('error', 'Unauthorized.');

            // Option 2: Abort with HTTP status
            // abort(403, 'Unauthorized.');
        }

        return $next($request); // Pass request to the next middleware/controller
    }
}
```

**Register it in `Kernel.php`:**
```php
// app/Http/Kernel.php

protected $middlewareAliases = [
    // ...existing entries...
    'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
];
```

**Use it on routes:**
```php
Route::get('/admin/dashboard', [AdminController::class, 'index'])
    ->middleware(['auth', 'admin']);
```

### Middleware Parameters

Middleware can accept parameters for more flexible logic:

```php
// app/Http/Middleware/EnsureUserHasRole.php

public function handle(Request $request, Closure $next, string $role): Response
{
    if (! $request->user()->hasRole($role)) {
        abort(403);
    }

    return $next($request);
}
```

Pass parameters with a colon separator:
```php
Route::get('/editor', [EditorController::class, 'index'])
    ->middleware('role:editor');

// Multiple parameters separated by commas
Route::get('/moderator', [ModController::class, 'index'])
    ->middleware('role:moderator,admin');
```

> **💡 Tip:** For multiple roles in one middleware, loop through them or use `in_array()`.

---

## Gates

### What are Gates?

Gates are **Closures** that determine whether a user is authorized to perform a given action. They are defined in `App\Providers\AuthServiceProvider` and are best for simple, model-unrelated checks.

```
Gate::define('action-name', function (User $user, ...$arguments) {
    return /* true or false */;
});
```

### Defining Gates

Open `app/Providers/AuthServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Simple gate: only admins can access admin panel
    Gate::define('access-admin-panel', function (User $user) {
        return $user->is_admin;
    });

    // Gate with a model: user can only update their own post
    Gate::define('update-post', function (User $user, Post $post) {
        return $user->id === $post->user_id;
    });

    // Gate with multiple conditions
    Gate::define('publish-post', function (User $user, Post $post) {
        return $user->id === $post->user_id
            && $user->hasVerifiedEmail()
            && $post->isDraft();
    });
}
```

**Super admin bypass (before hook):**
```php
Gate::before(function (User $user, string $ability) {
    if ($user->isSuperAdmin()) {
        return true; // Bypass all gates for super admins
    }
});
```

**After hook (fallback):**
```php
Gate::after(function (User $user, string $ability, bool|null $result, mixed $arguments) {
    if ($user->isOwner()) {
        return true;
    }
});
```

### Using Gates

**In controllers:**
```php
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    // Method 1: allows() — returns boolean
    public function update(Request $request, Post $post)
    {
        if (! Gate::allows('update-post', $post)) {
            abort(403);
        }

        // Proceed with update...
    }

    // Method 2: authorize() — throws AuthorizationException automatically
    public function destroy(Post $post)
    {
        Gate::authorize('update-post', $post);

        $post->delete();

        return redirect('/posts');
    }

    // Method 3: denies()
    public function publish(Post $post)
    {
        if (Gate::denies('publish-post', $post)) {
            return back()->with('error', 'You cannot publish this post.');
        }

        $post->publish();
    }
}
```

**Check for any/all abilities:**
```php
// User can do AT LEAST ONE of these
Gate::any(['update-post', 'delete-post'], $post);

// User can do ALL of these
Gate::check(['update-post', 'publish-post'], $post);
```

**Check for other users (not just the authenticated one):**
```php
// Check if a specific user (not necessarily the logged-in one) can do something
if (Gate::forUser($someUser)->allows('update-post', $post)) {
    // ...
}
```

**In Blade templates:**
```blade
@can('update-post', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@cannot('update-post', $post)
    <p>You don't have permission to edit this post.</p>
@endcannot

@canany(['update-post', 'delete-post'], $post)
    <div class="post-actions">...</div>
@endcanany
```

**Via the User model (via `can` / `cannot`):**
```php
$user = Auth::user();

if ($user->can('update-post', $post)) {
    // Authorized
}

if ($user->cannot('delete-post', $post)) {
    // Not authorized
}
```

**Using the `can` middleware on routes:**
```php
// Passes the Gate name and model binding
Route::put('/posts/{post}', [PostController::class, 'update'])
    ->middleware('can:update-post,post'); // 'post' refers to the route parameter
```

### Gates with Responses

Instead of returning `true`/`false`, return a `Response` for custom error messages:

```php
use Illuminate\Auth\Access\Response;

Gate::define('update-post', function (User $user, Post $post) {
    if ($user->id === $post->user_id) {
        return Response::allow();
    }

    return Response::deny('You do not own this post.');
});
```

**Inspecting the response:**
```php
$response = Gate::inspect('update-post', $post);

if ($response->allowed()) {
    // Do the action
} else {
    echo $response->message(); // "You do not own this post."
}
```

**Deny with a specific HTTP status:**
```php
return Response::denyWithStatus(404); // Returns 404 instead of 403
return Response::denyAsNotFound();    // Shorthand for 404
```

### Gates & Policies Comparison

| Feature | Gates | Policies |
|---|---|---|
| **Best for** | Simple, ad-hoc checks | Model-specific authorization |
| **Defined in** | `AuthServiceProvider` | Dedicated Policy class |
| **Structure** | Closure-based | Class with methods |
| **Scalability** | Can get cluttered | Organized and clean |
| **Example** | `Gate::define('view-reports', ...)` | `PostPolicy::update()` |

> **📝 Rule of thumb:** Use **Gates** for simple checks not tied to a specific model. Use **Policies** for actions on Eloquent models (`Post`, `User`, `Order`, etc.).

---

## Combining Middleware & Gates

A real-world setup uses both in a layered approach:

```
Request
  │
  ▼
[auth middleware] ──── Not logged in? ──→ Redirect to /login
  │
  ▼
[role middleware] ──── Wrong role? ──────→ 403 Forbidden
  │
  ▼
Controller
  │
  ▼
[Gate::authorize()] ── Doesn't own resource? → 403 Forbidden
  │
  ▼
Business Logic ✅
```

**Example implementation:**
```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
});
```

```php
// PostController.php
public function update(Request $request, Post $post)
{
    // Middleware already confirmed: user is authenticated & email is verified
    // Gate now confirms: user owns this specific post
    Gate::authorize('update-post', $post);

    $post->update($request->validated());

    return redirect()->route('posts.show', $post)->with('success', 'Post updated!');
}
```

---

## Common Patterns

### 🔒 Protect all routes in a controller
```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
}
```

### 🧑‍💼 Role-based access
```php
// Middleware approach
Route::middleware('role:admin')->group(function () {
    Route::resource('/admin/users', UserController::class);
});

// Gate approach (in AuthServiceProvider)
Gate::define('manage-users', fn(User $user) => $user->role === 'admin');
```

### 🔑 Guest-only routes
```php
// Redirect authenticated users away from login/register pages
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::get('/register', [AuthController::class, 'showRegister']);
});
```

### 📧 Email verification gate
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/premium', [PremiumController::class, 'index']);
});
```

### 🔄 Conditional UI with Gates
```blade
@auth
    @can('create', App\Models\Post::class)
        <a href="{{ route('posts.create') }}" class="btn">New Post</a>
    @endcan
@endauth
```

---

## Quick Reference

```php
// ─── MIDDLEWARE ────────────────────────────────────────────
Route::get('/path', fn() => ...)->middleware('auth');
Route::get('/path', fn() => ...)->middleware(['auth', 'verified']);
Route::middleware('auth')->group(fn() => ...);

// ─── DEFINING GATES ────────────────────────────────────────
Gate::define('gate-name', fn(User $user) => true);
Gate::define('gate-name', fn(User $user, Model $model) => $user->id === $model->user_id);
Gate::before(fn(User $user, string $ability) => $user->isAdmin() ? true : null);

// ─── CHECKING GATES ────────────────────────────────────────
Gate::allows('gate-name', $model);       // bool
Gate::denies('gate-name', $model);       // bool
Gate::authorize('gate-name', $model);    // throws 403 if denied
Gate::any(['a', 'b'], $model);           // bool
Gate::check(['a', 'b'], $model);         // bool
Gate::inspect('gate-name', $model);      // Response object

// ─── USER MODEL ────────────────────────────────────────────
$user->can('gate-name', $model);
$user->cannot('gate-name', $model);

// ─── BLADE DIRECTIVES ──────────────────────────────────────
@can('gate-name', $model) ... @endcan
@cannot('gate-name', $model) ... @endcannot
@canany(['a', 'b'], $model) ... @endcanany

// ─── ROUTE MIDDLEWARE (can) ────────────────────────────────
Route::put('/{model}', fn() => ...)->middleware('can:gate-name,model');

// ─── RESPONSES ─────────────────────────────────────────────
Gate::define('name', fn(User $u, Post $p) => $u->id === $p->user_id
    ? Response::allow()
    : Response::deny('Custom message.'));
```

---

## 📖 Further Reading

- [Laravel Docs — Middleware](https://laravel.com/docs/middleware)
- [Laravel Docs — Authorization (Gates & Policies)](https://laravel.com/docs/authorization)
- [Laravel Docs — Authentication](https://laravel.com/docs/authentication)

---

<div align="center">
  Made for Laravel learners 🚀 &nbsp;|&nbsp; Pull requests welcome!
</div>