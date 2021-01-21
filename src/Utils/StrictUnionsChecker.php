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
    public static function checkValuesAgainstParams(array $values, array $params, NodeTypeProvider $node_provider, Expr $expr, bool $check_against_phpdoc = false): void
    {
        for ($i_values = 0, $i_valuesMax = count($values); $i_values < $i_valuesMax; $i_values++) {
            $value = $values[$i_values];

            $value_type = $node_provider->getType($value->value);

            if ($value_type !== null) {
                //TODO: beware of named params
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
                        throw new ShouldNotHappenException('No param found for extra value for position ' . ($i_values + 1));
                    }
                } else {
                    $param = $params[$i_values];
                }

                if ($check_against_phpdoc) {
                    // if the function is from the stubs, the location of the type is not relevant
                    $param_type = $param->signature_type ?? $param->type ?? Type::getMixed();
                } else {
                    $param_type = $param->signature_type ?? Type::getMixed();
                }

                if (!StrictUnionsChecker::strictUnionCheck($param_type, $value_type)) {
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

        if ($container instanceof Atomic\TMixed) {
            return true;
        }

        return false;
    }
}
