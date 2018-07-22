<?php

namespace LumenQueryParser\Action;

use LumenQueryParser\QueryParserException;

class Sort 
{
    private const RELATION_DELIMITER = ':';

    private $queryBuilder;
    private $allowedFields = [];
    private $allowedRelations = [];

    public function __construct($queryBuilder, $allowedFields, $allowedRelations) {
        $this->queryBuilder = $queryBuilder;
        $this->allowedFields = $allowedFields;
        $this->allowedRelations = $allowedRelations;
    }

    public function apply($value) 
    {
        $values = explode(',', $value);

        foreach ($values as $field) {
            $prefix = substr($field, 0, 1);
            $direction = 'ASC';

            if ($prefix === '-') {
                $field = substr($field, 1, strlen($field) - 1);
                $direction = 'DESC';
            }

            if (strpos($field, self::RELATION_DELIMITER) == 0) {
                if (!in_array($field, $this->allowedFields)) {
                    throw new QueryParserException('Field ' . $field . ' is not sortable');
                }

                $this->queryBuilder->orderBy($field, $direction);
            } else {
                $relation = explode(self::RELATION_DELIMITER, $field);

                if (sizeof($relation) > 2) {
                    throw new QueryParserException('Found multiple relations for ' . $field);
                }
                $relationField = $relation[1];

                if (!in_array($relationField, $this->allowedFields[$relation[0]])) {
                    throw new QueryParserException('Field ' . $field . ' is not sortable');
                }

                // TODO: Does not work
                $this->queryBuilder->with([$relation[0] => function($query) use ($relationField, $direction){
                    $query->orderBy($relationField, $direction);
                }]);
            }
        }
    }
}