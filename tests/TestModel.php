<?php

namespace LumenQueryParser\Tests;

use Illuminate\Database\Eloquent\Model;
use LumenQueryParser\Queryable;

class TestModel extends Model
{
    use Queryable;

    public function getTable()
    {
        return 'test';
    }

    /**
     * @return array
     */
    public function getQueryableFields()
    {
        return ['id', 'name', 'email'];
    }
}