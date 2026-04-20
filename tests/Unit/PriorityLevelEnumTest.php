<?php

namespace Nylo\LaravelFCM\Test\Unit;

use Nylo\LaravelFCM\Enums\PriorityLevel;
use Nylo\LaravelFCM\Test\TestCase;

class PriorityLevelEnumTest extends TestCase
{
    public function test_enum_values_are_correct_strings()
    {
        $this->assertEquals('highest', PriorityLevel::HIGHEST->value);
        $this->assertEquals('lowest', PriorityLevel::LOWEST->value);
    }

    public function test_can_create_from_string_value()
    {
        $highest = PriorityLevel::from('highest');
        $lowest = PriorityLevel::from('lowest');

        $this->assertEquals(PriorityLevel::HIGHEST, $highest);
        $this->assertEquals(PriorityLevel::LOWEST, $lowest);
    }

    public function test_try_from_returns_null_for_invalid_value()
    {
        $result = PriorityLevel::tryFrom('invalid');

        $this->assertNull($result);
    }
}
