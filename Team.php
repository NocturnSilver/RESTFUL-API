<?php

/*
    Resources:
    [1] PATCH ideas .. but hard to understand - https://stackoverflow.com/questions/63246541/how-to-use-patch-on-php-rest-api-on-the-following-scenario
    [2] HATEOAS - Lecture 28: REST (Part 1) Handouts by Ullrich Hustadt
    [3] COMP519 Practical 17 AJAX by Ullrich Hustadt
    [4] COMP519 Practical 18 REST (1) by Ullrich Hustadt
    [5] COMP519 Practical 19 REST (2) by Ullrich Hustadt
    [6] Average age - https://stackoverflow.com/questions/13372395/average-age-from-dob-field-mysql-php
    [7] current date - https://www.w3schools.com/sql/func_mysql_curdate.asp
    [8] TIMESTAMPDIFF - https://www.w3resource.com/mysql/date-and-time-functions/mysql-timestampdiff-function.php 

*/

require_once("./Database.php");
require_once("./Player.php");

class Team {

    // Model Class for the MySQL team table
    private $conn;
    private $table = "team"; 

    public function __construct($db) {
        // assigns the PDO connection
        $this->conn = $db;
    }


    public function TeamReadAll() {
        // The function reads all data from the Team Model as a collection

        try {
            // Prepare statement to select all teams
            $query = "SELECT t.teamId, t.teamName, t.sport, ROUND(AVG(TIMESTAMPDIFF(YEAR, p.dob, CURDATE())), 1) AS avgAge " . 
            "FROM " . $this->table . " t LEFT JOIN player p ON t.teamId = p.teamId " .
            "GROUP BY t.teamId ORDER BY teamName ASC"; 
            $stmt = $this->conn->prepare($query);
            $stmt->execute();   

            // read all data and pass as an associative array
            $teams = [];
            foreach($stmt as $row) {
            $teams[] = array (
                "teamId" => $row["teamId"],
                "teamName" => $row["teamName"],
                "sport" => $row["sport"],
                "avgAge" => floatval($row["avgAge"]),
                );
            }

            // add the HATEAOS links (my meager attempts to try to conform to the HATEOAS principle)
            $links = array(
            array("href" => "/teams", "method" => "GET", "rel"=>"self"),
            array("href" => "/teams/{\$teamId}/players", "method" => "GET", "rel"=>"collection"),
            array("href" => "/teams/{\$teamId}/players", "method" => "POST", "rel"=>"create")
            );

            $response = array(
                "teams"=>$teams,
                "_links"=> $links
            );

            echo json_encode($response);   
            http_response_code(200);
            return $response;
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(["Error"=>"Interal Server Error"]);  
        }

        
    }

}

?>
