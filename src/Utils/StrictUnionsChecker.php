<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psalm\NodeTypeProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;
use function count;

class StrictUnionsChecker
{
    /**
     * @param array<Arg>                   $values
     * @param array<FunctionLikeParameter> $params
     * @throws NonStrictUsageException
     * @throws NonVerifiableStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function checkValuesAgainstParams(array $values, array $params, NodeTypeProvider $node_provider, Expr $expr): void
    {
        for ($i_values = 0, $i_valuesMax = count($values); $i_values < $i_valuesMax; $i_values++) {
            $value = $values[$i_values];

            $value_type = $node_provider->getType($value->value);

            if ($value_type !== null) {
                //TODO: beware of named params
                $param = null;
                if (!isset($params[$i_values])) {
                    // We have a value without corresponding param. We'll recursively search the last param in case of variadic
                    $i_values_tmp = $i_values - 1;
                    while ($i_values_tmp !== -1) {
                        if (!isset($params[$i_values_tmp])) {
                            $i_values_tmp--;
                            continue;
                        }
                        if ($params[$i_values_tmp]->is_variadic) {
                            $param = $params[$i_values_tmp];
                            break;
                        }
                        throw new ShouldNotHappenException('Last param found for extra value for position ' . ($i_values + 1) . ' was not a variadic');
                    }
                    if ($i_values_tmp === -1) {
                        //found a value with no corresponding param. Can happen in case of user error and in case of func_get_args usage
                        return;
                    }
                    Assert::notNull($param);
                } else {
                    $param = $params[$i_values];
                }

                $param_type = $param->signature_type ?? Type::getMixed();

                if (!self::strictUnionCheck($param_type, $value_type)) {
                    throw NonStrictUsageException::createWithNode('Found argument ' . ($i_values + 1) . ' mismatching between param ' . $param_type->getKey() . ' and value ' . $value_type->getKey(), $expr);
                }

                if ($value_type->from_docblock === true) {
                    //not trustworthy enough
                    throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
                }
            } else {
                throw new ShouldNotHappenException('Could not find value type for param ' . ($i_values + 1));
            }
        }
    }

    public static function strictUnionCheck(Union $container, Union $content): bool
    {
        //the goal here is to check that every type in $content is compatible with a type in $container in a strict way
        //we know that $container comes from signature so it can only contain php-expressible types (including union types for php 8)

        $content_types = $content->getAtomicTypes();
        $container_types = $container->getAtomicTypes();

        foreach ($content_types as $content_type) {
            $found_this_content_in_a_container = false;
            foreach ($container_types as $container_type) {
                if (self::strictTypeCheck($container_type, $content_type)) {
                    $found_this_content_in_a_container = true;
                    break;
                }
            }

            if (!$found_this_content_in_a_container) {
                //this content was not in any container, this doesn't match
                return false;
            }
        }

        return true;
    }

    private static function strictTypeCheck(Atomic $container, Atomic $content): bool
    {
        //We have to go check the type in $content and check if it belong in the $container
        if ($container instanceof Atomic\TNull) {
            return $content instanceof Atomic\TNull;
        }

        if ($container instanceof Atomic\TString) {
            return $content instanceof Atomic\TString;
        }

        if ($container instanceof Atomic\TInt) {
            return $content instanceof Atomic\TInt;
        }

        if ($container instanceof Atomic\TFloat) {
            return $content instanceof Atomic\TFloat || $content instanceof Atomic\TInt;
        }

        if ($container instanceof Atomic\TFalse) {
            return $content instanceof Atomic\TFalse;
        }

        if ($container instanceof Atomic\TBool) {
            return $content instanceof Atomic\TBool;
        }

        if ($container instanceof Atomic\TNamedObject) {
            return $content instanceof Atomic\TNamedObject;
        }

        if ($container instanceof Atomic\TObject) {
            return $content instanceof Atomic\TNamedObject || $content instanceof Atomic\TObject;
        }

        if ($container instanceof Atomic\TArray) {
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if ($container instanceof Atomic\TKeyedArray) {
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if ($container instanceof Atomic\TList) {
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if ($container instanceof Atomic\TIterable) {
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList  || $content instanceof Atomic\TIterable;
        }

        if ($container instanceof Atomic\TResource) {
            return $content instanceof Atomic\TResource;
        }

        if ($container instanceof Atomic\TCallable) {
            return $content instanceof Atomic\TCallable || $content instanceof Atomic\TCallableString || $content instanceof Atomic\TClosure || $content instanceof Atomic\TCallableArray || $content instanceof Atomic\TCallableKeyedArray || $content instanceof Atomic\TCallableList || $content instanceof Atomic\TCallableObject;
        }

        if ($container instanceof Atomic\TCallableString) {
            return $content instanceof Atomic\TCallableString;
        }

        if ($container instanceof Atomic\TClosure) {
            return $content instanceof Atomic\TClosure;
        }

        if ($container instanceof Atomic\TCallableArray) {
            return $content instanceof Atomic\TCallableArray;
        }

        if ($container instanceof Atomic\TCallableKeyedArray) {
            return $content instanceof Atomic\TCallableKeyedArray;
        }

        if ($container instanceof Atomic\TCallableList) {
            return $content instanceof Atomic\TCallableList;
        }

        if ($container instanceof Atomic\TCallableObject) {
            return $content instanceof Atomic\TCallableObject;
        }

        if ($container instanceof Atomic\TMixed) {
            return true;
        }

        return false;
    }
}
