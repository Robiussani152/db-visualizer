# 🔍 Laravel DB Visualizer

A powerful Laravel package to analyze your application models, relationships, database usage, and detect performance issues like **N+1 queries, unused columns, and missing eager loading**.

---

## 🚀 Features

- 📊 Scan all Eloquent Models (App + Modules support)
- 🔗 Auto-detect relationships
- 🧠 Detect N+1 query risks
- ❌ Find unused columns
- ⚡ Detect missing eager loading
- 📈 Performance scoring system (0–100)
- 🧹 Code usage analysis
- 🧩 Supports Laravel Modules (nwidart style)

---

## 📦 Installation

Install via Composer:

```bash
composer require naimul/db-visualizer
````

---

## ⚙️ Auto Service Provider

If you are using Laravel 10+, the package will auto-register.

If not, add manually:

```php
Naimul\DbVisualizer\DbVisualizerServiceProvider::class,
```

---

## 🗂 Publishing Configuration

To customize the package's configuration, publish the config file with:

```bash
php artisan vendor:publish --provider="Naimul\DbVisualizer\DbVisualizerServiceProvider" --tag="dbv-config"
```

This will create a `db-visualizer.php` config file in your `config` directory.

## 🗃 Publishing Assets (Views, JS, CSS)

To publish the package's frontend resources (views, JS, CSS), run:

```bash
php artisan vendor:publish --provider="Naimul\DbVisualizer\DbVisualizerServiceProvider" --tag="dbv-resources"
```

This will copy the package's resources into your application's `resources/vendor/db-visualizer` directory for customization.




## 🔐 Authorization

Access to DB Visualizer is controlled via a `viewDbVisualizer` gate. By default, access is **only granted in the `local` environment**.

To allow access in other environments, override the gate in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Allow authenticated admins only
    Gate::define('viewDbVisualizer', fn (?User $user) => $user?->is_admin);

    // Allow specific emails
    Gate::define('viewDbVisualizer', fn (?User $user) => in_array($user?->email, [
        'admin@example.com',
    ]));

    // Allow everyone (including unauthenticated users)
    Gate::define('viewDbVisualizer', fn (?User $user) => true);
}
```

Requests that fail the gate receive a `403` response. Unauthenticated users are redirected to the login page.

> **Important:** Always declare a nullable `?User $user` parameter in your gate callback. Laravel uses this to determine whether the gate supports unauthenticated (guest) access. A callback without any parameter — such as `fn () => true` — will **not** be called for guests and will redirect them to login instead.

---

## 🌐 Routes

After installation, visit:

```
/dbv
/dbv/data
/dbv/detail/{model}
```

Example:

```
http://your-app.test/dbv/data
```

---

## 📊 API Endpoints

### Get all models analysis

```http
GET /dbv/data
```

### Search models

```http
GET /dbv/data?search=User
```

### Model details

```http
GET /dbv/detail/User
```

---

## 🧠 What It Analyzes

### ✔ Models

* Table name
* Columns
* Relations
* Soft deletes

### ✔ Relations

* Used / unused detection
* N+1 query detection
* Missing eager loading

### ✔ Columns

* Used / unused detection

### ✔ Performance Score

* Calculates score (0–100)
* Quality labels:

  * Excellent
  * Good
  * Average
  * Poor

---

## 📈 Example Response

```json
{
  "model": "User",
  "table": "users",
  "performance_score": 85,
  "quality_label": "Good Quality",
  "unused_columns_count": 2,
  "n_plus_one_issues": 1,
  "relations": [
    {
      "method": "posts",
      "type": "HasMany",
      "used": true,
      "n_plus_one": false
    }
  ]
}
```

---

## ⚡ Performance Scoring Rules

| Issue              | Penalty |
| ------------------ | ------- |
| Unused relation    | -10     |
| Unused column      | -2      |
| N+1 issue          | -15     |
| Missing eager load | -10     |

Bonus:

* Soft deletes: +5
* Cache usage: +5
* API Resource usage: +5

---

## 🛠 Requirements

* PHP >= 8.1
* Laravel 10, 11, 12 supported

---

## 📁 Supported Structure

* `app/Models`
* `Modules/*/Entities`
* `Modules/*/Models`
* Blade Views scanning

---

## 🚀 Usage Example

```php
use Naimul\DbVisualizer\Services\ModelScannerService;

$scanner = app(ModelScannerService::class);

$data = $scanner->scan();

return $data;
```

---

## 🔥 Use Case

This package is useful for:

* Optimizing large Laravel applications
* Detecting hidden performance issues
* Code quality auditing
* Refactoring legacy systems
* Interview/demo projects

---

## 🧩 Future Plans

* CLI command (`php artisan dbv:scan`)
* UI dashboard (Telescope-like)
* Graph visualization
* GitHub Action integration

---

## 🤝 Contributing

Pull requests are welcome. For major changes, please open an issue first.

---

## 📜 License

MIT License © Naimul Islam

---

## ⭐ Support

If you like this package, give it a ⭐ on GitHub.