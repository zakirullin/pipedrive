<?php

namespace Zakirullin\Pipedrive;

class Entity
{
    /**
     * @var Pipedrive $pipedrive
     */
    protected $pipedrive;

    /**
     * @var EntityFilter
     */
    protected $entityFilter;

    /**
     * Entity constructor.
     * @param Pipedrive $pipedrive
     * @param EntityFilter $entityFilter
     */
    public function __construct($pipedrive, $entityFilter)
    {
        $this->setPipedrive($pipedrive);
        $this->setEntityFilter($entityFilter);

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
     * @return EntityFilter
     */
    public function getEntityFilter()
    {
        return $this->entityFilter;
    }

    /**
     * @param EntityFilter $entityFilter
     * @return $this
     */
    public function setEntityFilter($entityFilter)
    {
        $this->entityFilter = $entityFilter;

        return $this;
    }

    /**
     * @param EntityFilter $entity
     * @return mixed
     */
    public function create($entity)
    {
        $entity = (array)$entity;
        $entity = $this->addShortFields($entity);

        $parent = $this->entityFilter->getParent();
        while ($parent) {
            $entity[$parent->getIdField()] = $parent->getId();
            $parent = $parent->getParent();
        }

        return $this->pipedrive->process($this, 'post', $entity)->id;
    }

    // TODO exceptions
    /**
     * @param EntityFilter $entity
     * @return mixed
     * @throws \Exception
     */
    public function update($entityFilter, $entity)
    {
        $entity = (array)$entity;

        if ($entityFilter->getId() == null) {
            if (isset($entity['id'])) {
                $this->setId($entity['id']);
            } else {
                throw new \Exception('Unable to update entity without id');
            }
        }

        return $this->pipedrive->process($this, 'put', $entity);
    }

    public function delete()
    {

    }

    /**
     * @param EntityFilter $entityFilter
     * @return null|array
     */
    public function all()
    {
        $root = $this->getEntityFilter()->getRoot();
        $rootEntities = $this->getRootEntities($root);

        if (!$root->getNext()) {
            return $rootEntities;
        } else {
            $entities = [];
            while ($next = $root->getNext()) {
                $condition = $next->getCondition();
                if ($condition && !is_array($condition)) {
                    $id = $next->getCondition();
                    $entities = [$id => $rootEntities[$id]];
                } else {
                    $entities = $this->getChildEntities($rootEntities, $root->getType(), $next->getType());
                    if ($condition) {
                        $entities = $this->filter($entities, $condition);
                    }
                }

                $root = $next;
                $rootEntities = $entities;
            }

            return $entities;
        }
    }

    protected function filter(array $entities, $condition)
    {
        $filteredEntities = [];
        if ($condition && is_array($condition)) {
            foreach ($entities as $entity) {
                foreach ($condition as $field => $term) {
                    $field = $this->getField($field);
                    // TODO add exactly match
                    if ($entity->$field == $term) {
                        $filteredEntities[$entity->id] = $entity;
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

    protected function getRootEntities(EntityFilter $root)
    {
        $entities = [];
        $collect = function($entity) use (&$entities) {
            $entities[$entity->id] = $entity;
        };
        $this->getPipedrive()->walkAll($root, $collect);

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
            $parent = (new static($this->getPipedrive(), $parentType))->find($parentEntity->id);
            $newEntities = (new static($this->getPipedrive(), $childType, $parent))->all();
            foreach ($newEntities as $entity) {
                $entities[$entity->id] = $entity;
            }
        }

        return $entities;
    }



    protected function addShortFields($entity)
    {
        $isObject = is_object($entity);
        $entity = (array)$entity;
        foreach ($entity as $key => $value) {
            if ($field = $this->pipedrive->getShortField($this, $key)) {
                $entity[$field] = $value;
            }
        }

        if ($isObject) {
            $entity = (object)$entity;
        }

        return $entity;
    }

    /**
     * @param EntityFilter $entity
     * @param string $field
     * @return null|string
     */
    public function getField($field)
    {
        $fields = $this->getPipedrive()->getFields();
        $type = $this->getEntityFilter()->getType();

        return isset($fields[$type][$field]) ? $fields[$type][$field] : $field;
    }

    public function getIdField()
    {
        $idFields = $this->getPipedrive()->getIdFields();
        if (isset($idFields[$this->entityFilter->getType()])) {
            return $idFields[$this->entityFilter->getType()];
        } else {
            return $this->buildIdField();
        }
    }

    public function buildIdField()
    {
        return $this->getSingularType() . '_id';
    }

    public function buildSearchField()
    {
        return $this->getSingularType() . 'Field';
    }

    public function getSingularType()
    {
        return substr($this->getEntityFilter()->getType(), 0, -1);
    }
}