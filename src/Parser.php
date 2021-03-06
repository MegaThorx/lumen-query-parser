<?php

namespace LumenQueryParser;

use Illuminate\Http\Request;
use LumenQueryParser\Action\Filter;
use LumenQueryParser\Action\Sort;
use LumenQueryParser\Action\Paginate;

class Parser
{
    public static function parse(Request $request, $model)
    {
        $model = !is_object($model) ? new $model : $model;

        if (!in_array(Queryable::class, class_uses($model))) {
            throw new QueryParserException(class_basename(model) . ' needs to use Queryable');
        }

        $data = $request->all();
        $queryBuilder = $model->query();
        
        /*
         * Check if $key and $value are strings or numbers
         * to prevent arrays or objects being injected
         */ 
        $allowedFields = $model->getQueryableFields();
        $allowedRelations = $model->getQueryableRelations();
        $limits = $model->getQueryableLimit();


        $model->querySelf($queryBuilder);

        foreach ($allowedRelations as $relation) {
            $related = $model->{$relation}()->getRelated();
            
            if (!in_array(Queryable::class, class_uses($related))) {
                throw new QueryParserException($relation . ' needs to use Queryable');
            }

            $allowedFields[$relation] = $related->getQueryableFields();
        }

        foreach ($data as $key => $value) {
            self::parseItem($queryBuilder, $allowedFields, $allowedRelations, $model, $key, $value);
        }

        
        $paginator = (new Paginate($queryBuilder))->apply($data, $limits);

        return $paginator;
    }

    private static function parseItem($queryBuilder, $allowedFields, $allowedRelations, $model, $key, $value)
    {
        if ($key === 'sort') {
            (new Sort($queryBuilder, $allowedFields, $allowedRelations))->apply($value);
        } elseif ($key === 'limit') {
            $queryBuilder->limit($value);
        } elseif ($key === 'page') {
        } elseif ($key === 'relation') {
            // TODO: move it
            $values = explode(',', $value);
            foreach ($values as $relationValue) {
                if (in_array($relationValue, $allowedRelations)) {
                    $queryBuilder->with($relationValue);
                }
            }
        } else {
            (new Filter($queryBuilder, $allowedFields, $allowedRelations))->apply($key, $value);
        }
    }
}