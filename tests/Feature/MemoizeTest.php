<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JesseGall\LaravelMemoize\ArgumentSerializerFactoryInterface;
use JesseGall\LaravelMemoize\Memoize;
use JesseGall\LaravelMemoize\ModelAlreadyBooted;
use JesseGall\LaravelMemoize\ModelHasNoKey;
use JesseGall\LaravelMemoize\Serializers\Serializer;
use JesseGall\LaravelMemoize\Serializers\SerializerInterface;
use Orchestra\Testbench\TestCase;

class MemoizeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->integer('value');
            $table->timestamps();
            $table->temporary();
        });
    }

    public function test_InitialValueIsReturned_WhenMethodIsNotCached()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->value());
    }

    public function test_MethodCallIsCached_OnSubsequentCalls()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->value());
        $this->assertCount(1, $model->memoizeGetCache());

        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->value());
    }

    public function test_MethodCallIsCached_BasedOnArguments()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->valueWithArg('foo'));

        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(2, $model->valueWithArg('bar'));
        $this->assertEquals(1, $model->valueWithArg('foo'));
    }

    public function test_CacheIsCleared_WhenModelIsUpdated()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->value());

        $model->update(['value' => 2]);
        $this->assertEmpty($model->memoizeGetCache());
        $this->assertEquals(2, $model->value());
    }

    public function test_CacheIsCleared_WhenModelIsDeleted()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->value());

        $model->delete();
        $this->assertEmpty($model->memoizeGetCache());
    }

    public function test_InitialValueIsUniquePerModelInstance()
    {
        $model1 = TestModel::create(['value' => 1]);
        $model2 = TestModel::create(['value' => 2]);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    public function test_CachedValueIsUniquePerModelInstance()
    {
        $model1 = TestModel::create(['value' => 1]);
        $model2 = TestModel::create(['value' => 2]);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());

        $model1->updateQuietly(['value' => 3]);
        $model2->updateQuietly(['value' => 4]);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    public function test_OnlyTheModelInstanceIsAffected_WhenCacheIsCleared()
    {
        $model1 = TestModel::create(['value' => 1]);
        $model2 = TestModel::create(['value' => 2]);

        $this->assertEquals(1, $model1->value());
        $this->assertEquals(2, $model2->value());

        $model1->update(['value' => 3]);
        $model2->updateQuietly(['value' => 4]);

        $this->assertEquals(3, $model1->value());
        $this->assertEquals(2, $model2->value());
    }

    public function test_CacheIsCleared_WhenStaticClearCacheIsCalled()
    {
        $model = TestModel::create(['value' => 1]);
        $this->assertEquals(1, $model->value());

        $model->memoizeClearCache(true);
        $this->assertEmpty($model->memoizeGetCache());
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(2, $model->value());
    }

    public function test_MemoizeWorksWithComplexArguments()
    {
        $model = TestModel::create(['value' => 1]);
        $complexArg = ['foo' => 'bar', 'baz' => [1, 2, 3]];

        $this->assertEquals(1, $model->valueWithComplexArg($complexArg));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->valueWithComplexArg($complexArg));
        $this->assertEquals(2, $model->valueWithComplexArg(['different' => 'arg']));
    }

    public function test_MemoizeWorksWithModelArguments()
    {
        $target = TestModel::create(['value' => 1]);

        $model1 = TestModel::create(['value' => 2]);
        $model2 = TestModel::create(['value' => 3]);
        $model3 = TestModel::create(['value' => 4]);

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

    public function test_MemoizeWorksWithClosureArguments()
    {
        $model = TestModel::create(['value' => 1]);
        $closure = function () { return 5; };

        $this->assertEquals(6, $model->sumWithClosure($closure));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(6, $model->sumWithClosure($closure));

        $differentClosure = function () { return 10; };
        $this->assertEquals(12, $model->sumWithClosure($differentClosure));
    }

    public function test_MemoizeWorksWithNonModelClasses()
    {
        $helper = new MemoizeTestHelper();

        $this->assertEquals(1, $helper->incrementAndGet());
        $this->assertEquals(1, $helper->incrementAndGet());
        $this->assertEquals(2, $helper->incrementAndGetWithArg('foo'));
        $this->assertEquals(2, $helper->incrementAndGetWithArg('foo'));
        $this->assertEquals(3, $helper->incrementAndGetWithArg('bar'));
    }

    public function test_MemoizeWorksWithMultipleArguments()
    {
        $model = TestModel::create(['value' => 1]);

        $this->assertEquals(1, $model->valueWithMultipleArgs('foo', 'bar'));
        $model->updateQuietly(['value' => 2]);
        $this->assertEquals(1, $model->valueWithMultipleArgs('foo', 'bar'));
        $this->assertEquals(2, $model->valueWithMultipleArgs('foo', 'baz'));
    }

    public function test_MemoizeWorksWithNullArguments()
    {
        $model = TestModel::create(['value' => 1]);

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
        $model = TestModelWithCustomClearCacheOn::create(['value' => 1]);
        $model->value();
        $this->assertNotEmpty($model->memoizeGetCache());
        $model->fireCustomEvent();
        $this->assertEmpty($model->memoizeGetCache());
    }

    public function test_CanBindCustomArgumentSerializerFactory()
    {
        $this->app->bind(ArgumentSerializerFactoryInterface::class, fn() => new class implements ArgumentSerializerFactoryInterface {

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
        [$key] = array_keys($model->memoizeGetCache());
        $this->assertStringContainsString('custom-serializer', $key);
    }

}

class TestModel extends Model
{
    use Memoize;

    protected $fillable = ['value'];

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

}

class TestModelWithCustomClearCacheOn extends TestModel
{

    protected $table = 'test_models';

    public static function memoizeClearCacheOn(): array
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

    private $counter = 0;

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
}