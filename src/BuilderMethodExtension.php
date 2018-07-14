<?php declare(strict_types = 1);

namespace Weebly\PHPStan\Laravel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MethodReflection;
use Weebly\PHPStan\Laravel\Utils\AnnotationsHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VoidType;
use PHPStan\Type\BooleanType;
use PHPStan\Reflection\Php\NativeBuiltinMethodReflection;
use Weebly\PHPStan\Laravel\ReflectionMethodAlwaysStatic;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\Php\PhpMethodReflection;
use ReflectionMethod;

final class BuilderMethodExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * @var \PHPStan\Reflection\MethodReflection[][]
     */
    private $methods = [];

    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;

    /**
     * @var AnnotationsHelper
     */
    private $annotationsHelper;

    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        AnnotationsHelper $annotationsHelper
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->annotationsHelper = $annotationsHelper;
    }

    /**
     * @inheritdoc
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * @inheritdoc
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->isSubclassOf(Model::class)) {
            if ($methodName === 'where') {
                $phpDocParameterTypes = [
                    // 'column' => new ObjectType(Model::class),
                    // 'operator' => new ObjectType(Model::class),
                    // 'value' => new ObjectType(Model::class),
                    // 'boolean' => new ObjectType(Model::class),
                ];
                $methodReflection = new ReflectionMethod(Builder::class, 'where');
            } elseif ($methodName === 'orderBy') {
                $phpDocParameterTypes = [

                ];
                $methodReflection = new ReflectionMethod(QueryBuilder::class, 'orderBy');
            } elseif ($methodName === 'groupBy') {
                return false;
            } else {
                return false;
            }
            $this->methods[$classReflection->getName()][$methodName] = $this->createMethod(
                $classReflection,
                $methodReflection,
                $phpDocParameterTypes
            );
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->methods[$classReflection->getName()][$methodName];
    }

    private function createMethod(
        ClassReflection $classReflection,
        ReflectionMethod $methodReflection,
        array $phpDocParameterTypes
    ): PhpMethodReflection {
        return $this->methodReflectionFactory->create(
            $classReflection,
            null,
            new ReflectionMethodAlwaysStatic($methodReflection),
            $phpDocParameterTypes,
            new ObjectType(Builder::class),
            null,
            false,
            false,
            false
        );
    }
}
