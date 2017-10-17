<?php

$webhook_url = "https://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . "/BOT.php";

function SendReady() {
  global $result;
  if ($_SERVER["REQUEST_METHOD"] != "POST")  {
    $result = "not a post"; 
    return false;
  }
  if (!isset($_POST["webhook_url"])) {
    $result = "no webhook url";
    return false;
  }
  if (!filter_var($_POST["webhook_url"], FILTER_VALIDATE_URL)) {
    $result = "non valid url";
    return false;
  }
  if(!isset($_POST["bot_token"])) {
    $result = "no bot token";
    return false;
  }
  return true;
}

if(SendReady()) {
  $bot_token = $_POST["bot_token"];

  $webhook_url = $_POST["webhook_url"];

  $API_URL = 'https://api.telegram.org/bot' . $bot_token .'/';
  $method = 'setWebhook';
  $parameters = array('url' => $webhook_url);
  $url = $API_URL . $method. '?' . http_build_query($parameters);
  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  $result = curl_exec($handle);
}

if(!$result) $result = "Error";

?>
<p><?php echo $result; ?></p>
<h2>Register a Webhook</h2>
<form style="width:100%" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
<p>Webhook URL: </p>
  <input type="text" style="width:100%"  name="webhook_url" required="true" value="<?php echo $webhook_url;?>">
  <p>Webhook URL: </p>
  <input type="text" style="width:100%"  name="bot_token" required="true">
  <br><br>
  <input type="submit" name="submit" value="Submit">  
</form>