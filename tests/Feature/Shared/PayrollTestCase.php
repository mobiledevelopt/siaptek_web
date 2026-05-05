<?php

namespace Tests\Feature\Shared;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

abstract class PayrollTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setFixedDate(string $datetime = '2026-03-28 08:00:00')
    {
        Carbon::setTestNow($datetime);
    }

    protected function generateCombinations(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $key => $values) {
            $append = [];
            foreach ($result as $product) {
                foreach ($values as $value) {
                    $product[$key] = $value;
                    $append[] = $product;
                }
            }
            $result = $append;
        }
        return $result;
    }
}