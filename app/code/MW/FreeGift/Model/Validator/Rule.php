<?php
/**
 * Created by PhpStorm.
 * User: lap15
 * Date: 9/14/2015
 * Time: 9:57 AM
 */

namespace MW\FreeGift\Model\Validator;


class Rule
{
    function validate($item)
    {
        $qty = $item->getQty();
        if($qty %10 != 0){
            return false;
        }

        return true;
    }

    function getQuoteItemMessage()
    {
        return "The quantity must be multiple times of 10";
    }

    function getQuoteMessage()
    {
        return "Not allowed product quantity in the cart";
    }
}