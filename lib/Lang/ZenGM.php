<?php

namespace Lang;

use League\Csv\Writer;
use League\Csv\Reader;

class ZenGM {

    const PLAYERS = 'players';
    const TEAMS   = 'teams';

    /**
     * The path to the ZenGM export
     * @var string
     */
    private $file;

    /**
     * Constructor
     * @param string $file The path to the ZenGM export
     */
    public function __construct($file) {
        $this->file = $file;

        if (!file_exists($file)) {
            throw new \Exception("File $file does not exist.");
        }
    }

    /**
     * Export the given property to a CSV file, this is mainly used for debugging
     * @return void
     */
    public function export() {
        $this->exportTeams();
        $this->exportPlayers();
    }

    public function exportTeams() {
        $jsonData = json_decode(file_get_contents($this->file), true);
        $dir    = __DIR__ . '/../../data/';
        $records = $jsonData[self::TEAMS];

        $teams = array();
        $headers = array();

        foreach ($records as $i => $record) {
            $teams[$i] = array();

            foreach ($record as $property => $value) {
                if (!is_array($value)) {
                    if (!array_key_exists($property, $headers)) {
                        $headers[$property] = $property;
                    }

                    $teams[$i][$property] = $value;
                }
            }
        }

        // $teams[]

        $data = array();
        $dataHeaders = array();
        $properties = array('seasons', 'stats');

        foreach ($properties as $property) {
            $data[$property] = array();
            $dataHeaders[$property] = array('tid' => 'tid');

            foreach ($records as $i => $record) {
                $tid = $teams[$i]['tid'];

                foreach ($record[$property] as $propertyData) {
                    $data[$property][$tid] = array('tid' => $tid);

                    foreach ($propertyData as $propertyName => $propertyValue) {
                        if (!array_key_exists($propertyName, $dataHeaders)) {
                            $dataHeaders[$property][$propertyName] = $propertyName;
                        }

                        if (is_array($propertyValue)) {
                            $propertyValue = '';//implode(',', $propertyValue); // for the skills data
                        }

                        $data[$property][$tid][$propertyName] = $propertyValue;
                    }
                }
            }

            $this->exportArray($dataHeaders[$property], $data[$property], sprintf('%s/%s_%s.csv', $dir, self::TEAMS, $property));
        }

        $this->exportArray($headers, $teams, sprintf('%s/%s.csv', $dir, self::TEAMS));
    }

    public function exportPlayers() {
        $jsonData = json_decode(file_get_contents($this->file), true);
        $dir    = __DIR__ . '/../../data/';
        $records = $jsonData[self::PLAYERS];

        $players = array();
        $headers = array();

        foreach ($records as $i => $record) {
            $players[$i] = array();

            foreach ($record as $property => $value) {
                if (!is_array($value)) {
                    if (!array_key_exists($property, $headers)) {
                        $headers[$property] = $property;
                    }

                    $players[$i][$property] = $value;
                }
            }
        }

        $data = array();
        $dataHeaders = array();
        $properties = array(/*'draft', 'awards', 'salaries', */ 'contract', 'ratings', 'stats', 'born');

        foreach ($properties as $property) {
            $data[$property] = array();
            $dataHeaders[$property] = array('pid' => 'pid');

            foreach ($records as $i => $record) {
                $pid = $players[$i]['pid'];
                $data[$property][$pid] = array('pid' => $pid);

                foreach ($record[$property] as $k => $propertyData) {
                    if (is_array($propertyData)) {
                        foreach ($propertyData as $propertyName => $propertyValue) {
                            if (!array_key_exists($propertyName, $dataHeaders)) {
                                $dataHeaders[$property][$propertyName] = $propertyName;
                            }

                            if (is_array($propertyValue)) {
                                $propertyValue = implode(',', $propertyValue); // for the skills data
                            }

                            $data[$property][$pid][$propertyName] = $propertyValue;
                        }
                    } else {
                        if (!array_key_exists($k, $dataHeaders)) {
                            $dataHeaders[$property][$k] = $k;
                        }
                        $data[$property][$pid][$k] = $propertyData;
                    }
                }
            }

            $this->exportArray($dataHeaders[$property], $data[$property], sprintf('%s/%s_%s.csv', $dir, self::PLAYERS, $property));
        }

        $this->exportArray($headers, $players, sprintf('%s/%s.csv', $dir, self::PLAYERS));
    }

    private function exportArray($headers, $data, $fileName) {
        $csv = Writer::createFromPath($fileName, 'w+');
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
