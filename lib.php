<?php
define("API_PATH", "https://api.telegram.org/bot");
class TG
{
    public $bot_token = ""; // set  
    private $apiWithToken;

    function __construct($_bot_token)
    {
        $this->bot_token = $_bot_token;
        $this->apiWithToken = API_PATH . $_bot_token;
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

            if (false === $result)
                throw new Exception(curl_error($ch), curl_errno($ch));

        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        return $result;
    }

    public function SendMessage($data_string)
    {
        $result;
        try {

            $ch = $this->PrepareCurlPost($data_string, "/sendMessage");

            $result = curl_exec($ch);

            if (false === $result)
                throw new Exception(curl_error($ch), curl_errno($ch));

        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        return $result;
    }

    public function SendSimpleMessage($chat_id, $message) {
        $msg = new TextMessage($chat_id, $message);
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }

    public function SendPromptMessage($chat_id, $message, $reply_to_message_id) {
        $msg = new PromptMessage($chat_id, $message, $reply_to_message_id);
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }

    public function SendPromptWithButtonsInColumn($chat_id, $message, $reply_to_message_id, $buttons) {
        $msg = new MessageWithButtons($chat_id, $message, $reply_to_message_id, $buttons);
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }

    public function GetLastUpdate()
    {
        $result = false;
        try {
            $data = array("offset" => -1);
            $data_string = json_encode($data);
            $ch = $this->PrepareCurlPost($data_string, "/getUpdates");
            $response = curl_exec($ch);
            if (false === $response)
                throw new Exception(curl_error($ch), curl_errno($ch));
            else
                $result = $response;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }

    function PrepareCurlPost($data_string, $method)
    {
        $ch = curl_init($this->apiWithToken . $method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
        return $ch;
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

class TextMessage
{
    public $chat_id;
    public $text;

    function __construct($chat_id, $text)
    {
        $this->chat_id = $chat_id;
        $this->text = $text;
    }
}

class ReplyMarkup
{
    public $force_reply = true;
  	public $selective = true;
}

class ReplyButtonsInColumn extends ReplyMarkup
{
    public $keyboard = array();
    public $one_time_keyboard = true;

    function __construct($buttons) {
        foreach($buttons as $b_text) {
            array_push($this->keyboard, array($b_text));
        }

    }
}

class PromptMessage extends TextMessage
{
    public $reply_to_message_id;
    public $disable_notification = true;
    public $reply_markup;

    function __construct($chat_id, $text, $reply_to_message_id) {
        $this->chat_id = $chat_id;
        $this->text = $text;
        $this->reply_to_message_id = $reply_to_message_id;
        $this->reply_markup = new ReplyMarkup();
    }
}

class MessageWithButtons extends PromptMessage 
{
    function __construct($chat_id, $text, $reply_to_message_id, $buttons) {
        $this->chat_id = $chat_id;
        $this->text = $text;
        $this->reply_to_message_id = $reply_to_message_id;
        $this->reply_markup = new ReplyButtonsInColumn($buttons);
    }
}

