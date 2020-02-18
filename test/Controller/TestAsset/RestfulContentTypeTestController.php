<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractRestfulController;

class RestfulContentTypeTestController extends AbstractRestfulController
{
    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return array
     */
    public function update($id, $data)
    {
        return [
            'id' => $id,
            'data' => $data,
        ];
    }

    /**
     * Patch an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return array
     */
    public function patch($id, $data)
    {
        return [
            'id' => $id,
            'data' => $data,
        ];
    }
}
