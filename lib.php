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

    private function escapeSpecialChars($text)
    {
        return str_replace(array("_"), array("\\_"), $text);
    }

    public function SendSimpleMessage($chat_id, $text, $disable_notification = true, $parse_mode = "Markdown") {
        if($parse_mode == "Markdown") $text = $this->escapeSpecialChars($text);
        $msg = new TextMessage($chat_id, $text);
        $msg->parse_mode = $parse_mode;
        $msg->disable_notification = $disable_notification;
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }

    public function SendPromptMessage($chat_id, $text, $reply_to_message_id) {
        $msg = new PromptMessage(
        	$chat_id,
            $this->escapeSpecialChars($text),
        	$reply_to_message_id, 
        	new ForceReply()
        );
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }

    public function SendPromptWithButtonsInColumn($chat_id, $text, $reply_to_message_id, $buttons) {
    	$msg = new PromptMessage(
    		$chat_id,
            $this->escapeSpecialChars($text),
    		$reply_to_message_id, 
    		new ReplyKeyboardMarkupButtonsInColumn($buttons)
    	);
        $msg_string = json_encode($msg);
        return $this->SendMessage($msg_string);
    }
    
    public function SendRemoveKeyboardMessage($chat_id, $text, $reply_to_message_id) {
    	$msg = new PromptMessage(
    		$chat_id,
            $this->escapeSpecialChars($text),
    		$reply_to_message_id, 
    		new ReplyKeyboardRemove()
    	);
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

//TELEGRAM API TYPES

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
    public $parse_mode = "Markdown";
    public $disable_web_page_preview = true;
    public $disable_notification = true;
    public $reply_to_message_id;

    function __construct($chat_id, $text)
    {
        $this->chat_id = $chat_id;
        $this->text = $text;
    }
}

class PromptMessage extends TextMessage
{
	public $reply_markup;
	function __construct($chat_id, $text, $reply_to_message_id, $reply_markup) {
		parent::__construct($chat_id, $text);
		$this->reply_to_message_id = $reply_to_message_id;
		$this->reply_markup = $reply_markup;
	}
}

class ForceReply
{
	public $force_reply = true;
	public $selective = true;
}

class ReplyKeybordRemove 
{	
	public $remove_keyboard = true;
	public $selective = true;
}

class ReplyKeyboardMarkup
{
	public $keyboard = array();
	public $resize_keyboard = false;
	public $one_time_keyboard = true;
	public $selective = true;
}

class ReplyKeyboardMarkupButtonsInColumn extends ReplyKeyboardMarkup
{
    function __construct($buttons) {
        foreach($buttons as $b_text) {
            array_push($this->keyboard, array($b_text));
        }

    }
}
