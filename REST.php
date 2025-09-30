<?php

/*
    Resources:
    [1] COMP519 Practical 17 AJAX by Ullrich Hustadt
    [2] COMP519 Practical 18 REST (1) by Ullrich Hustadt
    [3] COMP519 Practical 19 REST (2) by Ullrich Hustadt
    [4] AJAX - https://www.youtube.com/watch?v=82hnvUYY6QA - by Brad Traversy
    [5] Php REST - https://www.youtube.com/watch?v=OEWXbpUMODk - by Brad Traversy
    [6] Php REST - https://www.youtube.com/watch?v=-nq4UbD0NT8&t=848s - by Brad Traversy
    [7] Php REST - https://www.youtube.com/watch?v=tG2U18EmIu4&t=414s - by Brad Traversy
    [8] Routes, Routers and Routing in PHP - https://www.youtube.com/watch?v=JycBuHA-glg&t=317s - Dave Hollingworth


*/

// changes Content-Type of the header
header("Content-Type: application/json");

// Imports the files below to the file so we can use the class
require_once("./Database.php");
require_once("./Team.php");
require_once("./Player.php");

// Instantiate a new Database and Team object
$db = new Database();
$conn = $db->getConnection();
$team = new Team($conn); // ASSUME THIS IS CORRECT
$player = new Player($conn);


// POST DATA ---------------------------------------------------------------------

    // NEW / MODIFIED: Get raw input and decode JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Handle the AJAX data (this took an eternity to do)
    if (isset($data['methods'])) {
        // make sure that the methods are in upper case
        $method = strtoupper($data['methods']);
        $url = $data['resource'];
        $jsonBody = $data['jsonBody'];

        // Turn it into an associative array
        if (!empty($jsonBody)) {
            $data = json_decode($jsonBody, true);
        } else {
            // if empty then assign an empty array
            $data = [];
        }
        
    } else {
        // Fallback to default handling if direct API call (without wrapper)
        $method = $_SERVER['REQUEST_METHOD'];
        $data = json_decode(file_get_contents('php://input'), true);
        $url = $_SERVER['REQUEST_URI'];
    }

    // returns associate array of the REQUEST_URI
    $uri = parse_url($url, PHP_URL_PATH); 
    $uriParts = explode('/', trim($uri, '/'));
    // $resource = $uriParts[count($uriParts) - 1];

    // variables for switch statements
    $collection = $uriParts[2];
    $teamId;
    $subcollection;
    $playerId;

    // assigns different parts for switch statement - make a guard that makes
    if (array_key_exists(3, $uriParts) && preg_match('/^\d+$/',$uriParts[3])) {
        $teamId = $uriParts[3];
        // proceed to check if after teamId is 'players'
        if ($uriParts[4] == "players") {
            $subcollection = $uriParts[4];
            // proceed if players if there exists players before playerId
            if (array_key_exists(5, $uriParts) && preg_match('/^\d+$/',$uriParts[5])) {
                $playerId = $uriParts[5];
            }  
        } 
    } 

// Functions for Handling HTTP METHODS ------------------------------------------------------------------------------------- 

    // Functions That handle for the team MySQL table

    function TeamhandleGet($modelObject) {
        // Handles 'GET' Requests 
        $stmt = $modelObject->TeamReadAll();
    }

// Functions that handle for the player MySQL table

    // Functions that handle the collective player resource (Requires teamId only)
        function PlayerhandleGet($modelObject, $teamId) {
            // Get Request for retrieving all players from a specific team
            $stmt = $modelObject->PlayerReadAll($teamId);
        }

        function PlayerhandleCreate($modelObject, $data, $teamId) {
            // POST a new player in the player for a specific team
            $stmt = $modelObject->PlayerCreate($data, $teamId);
            return;
        }
    
    // Functions that handle member resources of player (requires teamId and playerId)

        function PlayerHandleSingleGet($modelObject, $teamId, $playerId) {
            $stmt = $modelObject->PlayerRead($teamId, $playerId);
            return;
        }

        function PlayerhandlePatch($modelObject, $data, $teamId, $playerId) {
            // Handles the PUT Request
            $stmt = $modelObject->PlayerUpdate($data, $teamId, $playerId);
            return;
        }

        function PlayerhandleDelete($modelObject, $teamId, $playerId) {
            $stmt = $modelObject->PlayerDelete($teamId, $playerId);
            return;
        }

    // --------------------------SWITCH STATEMENT TO HANDLE API ENDPOINT -----------------------------------------
    // ------------------------- CREATE A PORTION TO HANDLE NON-EXISTENT RESOURCE PATHS --------------------------


    if ($collection === "teams") {
        if (isset($teamId) && $subcollection == "players") {
            if (isset($playerId) && count($uriParts) === 6 && $collection === "teams" && $subcollection == "players") {  
                switch ($method) {
                    // Switch statement for endpoint : /teams/{teamID}/players/{playerId}
                    case "GET":
                        // Get information on a specific player
                        PlayerHandleSingleGet($player, $teamId, $playerId);
                        break;
                    
                    case "PATCH":
                        // Update information on a specific player
                        PlayerHandlePatch($player, $data, $teamId, $playerId);
                        break;
                    
                    case "DELETE":
                        // Delete a specific player from the table
                        PlayerHandleDelete($player, $teamId, $playerId);
                        break;

                    default:
                        http_response_code(405);
                        echo json_encode(["Error"=> "Method not allowed. Only GET, PATCH, and DELETE methods are allowed"]);
                }

            } else {
                if (count($uriParts) === 5 && $collection === "teams" && $subcollection == "players") {
                    switch ($method) {
                        // switch case available for endpoint /teams/{teamId}/players - collection resource 
                        case "GET":
                            // Get information on all players for a specific team
                            PlayerHandleGet($player, $teamId);
                            break;
        
                        case "POST":
                            // Add a player to a specific team  
                            PlayerhandleCreate($player, $data, $teamId);
                            break;
    
                        default:
                            http_response_code(405);
                            echo json_encode(["Error"=>"Method not allowed. Only GET AND POST methods allowed"]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(["Error" => "Resource not found"]);
                }
                
            }

        } else {
            // if there is no teamID - methods available for /teams
            if (count($uriParts) === 3 && $collection === "teams") {
                switch ($method) {
                    case "GET":
                        TeamhandleGet($team);
                        break;
    
                    default:
                        http_response_code(405);
                        echo json_encode(["message"=>"Method not allowed. The only methods allowed for resource is GET"]);
                } 
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Resource not found"]);
            }
        }
    } else { 
        // accessing the root of the file
        if (count($uriParts) === 2 && $uriParts[1]=="v1") {

            switch($method) {
                case "GET":
                    $links = [];
                    $links["_links"] = array(
                        array("href" => "/teams", "method" => "GET", "rel"=>"self"),
                    );
                    echo json_encode($links); 
                    break;

                default:
                    http_response_code(405);
                    echo json_encode(["message"=>"Method not allowed"]);
            }
             
            
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Resource not found"]);
        }
        
    }


    