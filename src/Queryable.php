<?php

namespace LumenQueryParser;

trait Queryable
{
    /**
     * @return array
     */
    public function getQueryableFields()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getQueryableRelations()
    {
        return [];
    }

    /**
     * @return int
     */
    public function getQueryableLimit()
    {
        return [
            'min' => 1,
            'default' => 25,
            'max' => 100
        ];
    }
}
