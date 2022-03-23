<?php

namespace App\Services\FreddieMacRatesApi;

/**
 * FeddieMacRates API interface
 */
interface FreddieMacRatesApiInterface
{
    public function getWeekly();

    public function getHistoricalRates();
}
