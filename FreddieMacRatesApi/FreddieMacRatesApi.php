<?php

namespace App\Services\FreddieMacRatesApi;

use GuzzleHttp\Client;
use \Illuminate\Support\Collection;

class FreddieMacRatesApi implements FreddieMacRatesApiInterface
{
    private $rateUrl = 'https://www.freddiemac.com/pmms/docs/PMMS_history.csv';

    private $guzzleOptions = [
        'timeout' => 15,
        'connect_timeout' => 15,
        'verify' => false,
    ];

    /**
     * @return void
     */
    public function __construct()
    {
    }

    public function getHistoricalRates()
    {
        return $this->formatOutput(
            $this->loadSheet($this->fetchData())
        );
    }

    public function getWeekly()
    {
        $year = date('Y');
        return $this->formatOutput($this->loadSheet($this->fetchData()),$year);

    }

    private function fetchData()
    {
        $tempDatafile = tempnam(sys_get_temp_dir(), 'freddie-rates-');
        $client = new Client($this->guzzleOptions);
        try {
            $client->get($this->rateUrl, ['sink' => $tempDatafile]);
        } catch (\Exception $e) {
            if (file_exists($tempDatafile)) {
                unlink($tempDatafile);
            }
            throw new \Exception('Unable to fetch data from ' . $this->rateUrl);
        }

        return $tempDatafile;
    }

    private function formatOutput(Collection $sheetArray, int $year = null)
    {
        // Make Carbon object for a particular year.
        $query = $sheetArray;
        if ($year) {
            $date = \Carbon\Carbon::createFromDate($year, 1, 1);
            $query = $query->where('date', '>', $date);
        } else {
            $date = \Carbon\Carbon::createFromDate(1970, 1, 1);
        }

        $formattedArray = [];
        foreach ($sheetArray->where('date', '>', $date) as $row) {
            // If the row doesn't start with a date, skip it.

            $formattedArray[] = [
                'mortgage_date' => $row['date']->format('m/d/Y'),
                'fixed_30' => $row['pmms30'] ?? null,
                'fixed_30_fees_and_points' => is_numeric($row['pmms30p']) ? $row['pmms30p'] : null,
                'fixed_15' => $row['pmms15'] ?? null,
                'fixed_15_fees_and_points' => $row['pmms15p'] ?? null,
                'arm_5' => $row['pmms51'] ?? null,
                'arm_5_fees_and_points' => $row['pmms51p'] ?? null,
                'arm_5_margin' => $row['pmms51m'] ?? null,
                'spread_30_yr_5_1_arm' => $row['pmms51spread'] ?? null,
            ];

        }

        return $formattedArray;
    }

    private function loadSheet(string $sheetPath)
    {
        // read CSV to associative array
        $csv = array_map('str_getcsv', file($sheetPath));
        // get header row
        $header = array_shift($csv);
        


        // replace header row keys with header row values
        $csv = array_map(function($row) use ($header) {
            foreach ($header as $key => $value) {
                // Make Carbon date
                if ($value === 'date') {
                    $row[$value] = \Carbon\Carbon::createFromFormat('m/d/Y', $row[$key]) ?? null;
                    unset($row[$key]);
                    continue;
                }
                $row[$value] = $row[$key] ?? null;
                unset($row[$key]);
            }
            return array_combine($header, $row);
        }, $csv);

        // Add to collection
        return collect($csv);
        
    }
}
