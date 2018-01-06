<?php
/*
  challonge-php v1.0.1 - A PHP API wrapper class for Challonge! (http://challonge.com)
  (c) 2014 Tony Drake
  Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
*/

class ChallongeAPI {
  // Attributes
  private $api_key;
  public $errors = array();
  public $warnings = array();
  public $status_code = 0;
  public $verify_ssl = true;
  public $result = false;
  
  /* 
    Class Constructor
    $api_key - String
  */
  public function __construct($api_key='') {
    $this->api_key = $api_key;
  }
  
  /*
    makeCall()
    $path - String
    $params - array()
    $method - String (get, post, put, delete)
  */
  public function makeCall($path='', $params=array(), $method='get') {
   
    // Clear the public vars
    $this->errors = array();
    $this->status_code = 0;
    $this->result = false;
    
    // Append the api_key to params so it'll get passed in with the call
    $params['api_key'] = $this->api_key;
    
    // Build the URL that'll be hit. If the request is GET, params will be appended later
    $call_url = "https://api.challonge.com/v1/".$path.'.xml';
    
    $curl_handle=curl_init();
    // Common settings
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
    
    if (!$this->verify_ssl) {
      // WARNING: this would prevent curl from detecting a 'man in the middle' attack
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYPEER, 0); 
    }
    
    $curlheaders = array(); //array('Content-Type: text/xml','Accept: text/xml');
    
    // Determine REST verb and set up params
    switch( strtolower($method) ) {
      case "post":
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;
        
      case 'put':
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;
        
      case 'delete':
        $params["_method"] = "delete";
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        // curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
        
      case "get":
      default:
        $call_url .= "?".http_build_query($params, "", "&");
    }
    
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $curlheaders); 
    curl_setopt($curl_handle,CURLOPT_URL, $call_url);
    
    $curl_result = curl_exec($curl_handle);   
    $info = curl_getinfo($curl_handle);
    $this->status_code = (int) $info['http_code'];
    $return = false;
    if ($curl_result === false) { 
      // CURL Failed
      $this->errors[] = curl_error($curl_handle);
    } else {
      switch ($this->status_code) {
      
        case 401: // Bad API Key
        case 422: // Validation errors
        case 404: // Not found/Not in scope of account
          $return = $this->result = new SimpleXMLElement($curl_result);
          foreach($return->error as $error) {
            $this->errors[] = $error;
          }
          $return = false;
          break;
          
        case 500: // Oh snap!
          $return = $this->result = false;
          $this->errors[] = "Server returned HTTP 500";
          break;
          
        case 200:
          $return = $this->result = new SimpleXMLElement($curl_result);
          // Check if the result set is nil/empty
          if (sizeof($return) ==0) {
            $this->errors[] = "Result set empty";
            $return = false;
          }
          break;
          
        default:
          $this->errors[] = "Server returned unexpected HTTP Code ($this->status_code)";
          $return = false;
      }
    }
    
    curl_close($curl_handle);
    return $return;
  }
  
  public function getTournaments($params=array()) {
    return $this->makeCall('tournaments', $params, 'get');
  }
  
  public function getTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id", $params, "get");
  }
  
  public function createTournament($params=array()) {
    if (sizeof($params) == 0) {
      $this->errors = array('$params empty');
      return false;
    }
    return $this->makeCall("tournaments", $params, "post");
  }
  
  public function updateTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id", $params, "put");
  }
  
  public function deleteTournament($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id", array(), "delete");
  }
  
  public function publishTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/publish/$tournament_id", $params, "post");
  }
  
  public function startTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/start/$tournament_id", $params, "post");
  }
  
  public function resetTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/reset/$tournament_id", $params, "post");
  }
  
  
  public function getParticipants($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id/participants");
  }
  
  public function getParticipant($tournament_id, $participant_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", $params);
  }
  
  public function createParticipant($tournament_id, $params=array()) {
    if (sizeof($params) == 0) {
      $this->errors = array('$params empty');
      return false;
    }
    return $this->makeCall("tournaments/$tournament_id/participants", $params, "post");
  }
  
  public function updateParticipant($tournament_id, $participant_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", $params, "put");
  }
  
  public function deleteParticipant($tournament_id, $participant_id) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", array(), "delete");
  }
  
  public function randomizeParticipants($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id/participants/randomize", array(), "post");
  }
  
  
  public function getMatches($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/matches", $params);
  }
  
  public function getMatch($tournament_id, $match_id) {
    return $this->makeCall("tournaments/$tournament_id/matches/$match_id");
  }
  
  public function updateMatch($tournament_id, $match_id, $params=array()) {
    if (sizeof($params) == 0) {
      $this->errors = array('$params empty');
      return false;
    }
    return $this->makeCall("tournaments/$tournament_id/matches/$match_id", $params, "put");
  }

  public function hasErrors()
  {
      return count($this->errors) > 0 ? true : false;
  }

  public function listErrors()
  {
    foreach ($this->errors as $error) {
      echo $error."\n"; // Output the error message
    }
  }
  

  public $lastTournaments;

  public function GetTournamentsJSON($parameters = array())
  {
    $callUrl = "https://api.challonge.com/v1/tournaments.json?";
    $parameters["api_key"] = $this->api_key;
    $callUrl .= http_build_query($parameters);
    $result = file_get_contents($callUrl);

    if ($result)
      $assocArray = json_decode($result, true);

    $this->lastTournaments = array();
    if ($assocArray && count($assocArray)) {
      foreach ($assocArray as $element) {
        $t = $element["tournament"];
        $creator = explode("_", $t["url"])[0];
        $this->lastTournaments[] = array_merge(
          $t,
          array(
            "creator" => $creator
          )
        );
      }
      return $this->lastTournaments;
    }

    return false;
  }

  public function TestPropertiesInTournament($tournament, $params)
  {
    if (count($params) == 0) return true;
    foreach ($params as $key => $value) {
      if (isset($tournament[$key]) && $tournament[$key] != $value)
        return false;
    }
    return true;
  }

  public function FilterTournamnets($params) {

    if(!$this->lastTournaments || count($this->lastTournaments) == 0)
      return false;

    $filtered = array();
    foreach($this->lastTournaments as $t) {
      if($this->TestPropertiesInTournament($t, $params)) 
        $filtered[] = $t;
    }

    return $filtered;
  }

  public function GetTournamentByName($name, $tournamentsSubset = array())
  {
    $tournaments = $this->lastTournaments;

    if(count($tournamentsSubset) != 0)
      $tournaments = $tournamentsSubset;

    if (!$tournaments || count($tournaments) == 0) return false;

    foreach ($tournaments as $t) {
      $tName = trim(strtolower($t["name"]));
      if ($name == $tName) return $t;
    }
    return false;
  }
  
  public $lastParticipants;

  public function GetParticipantsJSON($tournament_id){
	  	$callUrl = "https://api.challonge.com/v1/tournaments/";
	  	$callUrl .= $tournament_id;
	  	$callUrl .= "/participants.json?api_key=" . $this->api_key;
	  	
	  	$result = file_get_contents($callUrl);
  		
  		if($result)
  			$assocArray = json_decode($result, true);
  		if($assocArray && count($assocArray)) {
            foreach($assocArray as $key => &$t) {
              if(isset($t["participant"]) && $t["participant"]["active"] == 1) {
                $t = $t["participant"];
              }else{
                unset($assocArray[$key]);
              }
              // TODO: extract telegram user data and IGN
            }
            $this->lastParticipants = array_values($assocArray);
  			return $this->lastParticipants;
  		}
  			
  		return false;
  }

  public function GetParticipantByName($username) {
    if(!$this->lastParticipants || count($this->lastParticipants) == 0)
      return false;
    foreach($this->lastParticipants as $p) {
      if($p["name"] == $username) {
        return $p;
      }
    }
    return false;
  }

  public function GetParticipantById($participant_id) {
    if(!$this->lastParticipants || count($this->lastParticipants) == 0)
      return false;
    foreach($this->lastParticipants as $p) {
      if($p["id"] == $participant_id) {
        return $p;
      }
    }
    return false;
  }

  public $lastMatches;

  public function GetMatchesJSON($tournament_id, $parameters) {
    $callUrl = "https://api.challonge.com/v1/tournaments/$tournament_id/matches.json?";
    $parameters["api_key"] = $this->api_key;
    $callUrl .= http_build_query($parameters);
    $result = file_get_contents($callUrl);

    if($result)
  			$assocArray = json_decode($result, true);
  		if($assocArray && count($assocArray)) {
  			foreach($assocArray as &$m) {
          $m = $m["match"];
        }
        $this->lastMatches = $assocArray;
  			return $this->lastMatches;
  		}
  			
  		return false;
  }

  public function GetOpponentInMatch($match, $participant_id) {
    if($match["player1_id"] == $participant_id)
      return $this->GetParticipantById($match["player2_id"]);

    if($match["player2_id"] == $participant_id)
      return $this->GetParticipantById($match["player1_id"]);

    return false;
  }

  public function GetMyTournaments($username)
  {
    $tournaments = $this->GetTournamentsJSON();
    $this->lastTournaments = array();

    if(!$tournaments || count($tournaments) == 0) {
      return $this->lastTournaments;
    }

    foreach($tournaments as &$t){
      $participants = $this->GetParticipantsJSON($t['id']);
      if(!$participants) continue;

      foreach($participants as $p) {
        if(isset($p['active']) && $p['active'] == 1
           && isset($p['name']) && strtolower(trim($p['name'])) == strtolower(trim($username))) {
          $t['participant_id'] = $p['id'];
          $this->lastTournaments[] = $t;
          break;
        }
      }
    }
    return $this->lastTournaments;
  }

  public function GetMyLastMatch($username) {
    // TODO: get tournamnets for only last 24 hours
    $runningTournaments = $this->GetTournamentsJSON(array(
      "state" => "in_progress"
    ));
    if(!$runningTournaments || count($runningTournaments) == 0) {
      return false;
    }

    for($i = count($runningTournaments) -1; $i >= 0; $i--) {
      $t = $runningTournaments[$i];
      // get participants
      $participants = $this->GetParticipantsJSON($t["id"]);
      if($participants == false)
          continue;
      // find user participant
      $user_participant = $this->GetParticipantByName($username);
      if($user_participant == false)
          continue;
      // get participant matches
      $matches = $this->GetMatchesJSON($t["id"], array(
          "state" => "open",
          "participant_id" => $user_participant["id"]
      ));
      if($matches && count($matches)) {
        // inject user participand id (one that called) into match array
        $matches[0]["user_participant"] = $user_participant;
        return $matches[0];
      }
      
    }
    return false;
  }

}
?>