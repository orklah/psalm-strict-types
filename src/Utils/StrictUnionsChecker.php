<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use OutOfBoundsException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psalm\NodeTypeProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
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
        $i_valuesMax = self::getMaxValues($values);
        $is_unpacked = false;
        $is_variadic = false;
        for ($i_values = 0; $i_values < $i_valuesMax; $i_values++) {
            $value_type = self::getUnionValueForPosition($values, $i_values, $node_provider, $is_unpacked);

            if ($value_type !== null) {
                try {
                    $param_type = self::getUnionParamForPosition($params, $i_values, $is_variadic);
                } catch (OutOfBoundsException $e) {
                    // No parameter left. It's okay if we have unpacked values though
                    if ($is_unpacked) {
                        break;
                    }
                    throw $e;
                }
                if ($param_type->isMixed()) {
                    //this param is not interesting because everything will pass for mixed
                    if($is_unpacked && $is_variadic) {
                        //really? What kind of monster would do that?
                        break;
                    }
                    continue;
                }

                if (!self::strictUnionCheck($param_type, $value_type)) {
                    throw NonStrictUsageException::createWithNode('Found argument ' . ($i_values + 1) . ' mismatching between param ' . $param_type->getKey() . ' and value ' . $value_type->getKey(), $expr);
                }

                if ($value_type->from_docblock === true) {
                    //not trustworthy enough
                    throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
                }

                if($is_unpacked && $is_variadic) {
                    //really? What kind of monster would do that?
                    break;
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
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList || $content instanceof Atomic\TIterable;
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

        return false;
    }

    /**
     * @param array<Arg> $values
     * @return 0|positive-int
     */
    private static function getMaxValues(array $values): int
    {
        $i_valuesMax = count($values);
        $last_value = array_pop($values);
        if ($last_value->unpack) {
            return PHP_INT_MAX;
        }

        return $i_valuesMax;
    }

    /**
     * @param array<Arg>     $values
     * @param 0|positive-int $i_values
     */
    private static function getUnionValueForPosition(array $values, int $i_values, NodeTypeProvider $node_provider, bool &$is_unpacked = false): ?Union
    {
        if (isset($values[$i_values]) && !$values[$i_values]->unpack) {
            $value = $values[$i_values];
            return $node_provider->getType($value->value);
        }

        $last_value = array_pop($values);
        if ($last_value->unpack) {
            $is_unpacked = true;
            $last_value_type = $node_provider->getType($last_value->value);

            if($last_value_type === null){
                return Type::getMixed(); // couldn't resolve type. Return the largest type
            }
            if(!$last_value_type->isSingle()){
                return Type::getMixed(); // type is a mix. Return the largest type
            }
            $atomic_types = $last_value_type->getAtomicTypes();
            $atomic_type = array_pop($atomic_types);

            $arg_type_param = null;
            if ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                $arg_type_param = $atomic_type->getGenericValueType();
            } elseif ($atomic_type instanceof Type\Atomic\TList) {
                $arg_type_param = $atomic_type->type_param;
            } elseif ($atomic_type instanceof Type\Atomic\TArray) {
                $arg_type_param = $atomic_type->type_params[1];
            } elseif ($atomic_type instanceof Type\Atomic\TIterable) {
                $arg_type_param = $atomic_type->type_params[1];
            }

            if ($arg_type_param === null) {
                $arg_type_param = Type::getMixed();
            }

            return $arg_type_param;
        }

        throw new OutOfBoundsException('No argument for position ' . ($i_values + 1) . ' and no unpack detected');
    }

    /**
     * @param array<FunctionLikeParameter> $params
     * @param int                          $i_values
     */
    private static function getUnionParamForPosition(array $params, int $i_values, bool &$is_variadic = false): Union
    {
        //TODO: beware of named params
        if (isset($params[$i_values])) {
            $param = $params[$i_values];
        } else {
            $last_param = array_pop($params);
            if ($last_param->is_variadic) {
                $is_variadic = true;
                $param = $last_param;
            } else {
                throw new OutOfBoundsException('No param for position ' . ($i_values + 1) . ' and no variadic detected');
            }
        }

        return $param->signature_type ?? Type::getMixed();
    }
}
