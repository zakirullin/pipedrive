<?php

namespace Zakirullin\Pipedrive\Executors;

class FindExecutor extends Executor
{
    protected function fetch()
    {
        $condition = $this->getQuery()->getCondition();
        $field = array_keys($condition)[0];
        $term = array_shift($condition);
        $this->getQuery()->setCondition($condition);
        $type = $this->getQuery()->getType();

        $entities = $this->getPipedrive()->find($type, $field, $term)->getEntities();
        foreach ($entities as &$entity) {
            $entity = $this->getPipedrive()->get($type, $entity->id)->getEntity();
        }

        return $entities;
    }
}