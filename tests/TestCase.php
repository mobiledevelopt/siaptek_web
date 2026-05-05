<?php

namespace Tests;

use App\Exceptions\ApiException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Carbon\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setHariKerja($time = '08:00:00')
    {
        Carbon::setTestNow("2026-03-04 {$time}"); // Rabu
    }

    protected function setHariLibur($time = '08:00:00')
    {
        Carbon::setTestNow("2026-03-08 {$time}"); // Minggu
    }

    protected function setSebelumJamMasuk()
    {
        Carbon::setTestNow('2026-03-04 06:30:00');
    }

    protected function setSetelahJamMasuk()
    {
        Carbon::setTestNow('2026-03-04 08:30:00');
    }

    protected function setSetelahBatasMasuk()
    {
        Carbon::setTestNow('2026-03-04 10:01:00');
    }

    protected function setSebelumJamPulang()
    {
        Carbon::setTestNow('2026-03-04 06:30:00');
    }

    protected function setJamPulang()
    {
        Carbon::setTestNow('2026-03-04 15:00:00');
    }

    protected function setMaxJamPulang()
    {
        Carbon::setTestNow('2026-03-04 18:01:00');
    }

    protected function setApelSore()
    {
        Carbon::setTestNow('2026-03-06 16:04:00');
    }

    protected function resetTime()
    {
        Carbon::setTestNow(); // reset ke real time
    }

    protected function assertApiException(
        $callback,
        $expectedErrorCode,
        $expectedMessage = null,
        $expectedStatus = 422
    ) {
        try {
            $callback();

            $this->fail('Expected ApiException was not thrown.');

        } catch (ApiException $e) {

            $this->assertEquals($expectedErrorCode, $e->getErrorCode());
            $this->assertEquals($expectedStatus, $e->getStatus());

            if ($expectedMessage) {
                $this->assertStringContainsString(
                    $expectedMessage,
                    $e->getMessage()
                );
            }
        }
    }
    
}

