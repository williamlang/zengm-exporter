<?php

namespace Lang;

use League\Csv\Writer;
use League\Csv\Reader;

class ZenGM {

    const META    = 'meta';
    const PLAYERS = 'players';
    const TEAMS   = 'teams';

    /**
     * Represents all the objects we'll be exporting
     * @var array
     */
    private static $exportObjects = array(self::PLAYERS, self::TEAMS);

    /**
     * Represents all the proeprties of the objects we'll be exporting
     * @var array
     */
    private static $exportObjectProperties = array(
        self::PLAYERS => array('draft', 'awards', 'salaries', 'contract', 'ratings', 'stats', 'born'),
        self::TEAMS   => array('seasons', 'stats')
    );

    /**
     * Represents the unique identifier for each exported object
     * @var array
     */
    private static $exportObjectId = array(
        self::PLAYERS => 'pid',
        self::TEAMS   => 'tid'
    );

    /**
     * Whether or not the export object has season dependencies for historical purposes
     * @var array
     */
    private static $exportObjectSeasonDependencies = array(
        self::PLAYERS => true,
        self::TEAMS   => false
    );

    /**
     * The path to the ZenGM export
     * @var string
     */
    private $file;

    /**
     * The JSON data from the ZenGM export
     * @var array
     */
    private $jsonData;

    /**
     * Constructor
     * @param string $file The path to the ZenGM export
     */
    public function __construct($file) {
        $this->file = $file;

        if (!file_exists($file)) {
            throw new \Exception("File $file does not exist.");
        }

        $this->jsonData   = json_decode(file_get_contents($this->file), true);
        $this->leagueName = preg_replace('/[^a-z0-9]/i', '_', $this->jsonData[self::META]['name']);
        $dir              = sprintf("%s/../../data/%s/", __DIR__, $this->leagueName);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

    /**
     * Export the given property to a CSV file, this is mainly used for debugging
     * @return void
     */
    public function export($object = null) {
        $seasons = $this->getSeasons();
        $seasons = $this->exportSeasons($seasons);

        if (empty($object)) {
            foreach (self::$exportObjects as $object) {
                $this->exportObject($object, $seasons);
            }
        } else {
            $this->exportObject($object, $seasons);
        }

        // $this->exportTeams();
        // $this->exportPlayers();
    }

    /**
     * Export seasons, assign a season id
     * @param  array $seasons
     * @return array
     */
    private function exportSeasons($seasons) {
        $dir = sprintf("%s/../../data/%s/", __DIR__, $this->leagueName);

        $headers = array(
            'sid'  => 'sid',
            'year' => 'year'
        );

        $objects = array();

        foreach ($seasons as $seasonYear) {
            $sid = sizeof($objects) + 1;
            $objects[$seasonYear] = array('sid' => $sid, 'year' => $seasonYear);
        }

        $this->exportArray($headers, $objects, sprintf('%s/%s.csv', $dir, 'seasons'));
        return $objects;
    }

    /**
     * Get the current season for objects that need this for historical purposes
     * @return int
     */
    private function getSeasons() {
        $startSeason = date("Y", time());
        $endSeason   = 0;

        $stats = $this->jsonData[self::TEAMS][0]['stats'];
        foreach ($stats as $stat) {
            $startSeason = min($startSeason, $stat['season']);
            $endSeason   = max($endSeason, $stat['season']);
        }

        return range($startSeason, $endSeason);
    }

    /**
     * Generic method to export an object to CSV
     * @param  string $object
     * @return void
     */
    public function exportObject($object, $seasons) {
        $records = $this->jsonData[$object];
        $dir     = sprintf("%s/../../data/%s/", __DIR__, $this->leagueName);

        $objects = array();
        $headers = array();

        foreach ($records as $i => $record) {
            $objects[$i] = array();

            foreach ($record as $property => $value) {
                if (!is_array($value)) {
                    if (!array_key_exists($property, $headers)) {
                        $headers[$property] = $property;
                    }

                    $objects[$i][$property] = $value;
                }
            }
        }

        $this->exportArray($headers, $objects, sprintf('%s/%s.csv', $dir, $object));

        $data        = array();
        $dataHeaders = array();
        $properties  = self::$exportObjectProperties[$object];
        $idColumn    = self::$exportObjectId[$object];
        $seasonDep   = self::$exportObjectSeasonDependencies[$object];

        foreach ($properties as $property) {
            $data[$property] = array();
            $dataHeaders[$property] = array($idColumn => $idColumn);

            foreach ($records as $i => $record) {

                if ($seasonDep && isset($record[$property]['season'])) {
                    $recordNo = md5($i . $record[$property]['season']);
                } else {
                    $recordNo = $i;
                }

                $idColumnValue = $objects[$i][$idColumn];
                $data[$property][$recordNo] = array($idColumn => $idColumnValue);

                foreach ($record[$property] as $k => $propertyData) {
                    if (is_array($propertyData)) {
                        foreach ($propertyData as $propertyName => $propertyValue) {
                            if (!array_key_exists($propertyName, $dataHeaders)) {
                                $dataHeaders[$property][$propertyName] = $propertyName;
                            }

                            if (is_array($propertyValue)) {
                                $propertyValue = ""; //implode(',', $propertyValue); // for the skills data
                            }

                            $data[$property][$recordNo][$propertyName] = $propertyValue;
                        }
                    } else {
                        if (!array_key_exists($k, $dataHeaders)) {
                            $dataHeaders[$property][$k] = $k;
                        }

                        $data[$property][$recordNo][$k] = $propertyData;
                    }
                }
            }

            $this->exportArray($dataHeaders[$property], $data[$property], sprintf('%s/%s_%s.csv', $dir, $object, $property));
        }
    }

    /**
     * Export an array to a CSV file
     * @param  array $headers
     * @param  array $data
     * @param  string $fileName
     * @return void
     */
    private function exportArray($headers, $data, $fileName) {
        $csv = Writer::createFromPath($fileName, 'a+');
        array_unshift($data, $headers);
        $csv->insertAll($data);
    }

    /**
     * Flatten an array
     * @param  array  $array
     * @param  string $prefix
     * @return array
     */
    private function flatten($array, $prefix = '') {
        $result = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
