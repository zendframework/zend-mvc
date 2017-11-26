<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Mvc\Controller\AbstractRestfulController;

class RestfulTestController extends AbstractRestfulController
{
    public $entities = [];
    public $entity   = [];

    /**
     * @var ResponseInterface|null
     */
    public $headResponse;

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data)
    {
        return ['entity' => $data];
    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->entity = [];
        return [];
    }

    /**
     * Delete the collection
     *
     * @return ResponseInterface
     */
    public function deleteList($data)
    {
        if (is_array($this->entity)) {
            foreach ($data as $row) {
                foreach ($this->entity as $index => $entity) {
                    if ($row['id'] == $entity['id']) {
                        unset($this->entity[$index]);
                        break;
                    }
                }
            }
        }

        $response = $this->getResponse() ?? new Response();
        $response = $response->withStatus(204)
            ->withAddedHeader('X-Deleted', 'true');

        return $response;
    }

    /**
     * Return single resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        return ['entity' => $this->entity];
    }

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList()
    {
        return ['entities' => $this->entities];
    }

    /**
     * Retrieve the headers for a given resource
     */
    public function head($id = null)
    {
        if ($id) {
            $this->getEvent()->setResponse(
                ($this->getResponse() ?? new Response())->withAddedHeader('X-ZF2-Id', $id)
            );
        }

        if ($this->headResponse) {
            return $this->headResponse;
        }
    }

    /**
     * Return list of allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function options()
    {
        $response = $this->getResponse() ?? new Response();
        $response = $response->withAddedHeader('Allow', 'GET, POST, PUT, DELETE, PATCH, HEAD, TRACE');
        return $response;
    }

    /**
     * Patch (partial update) an entity
     *
     * @param  int $id
     * @param  array $data
     * @return array
     */
    public function patch($id, $data)
    {
        $entity     = (array) $this->entity;
        $data['id'] = $id;
        $updated    = array_merge($entity, $data);
        return ['entity' => $updated];
    }

    /**
     * Replace the entire resource collection
     *
     * @param  array|\Traversable $items
     * @return array|\Traversable
     */
    public function replaceList($items)
    {
        return $items;
    }

    /**
     * Modify an entire resource collection
     *
     * @param  array|\Traversable $items
     * @return array|\Traversable
     */
    public function patchList($items)
    {
        //This isn't great code to have in a test class, but I seems the simplest without BC breaks.
        if (isset($items['name'])
            && $items['name'] == 'testDispatchViaPatchWithoutIdentifierReturns405ResponseIfPatchListThrowsException'
        ) {
            parent::patchList($items);
        }
        return $items;
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data)
    {
        $data['id'] = $id;
        return ['entity' => $data];
    }

    public function editAction()
    {
        return ['content' => __FUNCTION__];
    }

    public function testSomeStrangelySeparatedWordsAction()
    {
        return ['content' => 'Test Some Strangely Separated Words'];
    }

    public function describe()
    {
        return ['description' => __METHOD__];
    }
}
