<?php
/**
 * Created by zhou.
 * User: zhou
 * Date: 2015/11/16
 * Time: 14:00
 */

namespace Common\Model;



interface QueryInterface
{
    /**
     * @return \Common\Model\Model
     */
    public static function q();
}