<?php

namespace PhobosFramework\Database\Tests\Unit\QueryBuilder\Clauses;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\QueryBuilder\Clauses\JoinClause;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;

class JoinClauseTest extends TestCase {
    private JoinClause $clause;

    protected function setUp(): void {
        $this->clause = new JoinClause();
    }

    public function test_add_join_with_inner_type(): void {
        $result = $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'INNER');

        $this->assertInstanceOf(JoinClause::class, $result);
        $this->assertTrue($this->clause->hasJoins());
    }

    public function test_add_join_with_left_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'LEFT');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('LEFT JOIN', $sql);
    }

    public function test_add_join_with_right_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'RIGHT');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('RIGHT JOIN', $sql);
    }

    public function test_add_join_with_full_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'FULL');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('FULL JOIN', $sql);
    }

    public function test_add_join_with_cross_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'CROSS');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('CROSS JOIN', $sql);
    }

    public function test_add_join_with_left_outer_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'LEFT OUTER');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('LEFT OUTER JOIN', $sql);
    }

    public function test_add_join_normalizes_type_to_uppercase(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'left');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('LEFT JOIN', $sql);
    }

    public function test_add_join_trims_type(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', '  INNER  ');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('INNER JOIN', $sql);
    }

    public function test_add_join_throws_exception_for_invalid_type(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JOIN type');

        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'INVALID');
    }

    public function test_add_join_defaults_to_inner(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('INNER JOIN', $sql);
    }

    public function test_has_joins_returns_false_initially(): void {
        $this->assertFalse($this->clause->hasJoins());
    }

    public function test_to_sql_with_single_join(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'INNER');

        $sql = $this->clause->toSQL();

        $this->assertEquals('INNER JOIN users AS u ON u.id = posts.user_id', $sql);
    }

    public function test_to_sql_with_multiple_joins(): void {
        $this->clause
            ->addJoin('users', 'u', 'u.id = posts.user_id', 'INNER')
            ->addJoin('categories', 'c', 'c.id = posts.category_id', 'LEFT');

        $sql = $this->clause->toSQL();

        $expected = 'INNER JOIN users AS u ON u.id = posts.user_id LEFT JOIN categories AS c ON c.id = posts.category_id';
        $this->assertEquals($expected, $sql);
    }

    public function test_to_sql_returns_empty_string_when_no_joins(): void {
        $sql = $this->clause->toSQL();

        $this->assertEquals('', $sql);
    }

    public function test_get_joins_returns_array(): void {
        $this->clause->addJoin('users', 'u', 'u.id = posts.user_id', 'INNER');

        $joins = $this->clause->getJoins();

        $this->assertIsArray($joins);
        $this->assertCount(1, $joins);
        $this->assertEquals('users', $joins[0]['table']);
        $this->assertEquals('u', $joins[0]['alias']);
        $this->assertEquals('u.id = posts.user_id', $joins[0]['condition']);
        $this->assertEquals('INNER', $joins[0]['type']);
    }

    public function test_reset_clears_all_joins(): void {
        $this->clause
            ->addJoin('users', 'u', 'u.id = posts.user_id')
            ->addJoin('categories', 'c', 'c.id = posts.category_id');

        $result = $this->clause->reset();

        $this->assertInstanceOf(JoinClause::class, $result);
        $this->assertFalse($this->clause->hasJoins());
        $this->assertEmpty($this->clause->getJoins());
    }

    public function test_method_chaining_works(): void {
        $result = $this->clause
            ->addJoin('users', 'u', 'u.id = posts.user_id')
            ->addJoin('categories', 'c', 'c.id = posts.category_id')
            ->reset()
            ->addJoin('tags', 't', 't.id = posts.tag_id');

        $this->assertInstanceOf(JoinClause::class, $result);
        $this->assertCount(1, $this->clause->getJoins());
    }
}
