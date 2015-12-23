<?php

use Util\Config;
use Util\Database;
use Util\Util;

class Initializer {

    private function createConfigFile(){

        // we need that command to determine the absolute paths
        if(getcwd() === FALSE){
            fprintf(STDERR, "Can't get the current working path.\n");
            exit(2);
        }

        // sample configuration written if none is present
        $config = array(
            'general' => array(
                'enable' => true,
                'safe_mode' => true
            ),
            'dirs' => array(
                'deleted' => getcwd().'/deleted'
            ),
            'remove' => array(
                'days' => 30,
                'backups' => 7
            ),
            'logging' => array(
                'dir' => getcwd().'/log',
                'mail' => array('keidel@gbv.de'),
                'enable_mail' => true,
                'max_mailsize' => 1000000
            ),
            'database' => array(
                'host' => 'localhost',
                'port' => '27017',
                'options' => array(
                    'safe'    => true,
                    'fsync'   => true,
                    'timeout' => 10000
                )
            ),
            'firstrun' => true
        );

        // try to write the configuration file
        if(file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === FALSE){
            fprintf(STDERR, "Couldn't create the config file. Please make sure you have sufficient rights to write in this directory.\n");
            exit(3);
        }

        fprintf(STDOUT, "A configuration file template has been copied to %s.\nPlease review the configuration to make sure it can be used.\n", getcwd().'/config.json');
        exit(1);
    }

    private function checkAndCreateDir($path){

        echo "Checking ".$path."... ";

        if(is_dir($path)){
            echo "Exists!\n";
            return;
        }

        if(mkdir($path)){
            echo "Created!\n";
        }else{
            echo "Create failed!\n";
            exit(6);
        }
    }

    public function run(){

        // Try to create a configuration file if non exists so far
        if(!file_exists('config.json')){
            $this->createConfigFile();
        }

        $config = Config::getInstance();

        if($config->getValue('firstrun')){
            echo "This appears to be your first run of the AutoRemover.\n";
            echo "I will check the configuration and create the directories if the do not already exist.\n\n";

            echo "Checking if the PHP Mongo extension is available... ";
            if(extension_loaded("mongo")){
                echo "Yes!\n\n";
            }else{
                echo "No. Please install it.\n";
                exit(7);
            }

            echo "Trying to connect to the database... ";
            try{
                Database::getInstance();
                echo "Connected! \n\n";
            }catch(Exception $e){
                echo "Fail! \n";
                echo "Reason: ".$e->getMessage()."\n";
                exit(8);
            }

            //check if all dirs exist
            echo "\n\nChecking if all local directories exist and create them if possible.";
            $this->checkAndCreateDir($config->getValue('logging', 'dir'));

            // check if the mail is valid
            if($config->getValue('logging', 'enable_mail')){
                echo "\n\nChecking if the log emails are valid...\n";
                $emails = $config->getValue('logging', 'mail');
                foreach($emails as $email){
                    echo "\t ".$email." ... ";
                    if(filter_var($email ,FILTER_VALIDATE_EMAIL)){
                        echo "Valid!\n";
                    }else{
                        echo "Invalid email!\n";
                        exit(6);
                    }
                }
                echo "\n";

            }

            echo "\n\nChecking removal period...";
            if($config->getValue('remove', 'days') > 0){
                echo "OK! \nRejected titles get removed after ".$config->getValue('remove', 'days')." days!\n";
            }else{
                echo "Invalid! Please set the period > 0 days!\n";
                exit(7);
            }

            if($config->getValue('general', 'safe_mode')){

                echo "\n\nSafe mode is enabled, performing additional checks\n";
                $this->checkAndCreateDir($config->getValue('dirs', 'deleted'));

                echo "\nChecking removal period of backups...";
                if($config->getValue('remove', 'backups') > 0){
                    echo "OK! \nRejected titles get removed after ".$config->getValue('remove', 'backups')." days!\n";
                }else{
                    echo "Invalid! Please set the period > 0 days!\n";
                    exit(7);
                }
            }

            //disable check on next run
            $config->setFirstRun();
            echo "\n\nAll checks passed! The AutoRemover is now ready to be used!\n";
            exit(0);
        }
    }


}