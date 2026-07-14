<?php

namespace PhobosFramework\Database\Tests\Unit\QueryBuilder\Clauses;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\QueryBuilder\Clauses\LimitClause;
use PhobosFramework\Database\QueryBuilder\Grammar\AnsiGrammar;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;

class LimitClauseTest extends TestCase {
    private LimitClause $clause;
    private AnsiGrammar $grammar;

    protected function setUp(): void {
        $this->clause = new LimitClause();
        $this->grammar = new AnsiGrammar();
    }

    public function test_set_limit_accepts_valid_value(): void {
        $result = $this->clause->setLimit(10);

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertTrue($this->clause->hasLimit());
        $this->assertEquals(10, $this->clause->getLimit());
    }

    public function test_set_limit_accepts_zero(): void {
        $result = $this->clause->setLimit(0);

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertEquals(0, $this->clause->getLimit());
    }

    public function test_set_limit_throws_exception_for_negative_value(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LIMIT must be a non-negative integer');

        $this->clause->setLimit(-1);
    }

    public function test_set_offset_accepts_valid_value(): void {
        $result = $this->clause->setOffset(20);

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertTrue($this->clause->hasOffset());
        $this->assertEquals(20, $this->clause->getOffset());
    }

    public function test_set_offset_accepts_zero(): void {
        $result = $this->clause->setOffset(0);

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertEquals(0, $this->clause->getOffset());
    }

    public function test_set_offset_throws_exception_for_negative_value(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OFFSET must be a non-negative integer');

        $this->clause->setOffset(-1);
    }

    public function test_has_limit_returns_false_initially(): void {
        $this->assertFalse($this->clause->hasLimit());
    }

    public function test_has_offset_returns_false_initially(): void {
        $this->assertFalse($this->clause->hasOffset());
    }

    public function test_to_sql_with_limit_only(): void {
        $this->clause->setLimit(10);

        $sql = $this->clause->toSQL($this->grammar);

        $this->assertEquals('LIMIT 10', $sql);
    }

    public function test_to_sql_with_offset_only(): void {
        $this->clause->setOffset(20);

        $sql = $this->clause->toSQL($this->grammar);

        $this->assertEquals('OFFSET 20', $sql);
    }

    public function test_to_sql_with_both_limit_and_offset(): void {
        $this->clause->setLimit(10)->setOffset(20);

        $sql = $this->clause->toSQL($this->grammar);

        $this->assertEquals('LIMIT 10 OFFSET 20', $sql);
    }

    public function test_to_sql_returns_empty_string_when_no_values_set(): void {
        $sql = $this->clause->toSQL($this->grammar);

        $this->assertEquals('', $sql);
    }

    public function test_reset_clears_all_values(): void {
        $this->clause->setLimit(10)->setOffset(20);

        $result = $this->clause->reset();

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertFalse($this->clause->hasLimit());
        $this->assertFalse($this->clause->hasOffset());
        $this->assertNull($this->clause->getLimit());
        $this->assertNull($this->clause->getOffset());
    }

    public function test_method_chaining_works(): void {
        $result = $this->clause
            ->setLimit(10)
            ->setOffset(20)
            ->reset()
            ->setLimit(5);

        $this->assertInstanceOf(LimitClause::class, $result);
        $this->assertEquals(5, $this->clause->getLimit());
    }
}
