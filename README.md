# Employee Registration API — Laravel

Laravel দিয়ে তৈরি একটি Registration API, যা **Controller–Service–Request** আর্কিটেকচার ব্যবহার করে তৈরি করা হয়েছে। এই ডকুমেন্টে ব্যবহৃত প্যাকেজ এবং পুরো ডেভেলপমেন্ট প্রসেস ধাপে ধাপে লেখা আছে।

---

## Tech Stack

- **Framework:** Laravel 12
- **PHP:** 8.2.12
- **Database:** MySQL
- **Auth:** Laravel Sanctum (token-based API authentication)
- **Captcha:** PHP GD (built-in extension, কোনো external package লাগেনি)

---

## Installed Packages / Libraries

### 1. Laravel Sanctum
API authentication এর জন্য token-based auth সিস্টেম। JWT (`tymon/jwt-auth`) দিয়ে শুরু করা হয়েছিল, পরে Sanctum এ migrate করা হয়েছে।

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**কেন Sanctum:** DB-backed token, simple setup, Laravel এর সাথে native integration, JWT এর মতো আলাদা secret/config লাগে না।

### 2. Captcha — PHP GD Extension (কোনো Composer package লাগেনি)
প্রাথমিকভাবে `intervention/image` ব্যবহারের চেষ্টা করা হয়েছিল, কিন্তু:
- Laravel 12 + PHP 8.2 এ Intervention Image v3 তে Facade এবং `canvas()` মেথড deprecated/removed
- তাই সরাসরি PHP এর built-in **GD Extension** ব্যবহার করে captcha image generate করা হয়েছে (`imagecreatetruecolor`, `imagestring`, `imagepng` ইত্যাদি)

**Prerequisite:** `php.ini` তে `extension=gd` enable থাকতে হবে।

### 3. Laravel Core (built-in, আলাদা install লাগেনি)
- `Illuminate\Support\Facades\Cache` — captcha key/text সাময়িকভাবে store করার জন্য
- `Illuminate\Support\Facades\Hash` — password hashing এর জন্য
- `Illuminate\Support\Str` — random captcha string এবং UUID generate করার জন্য
- `Illuminate\Support\Facades\DB` — transaction handling এর জন্য

---

## Development Process (ধাপে ধাপে)

### ধাপ ১: Requirement বোঝা
Registration এ কী কী ইনফরমেশন লাগবে ঠিক করা হয়েছে:
- User Name, Employee ID, Company Code, Email, Password, Confirm Password
- Text Captcha, Terms & Conditions Checkbox

Password Rule: min 8 – max 15 characters, কমপক্ষে ১টা lowercase, ১টা uppercase, ১টা digit, ১টা special character (`-@#$%^+=`)।

### ধাপ ২: Database Design
তিনটা আলাদা টেবিল দিয়ে schema ডিজাইন করা হয়েছে:
- `companies` — company_code, name, status
- `employees` — company_id (FK), employee_id, name, status (company আগে থেকেই employee অ্যাড করে রাখবে)
- `users` — employee_id (FK, unique — one employee = one account), user_name, email, password

Migration ফাইল লিখে `php artisan migrate` দিয়ে টেবিল তৈরি করা হয়েছে।

### ধাপ ৩: Models তৈরি
`Company`, `Employee`, `User` মডেল তৈরি করে সঠিক relationship (`belongsTo`, `hasMany`, `hasOne`) সেট করা হয়েছে। `User` মডেলে `HasApiTokens` (Sanctum) trait যুক্ত করা হয়েছে।

### ধাপ ৪: Captcha System তৈরি
- `CaptchaController@generate` — random 6-character text generate করে, Cache এ ৩ মিনিটের জন্য store করে (key হিসেবে UUID), এবং GD দিয়ে image বানিয়ে base64 আকারে রিটার্ন করে
- Captcha-এর জন্য attempt-limit যুক্ত করা হয়েছে (৩ বারের বেশি ভুল দিলে captcha invalid হয়ে যায় — brute-force প্রতিরোধের জন্য)

### ধাপ ৫: Validation Layer (Form Request + Custom Rules)
- `RegisterRequest` — সব ফিল্ডের rule ডিফাইন করা (required, unique, email ইত্যাদি)
- Custom Rule ক্লাস:
  - `StrongPassword` — regex দিয়ে password policy চেক
  - `ValidEmployee` — company_code + employee_id মিলিয়ে actual employee আছে কিনা, এবং আগে থেকে registered কিনা চেক
  - `ValidCaptcha` — cache থেকে captcha মিলিয়ে verify করা, একবার ব্যবহার হলে delete করে দেওয়া (replay-attack প্রতিরোধ)

### ধাপ ৬: Service Layer
`RegisterService` — মূল business logic এখানে রাখা হয়েছে:
- DB transaction এর ভেতরে company/employee validate করে user তৈরি
- Password hash করা (`Hash::make`)
- Sanctum token generate করা (`createToken()->plainTextToken`)

### ধাপ ৭: Controller
`RegisterController@store` — শুধু `RegisterRequest` থেকে validated data নিয়ে Service কল করে এবং response ফরম্যাট করে রিটার্ন করে (thin controller, কোনো business logic নেই)।

### ধাপ ৮: Routes
```php
// routes/api.php
Route::middleware('throttle:5,1')->group(function () {
    Route::get('/captcha', [CaptchaController::class, 'generate']);
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
});
```

### ধাপ ৯: Security Hardening
- **Rate limiting** — `throttle:5,1` মিডলওয়্যার দিয়ে প্রতি IP তে মিনিটে ৫টা রিকোয়েস্ট লিমিট
- **SQL Injection** — Eloquent ORM ব্যবহারের কারণে automatic protection (raw query ব্যবহার হয়নি)
- **Mass Assignment protection** — Model এ `$fillable` এবং Service এ explicit array দিয়ে data পাঠানো (পুরো request দেওয়া হয়নি)
- **XSS protection** — user_name ফিল্ডে regex দিয়ে শুধু safe character allow করা হয়েছে
- **CORS** — `config/cors.php` এ নির্দিষ্ট origin allow করার পরিকল্পনা করা হয়েছে
- **APP_DEBUG=false** — production এ রাখার নির্দেশনা নোট করা হয়েছে

### ধাপ ১০: Frontend Testing (Blade View)
Postman দিয়ে API টেস্ট করার পাশাপাশি, ব্রাউজারে সরাসরি টেস্ট করার জন্য একটা Blade template (`resources/views/auth/register.blade.php`) বানানো হয়েছে — যেখানে `fetch()` দিয়ে `/api/captcha` এবং `/api/register` কল করা হয়, captcha image দেখানো, validation error দেখানো, এবং refresh করার ফিচার আছে।

`web.php` তে শুধু view render করার route রাখা হয়েছে, actual API logic সম্পূর্ণ `api.php` তেই আছে — Blade page শুধু browser থেকে সেই API গুলো কল করার একটা UI হিসেবে কাজ করে।

---

## Folder Structure (মূল ফাইলগুলো)

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── CaptchaController.php
│   │   └── RegisterController.php
│   └── Requests/
│       └── RegisterRequest.php
├── Models/
│   ├── Company.php
│   ├── Employee.php
│   └── User.php
├── Rules/
│   ├── StrongPassword.php
│   ├── ValidEmployee.php
│   └── ValidCaptcha.php
└── Services/
    └── RegisterService.php

database/migrations/
├── xxxx_create_companies_table.php
├── xxxx_create_employees_table.php
└── xxxx_create_users_table.php

resources/views/auth/
└── register.blade.php

routes/
├── api.php
└── web.php
```

---

## Testing Checklist

- [ ] `GET /api/captcha` → captcha image + key রিটার্ন করে কিনা
- [ ] `POST /api/register` সঠিক ডাটা দিয়ে → 201 + access_token রিটার্ন করে কিনা
- [ ] ভুল captcha দিলে → 422 error আসে কিনা
- [ ] Weak password দিলে → validation error আসে কিনা
- [ ] Duplicate email/user_name দিলে → validation error আসে কিনা
- [ ] অস্তিত্বহীন company_code/employee_id দিলে → validation error আসে কিনা
- [ ] Token দিয়ে `GET /api/user` কল করলে → user info রিটার্ন করে কিনা
- [ ] Rate limit (৫ রিকোয়েস্ট/মিনিট) কাজ করছে কিনা

---

## Future Improvements (পরবর্তী ধাপে করণীয়)

- Login endpoint যুক্ত করা
- Email verification flow
- Password reset flow
- RBAC integration (তোমার existing RBAC Service এর সাথে সংযুক্ত করা)
- Production এ GD captcha এর বদলে আরও robust captcha (Google reCAPTCHA) বিবেচনা করা