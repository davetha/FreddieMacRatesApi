<?php

namespace App\Services\FreddieMacRatesApi;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class FreddieMacRatesApi implements FreddieMacRatesApiInterface
{
    private $rateUrl = 'https://www.freddiemac.com/pmms/docs/historicalweeklydata.xls';

    private $guzzleOptions = [
        'timeout' => 15,
        'connect_timeout' => 15,
        'verify' => false
    ];

    /**
     * @return void
     */
    public function __construct()
    {

    }

    public function getHistoricalRates() {
        return $this->formatOutput(
            $this->loadSheet("Full History", $this->fetchData())
        );
    }

    public function getWeekly() {
        $year = date("Y");
        return $this->formatOutput(
            $this->loadSheet("1PMMS" . $year, $this->fetchData()), $year
        );

    }

    private function fetchData() {
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

    private function formatOutput(array $sheetArray, int $year = NULL) {

        $formattedArray = [];
        foreach ($sheetArray as $row) {
            // If the row doesn't start with a date, skip it.
            if (!preg_match('/^\d+\/\d+/', $row[0])) {
                continue;
            }
            $formattedArray[] = [
                'mortgage_date' => $year ? $row[0] . "/" . $year : $row[0],
                'fixed_30' => is_numeric(rtrim($row[1]) ) ? rtrim($row[1]) : NULL,
                'fixed_30_fees_and_points' => is_numeric(rtrim($row[2]) ) ? rtrim($row[2]) : NULL,
                'fixed_15' => is_numeric(rtrim($row[3]) ) ? rtrim($row[3]) : NULL,
                'fixed_15_fees_and_points' => is_numeric(rtrim($row[4]) ) ? rtrim($row[4]) : NULL,
                'arm_5' => is_numeric(rtrim($row[5]) ) ? rtrim($row[5]) : NULL,
                'arm_5_fees_and_points' => is_numeric(rtrim($row[6]) ) ? rtrim($row[6]) : NULL,
                'arm_5_margin' => is_numeric(rtrim($row[7]) ) ? rtrim($row[7]) : NULL,
                'spread_30_yr_5_1_arm' => is_numeric(rtrim($row[8]) ) ? rtrim($row[8]) : NULL,
            ];
        }

        return $formattedArray;

    }

    private function loadSheet(string $sheetName,string $sheetPath) {
        $sheet = new Xls();
        $loadSheet = $sheet->load($sheetPath);
        //dd($loadSheet->getSheetNames());
        $sheetNumber = array_search($sheetName, $loadSheet->getSheetNames());
        if ( $sheetNumber === false ) {
            if (file_exists($sheetPath)) {
                unlink($sheetPath);
            }
            throw new \Exception('Sheet ' . $sheetName . ' not found in ' . $sheetPath);
        }

        unlink($sheetPath);
        return $loadSheet->getSheet($sheetNumber)->toArray();
    }

}
