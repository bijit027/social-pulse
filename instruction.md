# SocialPulse — AI Build Instructions
# Social Proof Widget SaaS — Laravel + Vue.js + MySQL

---

## PROJECT OVERVIEW

Build a SaaS application called SocialPulse.
Customers embed a JavaScript snippet on their website.
The snippet shows social proof notification popups like:
"John from New York just purchased Pro Plan ⚡"

---

## TECH STACK

- Backend: Laravel 11 + MySQL + Laravel Sanctum
- Frontend Dashboard: Vue.js 3 + Vite + Vue Router + Axios
- Widget: Vanilla JavaScript (no framework, no libraries)
- Payments: Lemon Squeezy
- Backend Deploy: Railway
- Frontend Deploy: Vercel

---

## STEP 1 — CREATE LARAVEL PROJECT

```bash
composer create-project laravel/laravel backend
cd backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## STEP 2 — CREATE ALL MIGRATIONS IN THIS EXACT ORDER

### Migration 1: Modify users table
Add these columns to the default users migration:
```php
$table->string('plan')->default('trial'); // trial/starter/pro
$table->timestamp('trial_ends_at')->nullable();
$table->string('lemon_squeezy_customer_id')->nullable();
$table->string('lemon_squeezy_subscription_id')->nullable();
```

### Migration 2: Create websites table
```php
Schema::create('websites', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('domain');
    $table->string('pixel_id')->unique();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### Migration 3: Create notifications table
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('website_id')->constrained()->onDelete('cascade');
    $table->string('type')->default('purchase'); // purchase/signup/review
    $table->string('message');
    $table->string('city')->nullable();
    $table->string('country')->nullable();
    $table->string('emoji')->default('🛒');
    $table->boolean('is_active')->default(true);
    $table->integer('display_order')->default(0);
    $table->timestamps();
});
```

### Migration 4: Create notification_displays table
```php
Schema::create('notification_displays', function (Blueprint $table) {
    $table->id();
    $table->foreignId('website_id')->constrained()->onDelete('cascade');
    $table->foreignId('notification_id')->constrained()->onDelete('cascade');
    $table->string('visitor_ip')->nullable();
    $table->timestamp('displayed_at');
    $table->index(['website_id', 'displayed_at']);
});
```

Run migrations:
```bash
php artisan migrate
```

---

## STEP 3 — CREATE MODELS

### User.php
Add to existing User model:
```php
protected $fillable = [
    'name', 'email', 'password',
    'plan', 'trial_ends_at',
    'lemon_squeezy_customer_id',
    'lemon_squeezy_subscription_id'
];

public function websites()
{
    return $this->hasMany(Website::class);
}

public function isOnTrial(): bool
{
    return $this->plan === 'trial' && $this->trial_ends_at?->isFuture();
}

public function isPaid(): bool
{
    return in_array($this->plan, ['starter', 'pro']);
}

public function canAddWebsite(): bool
{
    $limits = ['trial' => 1, 'starter' => 1, 'pro' => 5];
    return $this->websites()->count() < ($limits[$this->plan] ?? 0);
}
```

### Website.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Website extends Model
{
    protected $fillable = [
        'user_id', 'name', 'domain', 'pixel_id', 'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($website) {
            $website->pixel_id = Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
```

### Notification.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'website_id', 'type', 'message',
        'city', 'country', 'emoji',
        'is_active', 'display_order'
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
```

---

## STEP 4 — CREATE FORM REQUESTS

### RegisterRequest.php
```bash
php artisan make:request RegisterRequest
```
```php
public function rules(): array
{
    return [
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ];
}
```

### LoginRequest.php
```bash
php artisan make:request LoginRequest
```
```php
public function rules(): array
{
    return [
        'email'    => 'required|email',
        'password' => 'required|string',
    ];
}
```

### StoreWebsiteRequest.php
```bash
php artisan make:request StoreWebsiteRequest
```
```php
public function rules(): array
{
    return [
        'name'   => 'required|string|max:255',
        'domain' => 'required|string|max:255',
    ];
}
```

### StoreNotificationRequest.php
```bash
php artisan make:request StoreNotificationRequest
```
```php
public function rules(): array
{
    return [
        'type'    => 'required|in:purchase,signup,review',
        'message' => 'required|string|max:255',
        'city'    => 'nullable|string|max:100',
        'country' => 'nullable|string|max:100',
        'emoji'   => 'nullable|string|max:10',
    ];
}
```

---

## STEP 5 — CREATE CONTROLLERS

### AuthController.php
```bash
php artisan make:controller AuthController
```
```php
<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'plan'          => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me()
    {
        return response()->json($this->formatUser(auth()->user()));
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'plan'           => $user->plan,
            'trial_ends_at'  => $user->trial_ends_at,
            'is_on_trial'    => $user->isOnTrial(),
            'is_paid'        => $user->isPaid(),
        ];
    }
}
```

### WebsiteController.php
```bash
php artisan make:controller WebsiteController
```
```php
<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Models\Website;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function index()
    {
        $websites = auth()->user()->websites()->withCount('notifications')->get();
        return response()->json($websites);
    }

    public function store(StoreWebsiteRequest $request)
    {
        $user = auth()->user();

        if (!$user->canAddWebsite()) {
            return response()->json([
                'message' => 'You have reached your website limit. Please upgrade.'
            ], 403);
        }

        $website = $user->websites()->create($request->validated());
        return response()->json($website, 201);
    }

    public function update(StoreWebsiteRequest $request, Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $website->update($request->validated());
        return response()->json($website);
    }

    public function destroy(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $website->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function snippet(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $snippet = '<script src="' . config('app.url') . '/widget.js" data-pixel-id="' . $website->pixel_id . '"></script>';

        return response()->json(['snippet' => $snippet]);
    }
}
```

### NotificationController.php
```bash
php artisan make:controller NotificationController
```
```php
<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use App\Models\Website;

class NotificationController extends Controller
{
    public function index(Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($website->notifications()->orderBy('display_order')->get());
    }

    public function store(StoreNotificationRequest $request, Website $website)
    {
        if ($website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification = $website->notifications()->create($request->validated());
        return response()->json($notification, 201);
    }

    public function update(StoreNotificationRequest $request, Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->update($request->validated());
        return response()->json($notification);
    }

    public function destroy(Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function toggle(Notification $notification)
    {
        if ($notification->website->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->update(['is_active' => !$notification->is_active]);
        return response()->json($notification);
    }
}
```

### WidgetController.php
```bash
php artisan make:controller WidgetController
```
```php
<?php
namespace App\Http\Controllers;

use App\Models\NotificationDisplay;
use App\Models\Website;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function serve(string $pixelId)
    {
        $website = Website::where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();

        if (!$website) {
            return response()->json(['notifications' => []]);
        }

        $notifications = $website->notifications()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'type', 'message', 'city', 'country', 'emoji']);

        return response()->json(['notifications' => $notifications]);
    }

    public function trackDisplay(Request $request, string $pixelId)
    {
        $website = Website::where('pixel_id', $pixelId)->first();
        if (!$website) return response()->json(['ok' => true]);

        NotificationDisplay::create([
            'website_id'      => $website->id,
            'notification_id' => $request->notification_id,
            'visitor_ip'      => $request->ip(),
            'displayed_at'    => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
```

---

## STEP 6 — SETUP ROUTES

Replace `routes/api.php` with:
```php
<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Public widget endpoints
Route::get('/widget/{pixelId}', [WidgetController::class, 'serve']);
Route::post('/widget/{pixelId}/display', [WidgetController::class, 'trackDisplay']);

// Auth endpoints
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Websites
    Route::get('/websites', [WebsiteController::class, 'index']);
    Route::post('/websites', [WebsiteController::class, 'store'])->middleware('throttle:10,1');
    Route::put('/websites/{website}', [WebsiteController::class, 'update']);
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy']);
    Route::get('/websites/{website}/snippet', [WebsiteController::class, 'snippet']);

    // Notifications
    Route::get('/websites/{website}/notifications', [NotificationController::class, 'index']);
    Route::post('/websites/{website}/notifications', [NotificationController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/notifications/{notification}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::patch('/notifications/{notification}/toggle', [NotificationController::class, 'toggle']);
});
```

---

## STEP 7 — SETUP CORS

Replace `config/cors.php` with:
```php
<?php
return [
    'paths'               => ['api/*'],
    'allowed_methods'     => ['*'],
    'allowed_origins'     => [
        'http://localhost:5173',
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'     => ['*'],
    'exposed_headers'     => [],
    'max_age'             => 0,
    'supports_credentials' => true,
];
```

---

## STEP 8 — CREATE VUE.JS FRONTEND

```bash
npm create vite@latest frontend -- --template vue
cd frontend
npm install
npm install vue-router@4 axios @vueuse/core
```

### Create these pages in `src/pages/`:

1. `Login.vue` — email + password form, call POST /api/login
2. `Register.vue` — name + email + password form, call POST /api/register
3. `Dashboard.vue` — list all websites with notification count
4. `WebsiteDetail.vue` — show notifications for a website, copy snippet button
5. `Settings.vue` — user profile and plan info

### Create `src/services/api.js`:
```javascript
import axios from 'axios'
import router from './router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

export default api
```

### Create `src/router/index.js`:
```javascript
import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import Dashboard from '../pages/Dashboard.vue'
import WebsiteDetail from '../pages/WebsiteDetail.vue'
import Settings from '../pages/Settings.vue'

const routes = [
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  {
    path: '/',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/websites/:id',
    component: WebsiteDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/settings',
    component: Settings,
    meta: { requiresAuth: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) return '/login'
  if ((to.path === '/login' || to.path === '/register') && token) return '/'
})

export default router
```

---

## STEP 9 — CREATE THE JAVASCRIPT WIDGET

Create file `backend/public/widget.js`:
```javascript
(function () {
  var script = document.currentScript;
  var pixelId = script.getAttribute('data-pixel-id');
  var apiUrl = script.src.replace('/widget.js', '');

  if (!pixelId) return;

  fetch(apiUrl + '/api/widget/' + pixelId)
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.notifications || !data.notifications.length) return;
      initWidget(data.notifications, apiUrl, pixelId);
    })
    .catch(function () {});

  function initWidget(notifications, apiUrl, pixelId) {
    var index = 0;

    var container = document.createElement('div');
    container.style.cssText = 'position:fixed;bottom:20px;left:20px;z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:300px;display:none;';
    document.body.appendChild(container);

    function show() {
      var n = notifications[index % notifications.length];
      index++;

      container.innerHTML =
        '<div style="background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;animation:sp-slide-in 0.3s ease;">' +
        '<span style="font-size:24px;">' + (n.emoji || '🛒') + '</span>' +
        '<div style="flex:1;">' +
        '<div style="font-size:13px;font-weight:600;color:#1a1a1a;line-height:1.4;">' + n.message + '</div>' +
        (n.city ? '<div style="font-size:11px;color:#888;margin-top:2px;">' + n.city + (n.country ? ', ' + n.country : '') + '</div>' : '') +
        '</div>' +
        '<button onclick="this.closest(\'div\').parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;color:#ccc;font-size:18px;padding:0;line-height:1;">×</button>' +
        '</div>';

      container.style.display = 'block';

      fetch(apiUrl + '/api/widget/' + pixelId + '/display', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: n.id }),
      }).catch(function () {});

      setTimeout(function () {
        container.style.display = 'none';
        setTimeout(show, 8000);
      }, 5000);
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes sp-slide-in{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
    document.head.appendChild(style);

    setTimeout(show, 3000);
  }
})();
```

---

## STEP 10 — DEPLOY

### Backend to Railway:
1. Push backend to GitHub
2. Connect Railway to GitHub repo
3. Set root directory to `backend`
4. Add environment variables
5. Add MySQL service
6. Wire DB variables

### Frontend to Vercel:
1. Push frontend to GitHub
2. Connect Vercel to GitHub repo
3. Set root directory to `frontend`
4. Add `VITE_API_URL` environment variable
5. Deploy

---

## ENVIRONMENT VARIABLES NEEDED

### Backend
```
APP_NAME=SocialPulse
APP_ENV=production
APP_KEY=
APP_URL=https://your-backend.railway.app
FRONTEND_URL=https://your-frontend.vercel.app
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=socialpulse
DB_USERNAME=
DB_PASSWORD=
LEMON_SQUEEZY_API_KEY=
LEMON_SQUEEZY_STORE_ID=
LEMON_SQUEEZY_WEBHOOK_SECRET=
```

### Frontend
```
VITE_API_URL=https://your-backend.railway.app/api
```

---

## DONE CHECKLIST

```
[x] All migrations created and run
[ ] Auth endpoints working (test with Postman)
[ ] Website CRUD working
[ ] Notification CRUD working
[ ] Widget endpoint returns notifications JSON
[ ] widget.js shows popup on a test page
[ ] Vue.js dashboard login works
[ ] Dashboard shows websites list
[ ] Snippet copy button works
[ ] Notifications can be added and toggled
[ ] Deployed to Railway and Vercel
[ ] Widget works on live deployment
```