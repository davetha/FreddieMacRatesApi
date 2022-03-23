<?php

namespace App\Services\FreddieMacRatesApi;

/**
 * The Realty FeddieMacRates API interface
 */
interface FreddieMacRatesApiInterface
{
    public function getWeekly();

    public function getHistoricalRates();
}
