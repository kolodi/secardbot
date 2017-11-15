<?php
class TG
{
    public $bot_token = ""; // set  

    function __construct($_bot_token)
    {
        $this->bot_token = $_bot_token;
    }
    public function CreateJSON($obj)
    {
        return json_encode($obj);
    }

    public function SendInlineAnswerToTG($data_string)
    {
        $result;
        try {

            $ch = curl_init("https://api.telegram.org/bot" . $this->bot_token . "/answerInlineQuery");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ));
            
            $result = curl_exec($ch);

            if (FALSE === $result)
            throw new Exception(curl_error($ch), curl_errno($ch));

        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        return $result;
    }


}

class InlineQueryAnswer
{
    public $inline_query_id;
    public $results;
    public $cache_time;

    function __construct()
    {
        $this->inline_query_id = uniqid();
        $this->results = array();
        $this->cache_time = 300;
    }

    public function AddResult($element)
    {
        array_push($this->results, $element);
    }
}

class PhotoResult
{
    public $type;
    public $id;
    public $photo_url;
    public $thumb_url;
    public $title;
    public $caption;
    public $photo_width;
    public $photo_height;

    function __construct()
    {
        $this->type = "photo";
        $this->id = uniqid();
        $this->thumb_url = "";
        $this->caption = "";
        $this->photo_width = 344;
        $this->photo_height = 480;
    }
}

