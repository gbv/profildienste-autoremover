<?php

namespace Util;


class Database {

    private static $instance;

    private function __construct(){
        $config = Config::getInstance();
        $m = new \MongoClient('mongodb://'.$config->getValue('database', 'host').':'.$config->getValue('database', 'port'));
        $this -> db = $m->selectDB('pd');

        $this->titles = new \MongoCollection($this -> db, 'titles');
    }

    public static function getInstance(){
        if(is_null(Database::$instance)){
            Database::$instance = new Database();
        }

        return Database::$instance;
    }

    public function getAllTitles(\MongoDate $before){

        $all_titles_before = $this->titles->find(array(
            '$and' => array(
                array('lastStatusChange' =>
                    array('$lte' => $before)
                ),
                array('status' => 'rejected')
            )
        ));

        $titles = array();
        foreach ($all_titles_before as $title){
            $titles[] = $title;
        }

        return $titles;
    }

    public function deleteTitle($id){
        try {
            $this->titles->remove(array('_id' => $id));
        } catch(\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

}