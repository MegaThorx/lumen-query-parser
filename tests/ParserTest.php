<?php
namespace LumenQueryParser\Tests;

use Illuminate\Http\Request;
use LumenQueryParser\Parser;

class QueryParserTest extends \Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../../../bootstrap/app.php';
    }

    /**
     * @param $requestProvider array
     * @param $expectedResult string
     *
     * @dataProvider providerTestParser
     */
    public function testParser($requestProvider, $expectedResult)
    {
        $result = $this->manageRequest($requestProvider);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param $requestProvider array
     * @dataProvider providerWithErrorsTestParser
     * @expectedException \LumenQueryParser\QueryParserException
     */
    public function testParserWithErrors($requestProvider)
    {
        $this->manageRequest($requestProvider);
    }

    public function providerTestParser()
    {
        return [
            [['sort' => '-id', 'id' => '2'], 'select * from `test` where `id` = ? order by `id` desc'],
            [['sort' => 'id', 'id' => '2,10'], 'select * from `test` where (`id` = ? or `id` = ?) order by `id` asc'],
            [['email' => 'r.lacerda83@gmail.com'], 'select * from `test` where `email` = ?'],
            [['email' => 'r.lacerda83@gmail.com', 'id' => '5'], 'select * from `test` where `email` = ? and `id` = ?'],
        ];
    }

    public function providerWithErrorsTestParser()
    {
        return [
            [['columns' => 'id', 'sort' => '-id', 'idx' => '2'], 'select * from `tester` where (`test`.`ids` = ?) order by `test`.`ids` desc'],
            [['sort' => 'idx', 'id' => '2,10'], 'select * from `teston` where (`teston`.`id` = ? or `teston`.`id` = ?) order by `teston`.`id` asc'],
            [['email2' => 'r.lacerda83@gmail.com'], 'select * from `test` where (`test`.`email2` = ?)'],
            [['to' => 'r.lacerda83@gmail.com', 'idx' => '5'], 'select * from `testao` where (`testao`.`to` = ?) and (`testao`.`idm` = ?)'],
        ];
    }

    private function manageRequest($requestProvider)
    {
        $request = new Request();
        
        foreach ($requestProvider as $key => $value) {
            $request->merge([$key => $value]);
        }

        return Parser::parse($request, TestModel::class)->toSql();
    }
}