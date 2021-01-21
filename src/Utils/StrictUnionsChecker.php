<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

class StrictUnionsChecker
{
    public static function strictUnionCheck(Union $container, Union $content): bool{
        //the goal here is to check that every type in $content is compatible with a type in $container in a strict way
        //we know that $container comes from signature so it can only contain php-expressible types (including union types for php 8)

        $content_types = $content->getAtomicTypes();
        $container_types = $container->getAtomicTypes();

        foreach($content_types as $content_type){
            $found_this_content_in_a_container = false;
            foreach($container_types as $container_type){
                if(self::strictTypeCheck($container_type, $content_type)){
                    $found_this_content_in_a_container = true;
                    break;
                }
            }

            if(!$found_this_content_in_a_container){
                //this content was not in any container, this doesn't match
                return false;
            }
        }

        return true;
    }

    private static function strictTypeCheck(Atomic $container, Atomic $content): bool{
        //We have to go check the type in $content and check if it belong in the $container
        if($container instanceof Atomic\TNull){
            return $content instanceof Atomic\TNull;
        }

        if($container instanceof Atomic\TString){
            return $content instanceof Atomic\TString;
        }

        if($container instanceof Atomic\TInt){
            return $content instanceof Atomic\TInt;
        }

        if($container instanceof Atomic\TFloat){
            return $content instanceof Atomic\TFloat || $content instanceof Atomic\TInt;
        }

        if($container instanceof Atomic\TFalse){
            return $content instanceof Atomic\TFalse;
        }

        if($container instanceof Atomic\TBool){
            return $content instanceof Atomic\TBool;
        }

        if($container instanceof Atomic\TNamedObject){
            return $content instanceof Atomic\TNamedObject;
        }

        if($container instanceof Atomic\TObject){
            return $content instanceof Atomic\TNamedObject || $content instanceof Atomic\TObject;
        }

        if($container instanceof Atomic\TArray){
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if($container instanceof Atomic\TKeyedArray){
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if($container instanceof Atomic\TList){
            return $content instanceof Atomic\TArray || $content instanceof Atomic\TKeyedArray || $content instanceof Atomic\TList;
        }

        if($container instanceof Atomic\TMixed){
            return true;
        }

        return false;
    }
}
