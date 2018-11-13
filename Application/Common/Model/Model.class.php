<?php
/**
 * Created by zhou.
 * User: zhou
 * Date: 2015/11/16
 * Time: 15:51
 */

namespace Common\Model;

use Think\Exception;


/**
 * Class Query
 * @method $this order($order)
 * @method $this group($group)
 * @method $this where($where,$parse=null)
 * @method int count()
 */
abstract class Model extends \Think\Model implements QueryInterface
{
    private $_pagetion = null;

    public function pagetion($usepagetionLimit = true)
    {
        /** @noinspection PhpParamsInspection */
        $this->_pagetion = new \Think\Page($this->count(), 25);
        return $usepagetionLimit ? $this
            ->limit($this->_pagetion->firstRow . ',' . $this->_pagetion->listRows) :
            $this;
    }

    public static function q()
    {
        return new static();
    }

    /**
     * @return \Think\Page
     * @throws Exception
     */
    public function getPagetion()
    {
        if ($this->_pagetion === null) {
            throw new Exception("model pagetion is null");
        }

        return $this->_pagetion;
    }
}