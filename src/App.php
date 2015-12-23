<?php

use Util\Config;
use Util\Database;
use Util\Log;

class App {

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

    public function getMailer() {
        return $this->mailer;
    }


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
            $backup_di = new DateInterval('P'.$config->getValue('remove', 'backups').'D');
            $delete_backups_before = (new DateTime())->sub($backup_di)->getTimestamp();

            $deldir = opendir($config->getValue('dirs', 'deleted'));
            if($deldir){
                while (false !== ($f = readdir($deldir))) {
                    if($f !== '.' && $f !== '..'){
                        if (filemtime($config->getValue('dirs', 'deleted', true).$f) <= $delete_backups_before){
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