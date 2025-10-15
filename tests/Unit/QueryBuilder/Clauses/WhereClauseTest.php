<?php

namespace PhobosFramework\Database\Tests\Unit\QueryBuilder\Clauses;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;

class WhereClauseTest extends TestCase {
    private WhereClause $clause;

    protected function setUp(): void {
        $this->clause = new WhereClause();
    }

    public function test_add_condition_with_string(): void {
        $this->clause->addCondition('id = ?', 'AND', 1);

        $this->assertTrue($this->clause->hasConditions());
        $sql = $this->clause->toSQL();
        $this->assertEquals('WHERE id = ?', $sql);
    }

    public function test_add_condition_with_array(): void {
        $this->clause->addCondition(['id' => 1, 'status' => 'active'], 'AND');

        $sql = $this->clause->toSQL();
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('id = ?', $sql);
        $this->assertStringContainsString('status = ?', $sql);
    }

    public function test_add_condition_with_or_operator(): void {
        $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('id = ?', 'OR', 2);

        $sql = $this->clause->toSQL();
        $this->assertStringContainsString('OR', $sql);
    }

    public function test_add_condition_with_in_operator(): void {
        $this->clause->addCondition('id IN ?', 'AND', [1, 2, 3]);

        $sql = $this->clause->toSQL();
        $bindings = $this->clause->getBindings();

        $this->assertStringContainsString('IN (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    public function test_add_condition_throws_exception_for_empty_array_in_in_clause(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IN clause cannot have an empty array');

        $this->clause->addCondition('id IN ?', 'AND', []);
    }

    public function test_add_condition_with_not_in_operator(): void {
        $this->clause->addCondition('id NOT IN ?', 'AND', [1, 2, 3]);

        $sql = $this->clause->toSQL();
        $this->assertStringContainsString('NOT IN (?, ?, ?)', $sql);
    }

    public function test_add_condition_throws_exception_for_empty_array_in_not_in_clause(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IN clause cannot have an empty array');

        $this->clause->addCondition('id NOT IN ?', 'AND', []);
    }

    public function test_get_bindings_returns_correct_values(): void {
        $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('status = ?', 'AND', 'active');

        $bindings = $this->clause->getBindings();

        $this->assertEquals([1, 'active'], $bindings);
    }

    public function test_get_bindings_with_array_condition(): void {
        $this->clause->addCondition(['id' => 1, 'status' => 'active'], 'AND');

        $bindings = $this->clause->getBindings();

        $this->assertEquals([1, 'active'], $bindings);
    }

    public function test_has_conditions_returns_false_initially(): void {
        $this->assertFalse($this->clause->hasConditions());
    }

    public function test_to_sql_returns_empty_string_when_no_conditions(): void {
        $sql = $this->clause->toSQL();

        $this->assertEquals('', $sql);
    }

    public function test_reset_clears_all_conditions(): void {
        $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('status = ?', 'AND', 'active');

        $result = $this->clause->reset();

        $this->assertInstanceOf(WhereClause::class, $result);
        $this->assertFalse($this->clause->hasConditions());
        $this->assertEmpty($this->clause->getBindings());
    }

    public function test_multiple_conditions_with_and(): void {
        $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('status = ?', 'AND', 'active')
            ->addCondition('type = ?', 'AND', 'user');

        $sql = $this->clause->toSQL();
        $bindings = $this->clause->getBindings();

        $this->assertStringContainsString('AND', $sql);
        $this->assertCount(3, $bindings);
    }

    public function test_mixed_and_or_conditions(): void {
        $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('id = ?', 'OR', 2)
            ->addCondition('status = ?', 'AND', 'active');

        $sql = $this->clause->toSQL();

        $this->assertStringContainsString('AND', $sql);
        $this->assertStringContainsString('OR', $sql);
    }

    public function test_method_chaining_works(): void {
        $result = $this->clause
            ->addCondition('id = ?', 'AND', 1)
            ->addCondition('status = ?', 'AND', 'active')
            ->reset()
            ->addCondition('type = ?', 'AND', 'admin');

        $this->assertInstanceOf(WhereClause::class, $result);
        $bindings = $this->clause->getBindings();
        $this->assertCount(1, $bindings);
    }

    public function test_array_condition_with_multiple_values(): void {
        $this->clause->addCondition([
            'id' => 1,
            'status' => 'active',
            'type' => 'user',
            'deleted' => null
        ], 'AND');

        $bindings = $this->clause->getBindings();
        $this->assertCount(4, $bindings);
    }
}
