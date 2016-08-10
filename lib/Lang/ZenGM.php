<?php

namespace Lang;

use League\Csv\Writer;
use League\Csv\Reader;

class ZenGM {

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

    private static $exportObjectId = array(
        self::PLAYERS => 'pid',
        self::TEAMS   => 'tid'
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

        $this->jsonData = json_decode(file_get_contents($this->file), true);
    }

    /**
     * Export the given property to a CSV file, this is mainly used for debugging
     * @return void
     */
    public function export() {

        foreach (self::$exportObjects as $object) {
            $this->exportObject($object);
        }

        // $this->exportTeams();
        // $this->exportPlayers();
    }

    public function exportObject($object) {
        $records = $this->jsonData[$object];
        $dir    = __DIR__ . '/../../data/';

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

        foreach ($properties as $property) {
            $data[$property] = array();
            $dataHeaders[$property] = array($idColumn => $idColumn);

            foreach ($records as $i => $record) {
                $idColumnValue = $objects[$i][$idColumn];
                $data[$property][$idColumnValue] = array($idColumn => $idColumnValue);

                foreach ($record[$property] as $k => $propertyData) {
                    if (is_array($propertyData)) {
                        foreach ($propertyData as $propertyName => $propertyValue) {
                            if (!array_key_exists($propertyName, $dataHeaders)) {
                                $dataHeaders[$property][$propertyName] = $propertyName;
                            }

                            if (is_array($propertyValue)) {
                                $propertyValue = ""; //implode(',', $propertyValue); // for the skills data
                            }

                            $data[$property][$idColumnValue][$propertyName] = $propertyValue;
                        }
                    } else {
                        if (!array_key_exists($k, $dataHeaders)) {
                            $dataHeaders[$property][$k] = $k;
                        }
                        $data[$property][$idColumnValue][$k] = $propertyData;
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
