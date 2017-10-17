<?php

define("API_ENDPOINT_BASE", "https://api.telegram.org/bot");
define("API_ANSWER_INLINE_QUERY", "/answerInlineQuery");

class TG {
    public $bot_token = ""; // set  
    public $inlineQueryAnswer;

    function __construct($_bot_token, $type) {
      $this->bot_token = $_bot_token;
      if($type == "inline") {
        $this->inlineQueryAnswer = new InlineQueryAnswer();
      }
    }

    function GetAnswerInlineQueryEndpoint() {
        return API_ENDPOINT_BASE . $bot_token . API_ANSWER_INLINE_QUERY;
    }
    public function CreateJSON($obj) {
        return json_encode($obj);
    }

    
}

class InlineQueryAnswer {
    public $inline_query_id;
    public $results;
    public $cache_time;

    function __construct() {
        $this->inline_query_id = uniqid();
        $this->results = array();
        $this->cache_time = 300;
    }

    public function AddResult($element) {
        array_push($this->results, $element);
    }
}

class PhotoResult {
    public $type;
    public $id;
    public $photo_url;
    public $thumb_url;
    public $title;
    public $caption;
    public $photo_width;
    public $photo_height;

    function __construct() {
        $this->type = "photo";
        $this->id = uniqid();
    }
}

