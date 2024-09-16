# Laravel Memoize

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jessegall/laravel-memoize.svg?style=flat-square)](https://packagist.org/packages/jessegall/laravel-memoize)

Laravel Memoize is a library that provides method-level caching for your Laravel Eloquent models and other PHP classes.
It improves performance by caching the results of method calls based on their arguments.

### Memoization

[Memoization](https://en.wikipedia.org/wiki/Memoization) is a technique used to cache the results of function calls and
return the cached result when the same inputs occur again.

## Features

- Easy to implement with a simple trait
- Works with Eloquent models and regular PHP classes
- Supports caching based on method arguments
- Shared cache across multiple instances of the same model
- Automatically invalidates cache on model updates (or custom events)
- Customizable cache drivers for different storage options
- Customizable argument serialization for fine-grained control

---

## Table of Contents

* [Installation](#installation)
* [Usage](#usage)
* [Memoization and Models](#memoization-and-models)
* [Memoize Drivers](#memoize-drivers)
    + [Available Drivers](#available-drivers)
    + [Using Drivers](#using-drivers)
    + [Creating a Custom Driver](#creating-a-custom-driver)
    + [Switching Drivers Dynamically](#switching-drivers-dynamically)
* [Argument Serialization](#argument-serialization)
    + [Overriding the Default Factory](#overriding-the-default-factory)
    + [Extending the Default Factory](#extending-the-default-factory)
* [Cache](#cache)
    + [Clearing Cache](#clearing-cache)
    + [Customizing Cache Invalidation Events](#customizing-cache-invalidation-events)
* [Testing](#testing)
* [Contributing](#contributing)
* [License](#license)

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

The result of the callback is memoized based on the arguments provided to the method that calls `memoize`. 
In this example, the result of `expensiveCalculation` is cached based on the values of `$param1` and `$param2`. 

## Memoization and Models

When using Laravel Memoize with Eloquent models, here's how it works:

- If you use the `Memoize` trait in a model, the memoization is tied to the model's ID (primary key).
- If you pass a model as an argument to a memoized method, the model's ID is used as part of the cache key.

This means:

- Different instances of the same model (same ID) will share the same memoized results.
- Freshly queried models will use existing cached results if available.

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

## Memoize Drivers

Laravel Memoize can use different drivers to store cached results. It comes with two built-in drivers: `MemoryDriver`
and `CacheDriver`.

### Available Drivers

1. **MemoryDriver (Default)**: Stores cached results in memory for the duration of the request.

2. **CacheDriver**: Utilizes Laravel's caching system to store memoized results. This allows the results to persist
   between different requests and to be shared among multiple servers that are connected to the same cache storage.

⚠️ **Important Notice for Laravel Octane Users**

When using the `MemoryDriver` together with Laravel Octane, be aware that memoized results persist across multiple
requests handled by the same worker. This can lead to improved performance but also risks serving stale data. Implement
appropriate cache-clearing mechanisms for frequently changing data to ensure freshness.

### Using Drivers

By default, Laravel Memoize uses the `MemoryDriver`. To use a different driver, override the `memoizeDriver` method in
your model or class:

```php
use JesseGall\LaravelMemoize\Drivers\CacheDriver;
use JesseGall\LaravelMemoize\Drivers\DriverInterface;

class YourModel extends Model
{
    use Memoize;

    public static function memoizeDriver(): DriverInterface
    {
        return new CacheDriver(ttl: 60); // Cache results for 60 seconds
    }
    
}
```

### Creating a Custom Driver

You can create your own custom driver by implementing the `DriverInterface`:

1. Create your custom driver class:

```php
use JesseGall\LaravelMemoize\Drivers\DriverInterface;

class CustomDriver implements DriverInterface
{
    // Implement the required methods
}
```

2. Use your custom driver in your model or class:

```php
class YourModel extends Model
{
    use Memoize;

    public static function memoizeDriver(): DriverInterface
    {
        return new CustomDriver();
    }
}
```

### Switching Drivers Dynamically

You can switch drivers dynamically based on certain conditions:

```php
class YourModel extends Model
{
    use Memoize;

    public static function memoizeDriver(): DriverInterface
    {
        if (config('app.env') === 'production') {
            return new CacheDriver();
        }

        return new MemoryDriver();
    }
}
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
    public function make(mixed $arg): SerializerInterface
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

## Cache

### Clearing Cache

To manually clear the cache for a specific instance:

```php
$model->memoizeForget();
```

### Customizing Cache Invalidation Events

By default, the cache of the model is cleared automatically when a model is 'saved' or deleted.
You can customize the events that trigger cache invalidation by overriding the `memoizeCacheInvalidationEvents` method:

```php
class User extends Model
{
    use Memoize;

    public static function memoizeCacheInvalidationEvents(): array
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