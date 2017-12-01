<?php

namespace Services;

use Config\Config;
use Exception;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Driver\Exception\ConnectionTimeoutException;

class DatabaseService {

    /**
     * @var Config Configuration Service
     */
    private $config;

    /**
     * @var Client Underlying MongoDB client
     */
    private $client;

    /**
     * @var \MongoDB\Collection The title collection
     */
    private $titles;

    public function __construct(Config $config) {

        $this->config = $config;

        $this->client = new Client('mongodb://' . $config->getValue('database', 'host') . ':' . $config->getValue('database', 'port'));
        $db = $this->client->selectDatabase($config->getValue('database', 'name'));

        $this->titles = $db->selectCollection('titles');
    }

    public function checkConnectivity() {
        try {
            $this->client->listDatabases();
        } catch (ConnectionTimeoutException $e) {
            throw new Exception('Failed to connect to the database: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Get all rejected titles older than $before.
     *
     * @param UTCDateTime $before
     * @return array
     */
    public function getExpiredRejectedTitles(UTCDateTime $before){
        return $this->titles->find([
            '$and' => [
                ['lastStatusChange' =>
                    ['$lte' => $before]
                ],
                ['status' => 'rejected']
            ]
        ])->toArray();
    }

    /**
     * Get all rejected titles older than $before.
     *
     * @param UTCDateTime $before
     * @return int
     */
    public function deleteExpiredRejectedTitles(UTCDateTime $before){
        return $this->titles->deleteMany([
            '$and' => [
                ['lastStatusChange' =>
                    ['$lte' => $before]
                ],
                ['status' => 'rejected']
            ]
        ])->getDeletedCount();
    }
}