<?php

use Util\Config;
use Util\Database;
use Util\Log;

/**
 * Class App
 *
 * This is the main class of the App.
 * The class is designed as a singleton.
 */
class App {

    // Singleton
    private static $instance;

    private function __construct() {

        $init = new Initializer();
        $init->run();

    }

    public static function getInstance() {

        if (is_null(App::$instance)) {
            App::$instance = new App();
        }

        return App::$instance;
    }

    /**
     * Starts and run the application
     */
    public function run() {

        $config = Config::getInstance();
        $db = Database::getInstance();
        $log = Log::getInstance();

        $di = new DateInterval('P'.$config->getValue('remove', 'days').'D');

        $delete_all_before = (new DateTime())->sub($di);

        $md = new MongoDate($delete_all_before->getTimestamp());

        // get all titles older than the specified period of days
        $titles = $db->getAllTitles($md);

        // check if there are titles to delete
        if (count($titles) > 0){

            foreach ($titles as $title){

                $log->getLog()->addInfo('Deleting title '.$title['_id']);

                // delete the title from the database
                $db->deleteTitle($title['_id']);

                if($config->getValue('general', 'safe_mode')){
                    // save the title on disk
                    $fpath = $config->getValue('dirs', 'deleted', true) . $title['_id'] . '.json';
                    file_put_contents($fpath, json_encode($title, JSON_PRETTY_PRINT));

                    $log->getLog()->addInfo('Saved a backup copy of '.$title['_id'].' as '.$fpath);
                }
            }
        }

        // check if there are saved titles which should be deleted now
        if (is_dir($config->getValue('dirs', 'deleted'))){

            // All files after this date should be deleted
            $backup_di = new DateInterval('P'.$config->getValue('remove', 'backups').'M');
            $delete_backups_before = (new DateTime())->sub($backup_di)->getTimestamp();

            // check all files in the deleted dir
            $deldir = opendir($config->getValue('dirs', 'deleted'));
            if($deldir){
                while (false !== ($f = readdir($deldir))) {
                    if($f !== '.' && $f !== '..'){

                        $fpath = $config->getValue('dirs', 'deleted', true).$f;

                        // delete the file if the timestamp of the last modification
                        // is older than the specified period of days for backups
                        if (filemtime($fpath) <= $delete_backups_before){

                            if (unlink($config->getValue('dirs', 'deleted', true).$f)){
                                $log->getLog()->addInfo('Deleted backup '.$fpath);
                            }else{
                                $log->getLog()->addError('Could not delete '.$fpath);
                            }

                        }
                    }
                }
            }else{
                $log->getLog()->addError('Could not open deleted dir ');
            }
        }
    }
}