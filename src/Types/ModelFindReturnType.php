<?php

namespace Weebly\PHPStan\Laravel\Types;

use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use App\Models\Sidebar;
use PHPStan\Reflection\MethodReflection;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VoidType;
use PHPStan\Type\NullType;
use Illuminate\Database\Eloquent\Collection;

class ModelFindReturnType implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Builder::class;
    }

	public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodCommonSupported($methodReflection->getName());
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodCommonSupported($methodReflection->getName());
    }

    private function isMethodCommonSupported(string $methodName): bool
    {
        return in_array($methodName, [
            'find',
            'get',
        ]);
    }

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromCommonMethodCall($methodReflection->getName(), $methodCall->args);
    }

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromCommonMethodCall($methodReflection->getName(), $methodCall->args);
    }

    private function getTypeFromCommonMethodCall(string $methodName, array $args): Type
    {
        if ($methodName === 'first') {
            return new UnionType([
                new ObjectType(Model::class),
                new NullType(),
            ]);
        } elseif ($methodName === 'find') {
            return new UnionType([
                new ObjectType(Model::class),
                new ObjectType(Collection::class),
                new NullType(),
            ]);
        } elseif ($methodName === 'get') {
            return new UnionType([
                new ObjectType(Collection::class),
            ]);
        }

        return new VoidType();
    }

}
