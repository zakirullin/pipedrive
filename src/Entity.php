<?php

namespace Zakirullin\Pipedrive;

class Entity
{
    /**
     * @var EntityQuery
     */
    protected $entityQuery;

    /**
     * @var Pipedrive
     */
    protected $pipedrive;

    /**
     * Entity constructor.
     * @param Pipedrive $pipedrive
     * @param EntityQuery $entityFilter
     */
    public function __construct($entityQuery)
    {
        $this->setEntityQuery($entityQuery);
        $this->setPipedrive($entityQuery->getPipedrive());

        return $this;
    }
    
    /**
     * @return EntityQuery
     */
    public function getEntityQuery()
    {
        return $this->entityQuery;
    }

    /**
     * @param EntityQuery $entityQuery
     * @return $this
     */
    public function setEntityQuery($entityQuery)
    {
        $this->entityQuery = $entityQuery;

        return $this;
    }

    /**
     * @return Pipedrive
     */
    public function getPipedrive()
    {
        return $this->pipedrive;
    }

    /**
     * @param Pipedrive $pipedrive
     * @return $this
     */
    public function setPipedrive($pipedrive)
    {
        $this->pipedrive = $pipedrive;
        
        return $this;
    }

    /**
     * @param EntityQuery $entity
     * @return integer
     */
    public function create($entity)
    {
        $entity = (array)$entity;
        $entity = $this->addLongFields($entity);

        return $this->getPipedrive()->process($this->getEntityQuery(), 'post', $entity)->id;

        return $id;
    }

    // TODO exceptions
    /**
     * @param EntityQuery $entity
     * @return mixed
     * @throws \Exception
     */
    public function update($entity)
    {
        $entity = (array)$entity;
        $this->addLongFields($entity);

        if ($this->getEntityQuery()->getId() == null) {
            if (isset($entity['id'])) {
                $this->getEntityQuery()->setCondition($entity['id']);
            }
        }

        if ($id = $this->getEntityQuery()->getId()) {
            return $this->getPipedrive()->process($this->getEntityQuery(), 'put', $entity)->id;
        } else if ($condition = $this->getEntityQuery()->getCondition() || $this->getEntityQuery()->getPrev()) {
            // TODO if no condition
            $ids = [];
            $entities = $this->all();
            foreach ($entities as $id => $value) {
                $type = $this->getEntityQuery()->getType();
                $ids[] = current($this->getPipedrive()->$type->find($id)->update($entity));
            }

            return $ids;
        } else {
            throw new \Exception("Entity can't be update without id");
        }
    }

    public function delete()
    {

    }

    public function findAll($condition = null)
    {
        $this->getEntityQuery()->setCondition($condition);

        return $this->all();
    }

    public function walkAll($callback)
    {
        $this->getPipedrive()->walkAll($this->getEntityQuery(), $callback);
    }

    /**
     * @param EntityQuery $entityQuery
     * @return array|null
     */
    public function all()
    {
        $root = $this->getEntityQuery()->getRoot();
        $rootEntities = $this->getRootEntities($root);

        if (!$root->getNext()) {
            return $rootEntities;
        } else if ($rootEntities) {
            $entities = [];
            while ($next = $root->getNext()) {
                if ($id = $next->getId()) {
                    $entities = [$id => $rootEntities[$id]];
                } else {
                    $entities = $this->getChildEntities($rootEntities, $root->getType(), $next->getType());
                    if ($entities) {
                        $entities = $this->filter($entities, $next->getCondition());
                    }
                }

                $root = $next;
                $rootEntities = $entities;
            }

            return $entities;
        }

        return [];
    }

    public function findOne($condition)
    {
        $this->getEntityQuery()->setCondition($condition);

        return $this->one();
    }

    public function one()
    {
        $entities = $this->all();

        return current($entities);
    }

    protected function filter(array $entities, $condition)
    {
        $filteredEntities = [];
        if ($condition && is_array($condition)) {
            foreach ($entities as $entity) {
                foreach ($condition as $field => $term) {
                    // TODO add exactly match
                    $value = $entity->$field;
                    if (is_array($value)) {
                        foreach ($value as $object) {
                            if (is_object($object) && isset($object->value)) {
                                if ($object->value == $term) {
                                    $filteredEntities[$entity->id] = $entity;
                                    break;
                                }
                            }
                        }
                    } else {
                        if ($value == $term) {
                            $filteredEntities[$entity->id] = $entity;
                        }
                    }
                }
            }

        } else if ($condition) {
            foreach ($entities as $entity) {
                if ($entity->id == $condition) {
                    $filteredEntities[] = $entity;
                }
            }
        } else {
            $filteredEntities = $entities;
        }

        return $filteredEntities;
    }

    protected function getRootEntities(EntityQuery $root)
    {
        $entities = [];
        $collect = function($entity) use (&$entities) {
            if ($entity) {
                $entities[$entity->id] = (object)$this->addShortFields((array)$entity);
            }
        };
        $this->getPipedrive()->walkAll($root, $collect);

        $mustRetrieve = is_array($root->getCondition()) && !$root->getNext();
        if ($mustRetrieve) {
            foreach ($entities as $id => $entity) {
                $pipedrive = $this->getPipedrive();
                $type = $root->getType();
                $entities[$id] = $pipedrive->$type->findOne($id);
            }
        }

        $condition = $root->getCondition();
        if (is_array($condition)) {
            $firstTermIsProcessedByPipedrive = !$root->getPrev();
            if ($firstTermIsProcessedByPipedrive) {
                array_shift($condition);
            }
            $entities = $this->filter($entities, $condition);
        }

        return $entities;
    }

    protected function getChildEntities(array $parentEntities, $parentType, $childType)
    {
        $entities = [];
        foreach ($parentEntities as $parentEntity) {
            $pipedrive = $this->getPipedrive();
            $newEntities = $pipedrive->$parentType->find($parentEntity->id)->$childType->all();
            foreach ($newEntities as $entity) {
                $entities[$entity->id] = $entity;
            }
        }

        return $entities;
    }

    /**
     * @param array $entity
     * @return mixed
     */
    protected function addShortFields($entity)
    {
        foreach ($entity as $key => $value) {
            $field = $this->getPipedrive()->getFieldByHash($this->getEntityQuery()->getType(), $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return $entity;
    }

    /**
     * @param array $entity
     * @return mixed
     */
    protected function addLongFields($entity)
    {
        foreach ($entity as $key => $value) {
            $field = $this->getPipedrive()->getFieldHash($this->getEntityQuery()->getType(), $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return $entity;
    }
}