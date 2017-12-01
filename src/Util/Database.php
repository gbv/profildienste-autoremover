<?php

namespace Util;

/**
 * Class Database
 * @package Util
 *
 * Wrapper for interaction with the database.
 * This class is a singleton, so that only connection to the database is used.
 */
class Database {

    /**
     * @var Database Instance of this class (singleton)
     */
    private static $instance;

    /**
     * Database constructor.
     */
    private function __construct(){

        // establish a new connection
        $config = Config::getInstance();
        $m = new \MongoClient('mongodb://'.$config->getValue('database', 'host').':'.$config->getValue('database', 'port'));
        $this -> db = $m->selectDB('pd');

        $this->titles = new \MongoCollection($this -> db, 'titles');
    }

    /**
     * Getter for the Database instance.
     *
     * @return Database
     */
    public static function getInstance(){
        if(is_null(Database::$instance)){
            Database::$instance = new Database();
        }

        return Database::$instance;
    }

    /**
     * Get all rejected titles older than $before.
     *
     * @param \MongoDate $before
     * @return array
     */
    public function getAllTitles(\MongoDate $before){

        $all_titles_before = $this->titles->find([
            '$and' => [
                ['lastStatusChange' =>
                    ['$lte' => $before]
                ],
                ['status' => 'rejected']
            ]
        ]);

        $titles = array();
        foreach ($all_titles_before as $title){
            $titles[] = $title;
        }

        return $titles;
    }

    /**
     * Deletes a title with the id $id from the database.
     *
     * @param $id
     * @return bool|string true, if the operation suceeds, the errormessage otherwise
     */
    public function deleteTitle($id){
        try {
            $this->titles->remove(array('_id' => $id));
        } catch(\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

}