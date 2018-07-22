<?php

namespace LumenQueryParser\Action;

class Paginate 
{
    private $queryBuilder;

    public function __construct($queryBuilder) {
        $this->queryBuilder = $queryBuilder;
    }


    public function apply($data, $limits) 
    {
        $page = 1;
        $limit = $limits['default'];

        if (isset($data['page']) && is_numeric($data['page'])) {
            $page = (int)$data['page'];
        }

        if (isset($data['limit']) && is_numeric($data['limit'])) {
            $limit = (int)$data['limit'];

            $limit = $limit < $limits['min'] ? $limits['min'] : $limit;
            $limit = $limit > $limits['max'] ? $limits['max'] : $limit;
        }

        return $this->queryBuilder->paginate($limit, ['*'], 'page', $page);
    }
}