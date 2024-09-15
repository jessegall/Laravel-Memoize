# Laravel Memoize

Laravel Memoize is a trait that provides method-level caching for your Laravel Eloquent models and other PHP classes. It
helps improve performance by caching the results of method calls for the duration of a request.

## Features

- Easy to implement with a simple trait
- Works with Eloquent models and regular PHP classes
- Supports caching based on method arguments
- Shared cache across multiple instances of the same model (same ID)
- Automatically clears cache on model updates (or custom events)

## Installation

You can install the package via Composer:

```bash
composer require jessegall/laravel-memoize
```

## Usage

1. Use the `Memoize` trait in your Eloquent model or PHP class:

```php
use JesseGall\LaravelMemoize\Memoize;

class YourModel extends Model
{
    use Memoize;

    // ... your model code
}
```

2. Wrap the logic you want to memoize with the `memoize` function:

```php
public function expensiveCalculation($param1, $param2)
{
    return $this->memoize(function() use ($param1, $param2) {
        // Your expensive calculation here
        // For example:
        // - Complex database queries
        // - API calls
        // - Resource-intensive computations
        $result = // ... 
        return $result;
    });
}
```

The result of the callback is memoized based on the arguments provided. This means that calls to the same method with
the same arguments will return the cached result, avoiding redundant expensive operations within the same request.

## Memoization and Models

When using Laravel Memoize with Eloquent models, here's how it works:

- If you use the `Memoize` trait in a model, the memoization is tied to the model's ID (primary key).
- If you pass a model as an argument to a memoized method, the model's ID is used as part of the cache key.

This means:

- Different instances of the same model (same ID) will share the same memoized results.
- Freshly queried models will use existing memoized results if available.

Here's how it looks in practice:

```php
class User extends Model
{
    use Memoize;

    public function expensiveCalculation()
    {
        return $this->memoize(function() {
            // Expensive operation here...
            return $result;
        });
    }
}

// Using Memoize in a model
$user1 = User::find(1);
$result1 = $user1->expensiveCalculation();

$user2 = User::find(1); // Different instance, same ID
$result2 = $user2->expensiveCalculation();

// $result1 and $result2 are the same, calculation done only once

// Passing a model as an argument
class Calculator
{
    use Memoize;

    public function complexCalculation(User $user)
    {
        return $this->memoize(function() use ($user) {
            // Complex calculation
            return $result;
        });
    }
}

$calculator = new Calculator();
// $result3 and $result4 are the same, calculation done only once
$result3 = $calculator->complexCalculation($user1);
$result4 = $calculator->complexCalculation($user2);
```

## Warning for Laravel Octane Users

⚠️ **Important Notice for Laravel Octane Users**

When using Laravel Memoize with Laravel Octane, be aware that memoized results persist across multiple requests handled
by the same worker. This can lead to improved performance but also risks serving stale data. Implement appropriate
cache-clearing mechanisms for frequently changing data to ensure freshness.

## How It Works

Laravel Memoize optimizes performance by caching the results of method calls. Here's what you need to know:

1. **Cache Key Generation**: The cache key is generated based on the method name and arguments. This means that
   different arguments will result in different cache keys, and the results are cached separately for each unique
   combination of arguments.
2. **Cache Lifetime**: The cached results are stored for the duration of the request. This means that the cache is
   cleared automatically at the end of each request, ensuring that the results are fresh for each new request.
3. **Cache Clearing**: The cache is cleared automatically when the model is saved or deleted. This ensures that the
   cached results are updated when the model data changes.

## When to Use Memoization

Use memoization for methods that perform time-consuming tasks like complex
calculations, database queries, or API calls, especially if these methods are called multiple times with the same
input during a request.

Example scenario where memoization is beneficial:

```php
class User extends Model
{
    use Memoize;

    public function getFullProfileData($includePrivate = false)
    {
        return $this->memoize(function() use ($includePrivate) {
            // Expensive operation: multiple database queries, API calls, etc.
            $data = $this->profile()->with('posts', 'comments', 'friends')->get();
            if ($includePrivate) {
                $data['private_info'] = $this->privateInfo()->get();
            }
            return $data;
        });
    }
}

// In your controller or service:
$user = User::find(1);

// First call - performs the expensive operation
$profile = $user->getFullProfileData(true);

// Subsequent calls - returns the cached result instantly
$sameProfile = $user->getFullProfileData(true);
$alsoSameProfile = $user->getFullProfileData(true);

// Different parameters - performs the operation again
$publicProfile = $user->getFullProfileData(false);
```

In this scenario, the expensive `getFullProfileData` method is only executed twice (once for `true` and once
for `false`), regardless of how many times it's called, significantly improving performance.

## Advanced Usage

### Clearing Cache

To manually clear the cache for a specific instance:

```php
$model->memoizeClearCache();
```

To clear the entire static cache:

```php
$model->memoizeClearCache(true);
```

### Customizing Cache Clear Events

By default, the cache is cleared on 'saved' and 'deleted' events for Eloquent models. You can customize this by
overriding the `$memoizeClearCacheOn` property:

```php
protected static array $memoizeClearCacheOn = [
    'saved',
    'deleted',
    'custom-event',
];
```

## Testing

To run the tests, use the following command:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).