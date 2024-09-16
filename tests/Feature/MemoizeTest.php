<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JesseGall\LaravelMemoize\ArgumentSerializerFactoryInterface;
use JesseGall\LaravelMemoize\Drivers\CacheDriver;
use JesseGall\LaravelMemoize\Drivers\DriverInterface;
use JesseGall\LaravelMemoize\Drivers\MemoryDriver;
use JesseGall\LaravelMemoize\Memoize;
use JesseGall\LaravelMemoize\ModelHasNoKey;
use JesseGall\LaravelMemoize\Serializers\SerializerInterface;
use Orchestra\Testbench\TestCase;

class MemoizeTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        TestModel::setDriver(fn() => new MemoryDriver());

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->integer('value');
            $table->timestamps();
            $table->temporary();
        });
    }

    public static function driverProvider(): array
    {
        return [
            'MemoryDriver' => [fn() => new MemoryDriver()],
            'CacheDriver' => [fn() => new CacheDriver()],
        ];
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_InitialValueIsReturned_WhenMethodIsNotCached(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MethodCallIsCached_OnSubsequentCalls(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->value());
        $this->assertCount(1, $model->memoizeGet());

        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MethodCallIsCached_BasedOnArguments(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->valueWithArg('foo'));

        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(2, $model->valueWithArg('bar'));
        $this->assertEquals(1, $model->valueWithArg('foo'));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_CacheIsCleared_WhenModelIsUpdated(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->value());

        $model->update(['value' => 2]);
        $this->assertEmpty($model->memoizeGet());
        $this->assertEquals(2, $model->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_CacheIsCleared_WhenModelIsDeleted(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->value());

        $model->delete();
        $this->assertEmpty($model->memoizeGet());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_InitialValueIsUniquePerModelInstance(Closure $driver)
    {
        $model1 = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);
        $model2 = TestModel::create(['value' => 2]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_CachedValueIsUniquePerModelInstance(Closure $driver)
    {
        $model1 = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);
        $model2 = TestModel::create(['value' => 2]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());

        $model1->updateQuietly(['value' => 3]);
        $model2->updateQuietly(['value' => 4]);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_OnlyTheTargetModelIsAffected_WhenCacheIsCleared(Closure $driver)
    {
        $model1 = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);
        $model2 = TestModel::create(['value' => 2]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());

        $model1->update(['value' => 3]); // This will trigger a cache clear
        $model2->updateQuietly(['value' => 4]);

        $this->assertEquals(3, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithComplexArguments(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $complexArg = ['foo' => 'bar', 'baz' => [1, 2, 3]];

        $this->assertEquals(1, $model->valueWithComplexArg($complexArg));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->valueWithComplexArg($complexArg));
        $this->assertEquals(2, $model->valueWithComplexArg(['different' => 'arg']));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithModelArguments(Closure $driver)
    {
        $target = TestModel::create(['value' => 1]);

        $model1 = TestModel::create(['value' => 2]);
        TestModel::setDriver($driver);
        $model2 = TestModel::create(['value' => 3]);
        TestModel::setDriver($driver);
        $model3 = TestModel::create(['value' => 4]);
        TestModel::setDriver($driver);

        $this->assertEquals(3, $target->sumWith($model1));
        $this->assertEquals(4, $target->sumWith($model2));
        $this->assertEquals(5, $target->sumWith($model3));

        $target->updateQuietly(['value' => 2]);

        $this->assertEquals(3, $target->sumWith($model1));
        $this->assertEquals(4, $target->sumWith($model2));
        $this->assertEquals(5, $target->sumWith($model3));

        $target->update(['value' => 3]);

        $this->assertEquals(5, $target->sumWith($model1));
        $this->assertEquals(6, $target->sumWith($model2));
        $this->assertEquals(7, $target->sumWith($model3));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithClosureArguments(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $closure = function () { return 5; };

        $this->assertEquals(6, $model->sumWithClosure($closure));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(6, $model->sumWithClosure($closure));

        $differentClosure = function () { return 10; };
        $this->assertEquals(12, $model->sumWithClosure($differentClosure));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithNonModelClasses(Closure $driver)
    {
        $helper = new MemoizeTestHelper();
        MemoizeTestHelper::setDriver($driver);

        $this->assertEquals(1, $helper->incrementAndGet());
        $this->assertEquals(1, $helper->incrementAndGet());
        $this->assertEquals(2, $helper->incrementAndGetWithArg('foo'));
        $this->assertEquals(2, $helper->incrementAndGetWithArg('foo'));
        $this->assertEquals(3, $helper->incrementAndGetWithArg('bar'));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithMultipleArguments(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->valueWithMultipleArgs('foo', 'bar'));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->valueWithMultipleArgs('foo', 'bar'));
        $this->assertEquals(2, $model->valueWithMultipleArgs('foo', 'baz'));
    }

    /**
     * @dataProvider driverProvider
     */
    public function test_MemoizeWorksWithNullArguments(Closure $driver)
    {
        $model = TestModel::create(['value' => 1]);
        TestModel::setDriver($driver);

        $this->assertEquals(1, $model->valueWithNullableArg(null));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->valueWithNullableArg(null));
        $this->assertEquals(2, $model->valueWithNullableArg('not null'));
    }

    public function test_ExceptionIsThrown_WhenMemoizingNonExistentModel()
    {
        $model = new TestModel();
        $this->expectException(ModelHasNoKey::class);
        $model->value();
    }

    public function test_ExceptionIsThrown_WhenMemoizingMethodWithNonExistingModel()
    {
        $model = TestModel::create(['value' => 1]);
        $this->expectException(ModelHasNoKey::class);
        $model->sumWith(new TestModel());
    }

    public function test_CanChangeClearCacheOnEvents()
    {
        $model = TestModelWithCustomCacheInvalidationEvents::create(['value' => 1]);
        $model->value();
        $this->assertNotEmpty($model->memoizeGet());
        $model->fireCustomEvent();
        $this->assertEmpty($model->memoizeGet());
    }

    public function test_CanBindCustomArgumentSerializerFactory()
    {
        $this->app->bind(ArgumentSerializerFactoryInterface::class,
            fn() => new class implements ArgumentSerializerFactoryInterface {

                public function make(mixed $arg): SerializerInterface
                {
                    return new class implements SerializerInterface {
                        public function serialize(mixed $arg): string
                        {
                            return 'custom-serializer';
                        }
                    };
                }

            });

        $model = TestModel::create(['value' => 1]);
        $model->valueWithArg('foo');
        [$key] = array_keys($model->memoizeGet());
        $this->assertStringContainsString('custom-serializer', $key);
    }

}

class TestModel extends Model
{
    use Memoize;

    protected $fillable = ['value'];

    protected static DriverInterface $driver;

    public function value()
    {
        return $this->memoize(fn() => $this->value);
    }

    public function valueWithArg($arg)
    {
        return $this->memoize(fn() => $this->value);
    }

    public function valueWithComplexArg($arg)
    {
        return $this->memoize(fn() => $this->value);
    }

    public function sumWith(TestModel $other)
    {
        return $this->memoize(fn() => $this->value + $other->value);
    }

    public function sumWithClosure(\Closure $closure)
    {
        return $this->memoize(fn() => $this->value + $closure());
    }

    public function valueWithMultipleArgs($arg1, $arg2)
    {
        return $this->memoize(fn() => $this->value);
    }

    public function valueWithNullableArg($arg)
    {
        return $this->memoize(fn() => $this->value);
    }

    public static function memoizeDriver(): DriverInterface
    {
        return self::$driver;
    }

    public static function setDriver(Closure $driver): void
    {
        self::$driver = $driver();
    }

}

class TestModelWithCustomCacheInvalidationEvents extends TestModel
{

    protected $table = 'test_models';

    public static function memoizeCacheInvalidationEvents(): array
    {
        return ['custom-event'];
    }

    public function fireCustomEvent(): void
    {
        $this->fireModelEvent('custom-event');
    }
}

class MemoizeTestHelper
{
    use Memoize;

    private int $counter = 0;

    protected static DriverInterface $driver;

    public function incrementAndGet()
    {
        return $this->memoize(function () {
            return ++$this->counter;
        });
    }

    public function incrementAndGetWithArg($arg)
    {
        return $this->memoize(function () {
            return ++$this->counter;
        });
    }

    public static function setDriver(Closure $driver): void
    {
        self::$driver = $driver();
    }

}