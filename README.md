# Laravel Memoize

Laravel Memoize is a trait that provides method-level caching for your Laravel Eloquent models and other PHP classes. It helps improve performance by caching the results of expensive method calls.

## Features

- Easy to implement with a simple trait
- Works with Eloquent models and regular PHP classes
- Supports caching based on method arguments
- Automatically clears cache on model updates and deletes

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

2. Wrap the method you want to memoize with the `memoize` function:

```php
public function expensiveCalculation()
{
    return $this->memoize(function() {
        // Your expensive calculation here
        return $this->someExpensiveOperation();
    });
}
```

## Examples

### Basic Usage

```php
class User extends Model
{
    use Memoize;

    public function fullName()
    {
        return $this->memoize(function() {
            return $this->first_name . ' ' . $this->last_name;
        });
    }
}
```

### Caching with Arguments

```php
public function getPostsByCategory($category)
{
    return $this->memoize(function() use ($category) {
        return $this->posts()->where('category', $category)->get();
    });
}
```

### Working with Non-Model Classes

```php
class Helper
{
    use Memoize;

    private $counter = 0;

    public function incrementAndGet()
    {
        return $this->memoize(function() {
            return ++$this->counter;
        });
    }
}
```

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

By default, the cache is cleared on 'saved' and 'deleted' events for Eloquent models. You can customize this by overriding the `$memoizeClearCacheOn` property:

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