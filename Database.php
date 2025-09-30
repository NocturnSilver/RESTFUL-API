<?php

/*
    Resources:
    [1] COMP519 Practical 18 REST (1) by Ullrich Hustadt
    [2] COMP519 Practical 19 REST (2) by Ullrich Hustadt

*/

    class Database {
        private $host = "studdb.csc.liv.ac.uk";
        private $user = 'sgnsee';
        private $passwd = 'Assignment3';
        private $database = 'sgnsee';
        public $conn;

        public function __construct() {
            $opt = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false);

            $this->conn = null;

            try {
                $this->conn = new PDO('mysql:host=' . $this->host . 
                                       ';dbname=' .$this->database . 
                                       ';charset=utf8mb4',
                                       $this->user,$this->passwd,$opt);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage(),500);
            }
        }

        public function getConnection() {
            return $this->conn;
        }


    }

    // $db = new Database();
?>