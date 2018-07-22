<?php

namespace LumenQueryParser\Action;

use LumenQueryParser\QueryParserException;

class Filter 
{
    private const RELATION_DELIMITER = ':';

    protected static $allowedOperators = [
        'eq' => '=',
        'ne' => '<>',
        'gt' => '>',
        'lt' => '<',
        'ge' => '>=',
        'le' => '<=',
        'bt' => 'between',
        'nbt' => 'notBetween',
        'in' => 'in',
        'notIn' => 'notIn',
        'like' => 'like'
    ];

    private $queryBuilder;
    private $operator = '=';
    private $relatedElement = null;
    private $relatedElementFields = null;
    private $field = '';
    private $allowedFields = [];
    private $allowedRelations = [];

    public function __construct($queryBuilder, $allowedFields, $allowedRelations) {
        $this->queryBuilder = $queryBuilder;
        $this->allowedFields = $allowedFields;
        $this->allowedRelations = $allowedRelations;
    }

    public function apply($field, $value) 
    {

        $this->removeOperator($field);
        $this->checkRelation($field);
        $this->getOperator($field);
        
        if ($this->relatedElement === null) {
            if (!in_array($this->field, $this->allowedFields)) {
                throw new QueryParserException('Field ' . $this->field . ' is not filterable');
            }
            $this->applyOperator($this->queryBuilder, $value);
        } else {
            if (!in_array($this->field, $this->allowedFields[$this->relatedElement])) {
                throw new QueryParserException('Field ' . $this->field . ' is not filterable');
            }
            $this->queryBuilder->with($this->relatedElement);
            $this->queryBuilder->whereHas($this->relatedElement, function($builder) use ($value) {
                $this->applyOperator($builder, $value);
            });
        }
    }

    private function applyOperator($builder, $value) {
        $values = explode(',', $value);
        switch ($this->operator) {
            case 'between':
                if (sizeof($values) === 2) {
                    $builder->whereBetween($this->field, $values);
                } else {
                    throw new QueryParserException('Field ' . $this->field . ' is too many values for between');
                }
                break;
            case 'notBetween':
                if (sizeof($values) === 2) {
                    $builder->whereNotBetween($this->field, $values);
                } else {
                    throw new QueryParserException('Field ' . $this->field . ' is too many values for notBetween');
                }
                break;
            case 'in':
                $builder->whereIn($this->field, $values);
                break;
            case 'notIn':
                $builder->whereNotIn($this->field, $values);
                break;
            case 'like':
                $builder->where($this->field, 'like', '%' . $value . '%');
                break;
            default:
                if (sizeof($values) > 1) {
                    $builder->where(function($query) use ($values) {
                        foreach($values as $index => $val) {
                            if ($index === 0) {
                                $query->where($this->field, $this->operator, $val);
                                continue;
                            }
                            $query->orWhere($this->field, $this->operator, $val);
                        }
                    });
                } else {
                    $builder->where($this->field, $this->operator, $value);
                }
        }
    }

    private function removeOperator($field)
    {
        if (strpos($field, '{') == 0) {
            $this->field = $field;
            return;
        }

        $this->field = substr($field, 0, strpos($field, '{'));
    }    
    private function checkRelation()
    {
        if (strpos($this->field, self::RELATION_DELIMITER) == 0) {
            return;
        }
        $relation = explode(self::RELATION_DELIMITER, $this->field);

        if (sizeof($relation) > 2) {
            throw new QueryParserException('Found multiple relations for ' . $this->field);
        }

        $this->relatedElement = $relation[0];
        $this->field = $relation[1];
    }

    private function getOperator($field)
    {
        preg_match('/{(.*?)}/', $field, $match);
        if (sizeof($match) !== 0) {
            if (sizeof($match) !== 2) {
                throw new QueryParserException('Found multiple operators for ' . $field);
            }
            $operator = $match[1];

            if (isset(self::$allowedOperators[$operator])) {
                $this->operator = self::$allowedOperators[$operator];
                return;
            } else {
                throw new QueryParserException('Invalid operator \'' . $operator . '\' for ' . $this->field);
            }
        }
        $this->operator = '=';
    }    
}