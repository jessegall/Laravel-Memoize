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

## Argument Serialization

Laravel Memoize uses an argument serializer to generate unique cache keys based on method arguments. You can customize
this behavior by overriding the default `ArgumentSerializerFactoryInterface` or by creating your own implementation of
the `ArgumentSerializerFactoryInterface`.

### Overriding the Default Factory

To override the default argument serializer factory, you can bind your custom implementation to
the `ArgumentSerializerFactoryInterface` in your app service provider:

```php
use App\Services\CustomArgumentSerializer;
use JesseGall\LaravelMemoize\ArgumentSerializerFactoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArgumentSerializerFactoryInterface::class, CustomArgumentSerializerFactory::class);
    }
}
```

### Extending the Default Factory

If you want to extend the default `ArgumentSerializerFactory` to add support for additional types or modify existing
serialization logic, you can create a new class that extends `ArgumentSerializerFactory`:

```php
use JesseGall\LaravelMemoize\ArgumentSerializerFactory;
use JesseGall\LaravelMemoize\Serializers\Serializer;
use App\Models\CustomModel;
use App\Services\CustomModelSerializer;

class ExtendedArgumentSerializerFactory extends ArgumentSerializerFactory
{
    public function make(mixed $arg): Serializer
    {
        if ($arg instanceof CustomModel) {
            return new CustomModelSerializer();
        }

        return parent::make($arg);
    }
}
```

Then, bind your extended serializer in your app service provider:

```php
use JesseGall\LaravelMemoize\ArgumentSerializerFactoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArgumentSerializerFactoryInterface::class, ExtendedArgumentSerializerFactory::class);
    }
}
```

By customizing the argument serializer, you can control how different types of arguments are serialized for cache key
generation, allowing for more fine-grained control over the memoization process.

## Cache Configuration

⚠️ **Important Notice for Laravel Octane Users**

When using Laravel Memoize with Laravel Octane, be aware that memoized results persist across multiple requests handled
by the same worker. This can lead to improved performance but also risks serving stale data. Implement appropriate
cache-clearing mechanisms for frequently changing data to ensure freshness.

### Clearing Cache

To manually clear the cache for a specific instance:

```php
$model->memoizeClearCache();
```

To clear the entire static cache:

```php
YourClass::memoizeClearStaticCache();
```

### Customizing Cache Clear Events

By default, the cache is cleared on 'saved' and 'deleted' events for Eloquent models. You can customize this by
overriding the `memoizeClearCacheOn` method in your model.

```php
class User extends Model
{
    use Memoize;

    public static function memoizeClearCacheOn(): array
    {
        return ['saved', 'deleted', 'custom-event'];
    }

    // ...
}
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