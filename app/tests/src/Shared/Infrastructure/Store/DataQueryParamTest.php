<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure;

use Civi\Store\Filter\DataQueryFilter;
use Civi\Store\Filter\DataQueryOperator;
use Civi\Store\DataQueryParam;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;

class DataQueryParamTest extends TestCase
{
    public function test_simple_equals()
    {
        $args = [
            'filter' => ["name" => "juan", "edad" => "22"]
        ];
        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(2, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];
        $this->assertEquals('name', $cond->field());
        $this->assertEquals(DataQueryOperator::EQ, $cond->operator());
        $this->assertEquals('juan', $cond->value());
        $this->assertTrue($elements[1]->isCondition());
        $cond = $elements[1]->elements()[0];
        $this->assertEquals('edad', $cond->field());
        $this->assertEquals(DataQueryOperator::EQ, $cond->operator());
        $this->assertEquals('22', $cond->value());
    }

    public function test_like_operator()
    {
        $args = [
            'filter' => ["nameLike" => "juan"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];
        $this->assertEquals('name', $cond->field());
        $this->assertEquals(DataQueryOperator::LIKE, $cond->operator());
        $this->assertEquals('juan', $cond->value());
    }

    public function test_filter_with_complex_key_splits_and_builds_correctly()
    {
        $args = [
            'filter' => [
                'nameLikeOrAgeGreaterThan' => 'juan,22'
            ]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);

        $filter = $param->filter();
        $this->assertInstanceOf(DataQueryFilter::class, $filter);
        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);

        $orGroup = $elements[0];
        $this->assertTrue($orGroup->isOr());
        $conds = $orGroup->elements();
        $this->assertCount(2, $conds);

        /** @var DataQueryFilter $cond1 */
        $cond1 = $conds[0];
        /** @var DataQueryFilter $cond2 */
        $cond2 = $conds[1];

        $this->assertTrue($cond1->isCondition());
        $this->assertEquals('name', $cond1->elements()[0]->field());
        $this->assertEquals(DataQueryOperator::LIKE, $cond1->elements()[0]->operator());
        $this->assertEquals('juan', $cond1->elements()[0]->value());

        $this->assertTrue($cond2->isCondition());
        $this->assertEquals('age', $cond2->elements()[0]->field());
        $this->assertEquals(DataQueryOperator::GT, $cond2->elements()[0]->operator());
        $this->assertEquals('22', $cond2->elements()[0]->value());
    }

    public function test_between_operator()
    {
        $args = [
            'filter' => ["fechaBetween" => "2023-01-01,2023-01-31"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];
        $this->assertEquals('fecha', $cond->field());
        $this->assertEquals(DataQueryOperator::BETWEEN, $cond->operator());
        $this->assertEquals(['2023-01-01', '2023-01-31'], $cond->value());
    }
    public function test_in_operator()
    {
        $args = [
            'filter' => ["oficinaIn" => "1,2,3"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];
        $this->assertEquals('oficina', $cond->field());
        $this->assertEquals(DataQueryOperator::IN, $cond->operator());
        $this->assertEquals(['1', '2', '3'], $cond->value());
    }

    public function test_error_in_operator_not_the_last()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "IN" operator must be the last condition in a composite filter');

        $args = [
            'filter' => ["oficinaInAndNameLikeJuan" => "1,2,3,Juan"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $param->filter();
    }

    public function test_or_condition()
    {
        $args = [
            'filter' => ["nameLikeOrEdadGreaterThanEqual" => "juan,30"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isOr());
        $orGroup = $elements[0]->elements();

        $this->assertCount(2, $orGroup);

        $cond1 = $orGroup[0]->elements()[0];
        $this->assertEquals('name', $cond1->field());
        $this->assertEquals(DataQueryOperator::LIKE, $cond1->operator());
        $this->assertEquals('juan', $cond1->value());

        $cond2 = $orGroup[1]->elements()[0];
        $this->assertEquals('edad', $cond2->field());
        $this->assertEquals(DataQueryOperator::GTE, $cond2->operator());
        $this->assertEquals('30', $cond2->value());
    }

    public function test_ending_with()
    {
        $args = [
            'filter' => ["bussinessEmailEndingWith" => "example.com"]
        ];

        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];
        $this->assertEquals('bussinessEmail', $cond->field());
        $this->assertEquals(DataQueryOperator::ENDING_WITH, $cond->operator());
        $this->assertEquals('example.com', $cond->value());
    }

    public function test_path_equals()
    {
        $args = [
            'filter' => ["provinciaNombreEquals" => "juan"]
        ];
        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];

        $this->assertEquals('provincia.nombre', $cond->field());
        $this->assertEquals(DataQueryOperator::EQ, $cond->operator());
        $this->assertEquals('juan', $cond->value());
    }

    public function test_long_path_equals()
    {
        $args = [
            'filter' => ["provinciaPaisCountryCodeEquals" => "juan"]
        ];
        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $filter = $param->filter();

        $this->assertTrue($filter->isAnd());
        $elements = $filter->elements();
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0]->isCondition());
        $cond = $elements[0]->elements()[0];

        $this->assertEquals('provincia.pais.countryCode', $cond->field());
        $this->assertEquals(DataQueryOperator::EQ, $cond->operator());
        $this->assertEquals('juan', $cond->value());
    }


    public function test_unknow_field()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unkown field color');

        $args = [
            'filter' => ["colorEquals" => "juan"]
        ];
        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $param->filter();
    }

    public function test_path_unknow_field()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unkown field provinciaMarcado');

        $args = [
            'filter' => ["provinciaMarcadoEquals" => "juan"]
        ];
        $resource = 'Empleado';
        $schema = $this->makeTestSchema();
        $param = new DataQueryParam($schema, $resource, $args);
        $param->filter();

    }

    private function makeTestSchema(): Schema
    {
        $sdl = '
        type Query {
            empleados: [Empleado!]!
        }

        type Empleado {
            id: ID!
            name: String
            bussinessEmail: String
            edad: String
            oficina: String
            fecha: String
            age: String
            provincia: Provincia
        }
        type Provincia {
            id: ID!
            nombre: String
            pais: Pais
        }
        type Pais {
            id: ID!
            countryCode: String
        }
        ';
        return BuildSchema::build($sdl);
    }
}
