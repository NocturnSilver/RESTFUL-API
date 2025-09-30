<?php

/*
    Resources:
    [1] PATCH - https://stackoverflow.com/questions/63246541/how-to-use-patch-on-php-rest-api-on-the-following-scenario
    [2] HATEOAS - Lecture 28: REST (Part 1) Handouts by Ullrich Hustadt
    [3] COMP519 Practical 17 AJAX by Ullrich Hustadt
    [4] COMP519 Practical 18 REST (1) by Ullrich Hustadt
    [5] COMP519 Practical 19 REST (2) by Ullrich Hustadt
    [6] lastInsertId - https://www.php.net/manual/en/pdo.lastinsertid.php
    [7] implode as alias of join - https://www.php.net/manual/en/function.join.php
    [8] implode - https://www.php.net/manual/en/function.implode.php

*/

require_once("./Database.php");

class Player {

    // Model Class for the MySQL team table
    private $conn;
    private $table = "player";
    private $baseurl = "https://student.csc.liv.ac.uk/~sgnsee/v1";

    public function __construct($db) {
        // assigns the PDO connection
        $this->conn = $db;
    }

    public function PlayerRead($teamId, $playerId) {
        // The function reads a single data from a player

        try {
            // Prepare statement to select all teams
            $query = "SELECT * FROM " . $this->table . " WHERE teamId=:teamId AND playerId=:playerId"; 
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':playerId', $playerId);   
            $stmt->bindParam(':teamId', $teamId); 

            // execute the prepared statements
            $stmt->execute();   
            $players = $stmt->fetch(PDO::FETCH_ASSOC);
            $players["_links"] = array(
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "GET", "rel"=>"self"),
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "PUT", "rel"=>"edit"),
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "DELETE", "rel"=>"delete")
            );

            // send json file and change http_response_code
            echo json_encode($players);   
            http_response_code(200);
        } catch(PDOException $e) {
            http_response_code(400);
        }
        
        
        return $players;
    }

    public function PlayerReadAll($teamId) {
        // The function reads all data from the Team Model as a collection

        // Prepare statement to select all teams
        $query = "SELECT * FROM " . $this->table . " WHERE teamId=:teamId"; 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teamId', $teamId);   

        // execute the prepared statements
        $stmt->execute();   

        $players = [];
            foreach($stmt as $row) {
            $players[] = array (
                "playerId"=>$row["playerId"],
                "surname"=>$row["surname"],
                "givenName"=>$row["givenName"],
                "nationality"=>$row["nationality"], 
                "dob"=>$row["dob"], 
                "teamId"=>$row["teamId"]);
            }

            // add the HATEAOS links (my meager attempts to try to conform to the HATEOAS principle)
            $links = array(
                array("href" => "/teams/{\$teamId}/players", "method" => "GET", "rel"=>"self"),
                array("href" => "/teams/{\$teamId}/players", "method" => "POST", "rel"=>"create"),
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "GET", "rel"=>"self"),
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "PATCH", "rel"=>"edit"),
                array("href" => "/teams/{\$teamId}/players/{\$playerId}", "method" => "DELETE", "rel"=>"delete")
            );

        if (empty($players)) {
            http_response_code(404);
                $response = array(
                    "_links"=> $links
                );
            echo json_encode($players);
        } else {
            $response = array(
                "players"=>$players,
                "_links"=> $links
            );
            echo json_encode($response);   
            http_response_code(200);
            
            return $players;
        }

        
    }

    public function PlayerCreate($data, $teamId) {
        // The function creates an instance based on the response body

            try {
                // Prepare the query
                $query = "INSERT INTO " . $this->table . 
                " (surname, givenName, nationality, dob, teamId) " . 
                "VALUES (:surname, :givenName, :nationality, :dob, :teamId)";
                $stmt = $this->conn->prepare($query);
                
                // bind parameters
                $stmt->bindParam(':surname', $data['surname']);
                $stmt->bindParam(':givenName', $data['givenName']);
                $stmt->bindParam(':nationality', $data['nationality']);
                $stmt->bindParam(':dob', $data['dob']);
                $stmt->bindParam(':teamId', $teamId, PDO::PARAM_INT);

                //execute the statements
                $success = $stmt->execute();

                if ($success) {
                    // return the location of the URI
                    $playerId = $this->conn->lastInsertId();
                    echo json_encode(["location"=> "https://student.csc.liv.ac.uk/~sgnsee/v1/teams/".$teamId."/players/".$playerId]);
                    // set the http response code to sucess - 200
                    http_response_code(201);  
                } else {
                    echo json_encode(["message" => "Not Enough or Incorrect data was provided for member resource"]);
                    http_response_code(400); 
                }

            } catch (PDOException $e) {
                echo json_encode(["message"=>"POST Failed"]);
                http_response_code(500);   
            }
            
        
        // if statement for success return the $stmt otherwise return array with error message
        return $stmt;

    }

    public function PlayerUpdate($data, $teamId, $playerId) {

        // build a list of fields and values for the query
        $allowedFields = ['surname', 'givenName', 'dob', 'nationality', 'teamId'];
        $fields = [];
        $params = [];

        // Loop over every single one and check if they are one of the allowed fields
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = htmlspecialchars(trim($data[$field]));
            }
        }

        // Check if there is nothing inside the fields to update
        if (empty($fields)) {
            echo json_encode(["message" => "No valid fields provided for update"]);
            http_response_code(400);
            return;
        }
   
        try {
            // Prepare the query
            $query = "UPDATE ".$this->table ." SET " . implode(', ', $fields) . 
            " WHERE playerId = :playerId";
            $stmt = $this->conn->prepare($query);

            // bind to array to execute for fields
            $params[':playerId'] = $playerId;

            $success = $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                // Should return a json of the updated file
                echo json_encode($stmt);
                // set the http response code to sucess - 200
                http_response_code(204);  
            } else {
                echo json_encode(["message" => "There was an error in the PUT request"]);
                http_response_code(500); 
            }

        } catch (PDOException $e) {
            echo json_encode(["message"=>"PATCH Failed"]);
        }

        return $stmt;
    }

    public function PlayerDelete($teamId, $playerId) {

        try {
            // Prepare the query
            $query = "DELETE FROM " . $this->table . " WHERE teamId=:teamId AND playerId=:playerId";
            $stmt = $this->conn->prepare($query);

            // bind parameters
            $stmt->bindParam(':playerId', $playerId);
            $stmt->bindParam(':teamId', $teamId);

            // execute the statements
            $stmt->execute();

            if ($stmt) {
                // encode the message
                echo json_encode($stmt);
                // set the http response code to sucess - 200
                http_response_code(200);  
            } else {
                echo json_encode(["message" => "Member Resource Does not Exist"]);
                http_response_code(404); 
            }

        } catch (PDOException $e) {
            echo json_encode(["message"=>"Delete Failed"]);
        }

        return $stmt;
    }

}

?>
