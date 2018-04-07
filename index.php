<?php
include_once('config.php');
include_once('bot.php');
// database
function getLastAudio($chat_id){
    $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
    $result=mysqli_query($connect,"SELECT * FROM ".SERVER_TABLE." WHERE chat_id=".$chat_id);
    $row=mysqli_fetch_assoc($result);
    $out[0] = $row["last_audio_title"];
    $out[1] = $row["last_audio_singer"];
    $out[2] = $row["done"];
    mysqli_close($connect);
    return $out;
}
function setLastAudio($chat_id,$audio_title,$audio_singer){
    $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
    $run = "REPLACE INTO ".SERVER_TABLE." VALUES ('".$chat_id."', '".$audio_title."', '".$audio_singer."' , 0)";
    mysqli_query($connect,$run);
    mysqli_close($connect);
}
// check if the song is already answered
function isDone($chat_id){
    $lastAudio = getLastAudio($chat_id);
    return $lastAudio[2];
}
function setDone($chat_id){
    $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
    mysqli_query($connect,"UPDATE ".SERVER_TABLE." SET done=1 WHERE chat_id=".$chat_id);
    mysqli_close($connect);
}
// scoreboard
function getScores($chat_id,$user_id){
    $out = [];
    $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
    $result=mysqli_query($connect,"SELECT * FROM ".SERVER_TABLE_SCORE." WHERE chat_id=".$chat_id." AND user_id=".$user_id);
    if($row=mysqli_fetch_assoc($result)){
      mysqli_close($connect);
      $out["score"] = (int)$row["score"];
      $out["id"] = $row["id"];
      $out["name"] = $row["user_name"];
      return $out;
    }else{
      mysqli_close($connect);
      $out["score"] = 0;
      $out["id"] = "null";
      $out["name"] = "null";
      return $out;
    }
}
// retrieve users participated in the game in a group
function getUsersInChat($chat_id){
  $out = [];
  $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
  $result=mysqli_query($connect,"SELECT user_id FROM ".SERVER_TABLE_SCORE." WHERE chat_id=".$chat_id);
  while($row=mysqli_fetch_array($result)){
    array_push($out,$row["user_id"]);
  }
  mysqli_close($connect);
  return $out;
}
// +1 score
function increaseScore($chat_id,$user_id,$user_name){
  $arr = getScores($chat_id,$user_id);
  $id = $arr["id"];
  $score = $arr["score"];
  $score += 1;
  $connect=mysqli_connect(SERVER_NAME,SERVER_USER,SERVER_PASS,SERVER_DB);
  if($id == "null"){
    $run = "INSERT INTO ".SERVER_TABLE_SCORE." VALUES (null,".$chat_id.",'".$user_name."',".$user_id.",".$score.")";
    mysqli_query($connect,$run);
  }else{
    $run = "REPLACE INTO ".SERVER_TABLE_SCORE." VALUES (".$id.",".$chat_id.",'".$user_name."',".$user_id.",".$score.")";
    mysqli_query($connect,$run);
  }
  mysqli_close($connect);
}
function showScoreboard($chat_id,$message_id){
    $out = "";
    $users = getUsersInChat($chat_id);
    foreach ($users as $user) {
      $arr = getScores($chat_id,$user);
      $data[$user] = (int)$arr["score"];
      $people[$user] = trim($arr["name"]);
    }
    // sort hash by value (score)
    arsort($data);
    $i=1;
    foreach ($data as $key => $value) {
      $out = $out."\n".$i."- ".$people[$key]." : ".$value;
      $i+=1;
    }
    apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => $out));
}
// audio
function random_audio(){
    $dir = SONGS_FOLDER;
    $files = glob($dir . '/*.*');
    $file = array_rand($files);
    return $files[$file];
}
function isSimilar($main,$guess){
    if (strpos($main, $gess) !== false || $main == $guess) {
      return true;
    }
}
function newGame($chat_id,$message_id){
    $random = random_audio();
    $audio_url = GAME_PATH.$random;
    $audio_name = substr($random, strpos($random, "/") + 1);
    $audio_name_arr = explode(".",$audio_name,2);
    $audio_name = $audio_name_arr[0];
    $audio_attr_arr = explode("_",$audio_name,2);
    $audio_singer = $audio_attr_arr[0];
    $audio_title = $audio_attr_arr[1];
    setLastAudio($chat_id,$audio_title,$audio_singer);
    apiRequest("sendMessage", array('chat_id' => $chat_id, 'text' => 'بازی جدید! جواب رو به فرمت خواننده-آهنگ به وویس پایین ریپلای کنید'));
    apiRequest("sendAudio", array('chat_id' => $chat_id, 'audio' => $audio_url));
}
function guess($chat_id,$message_id,$guess,$from){
    $lastAudio = getLastAudio($chat_id);
    $title = $lastAudio[0];
    $singer = $lastAudio[1];
    $done = $lastAudio[2];
    if($done == 1){
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => 'آهنگ گفته شده. با دستور /new یکی دیگه بازی کن'));
      return;
    }
    $guess = trim($guess);
    $guess_arr = explode("-",$guess,2);
    $singer_guess = $guess_arr[0];
    $title_guess = $guess_arr[1];
    $singer_guess = trim($singer_guess);
    $title_guess = trim($title_guess);
    $singer_guess = str_replace(" ","",$singer_guess);
    $title_guess = str_replace(" ","",$title_guess);
    $singer = str_replace(" ","",$singer);
    $title = str_replace(" ","",$title);
    if(isSimilar($singer,$singer_guess) && isSimilar($title,$title_guess)){
      $congrats = "آفرین ".($from["first_name"])."!"."\n"."کاملا درسته"."\n"."💃💃💪💪";
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => $congrats));
      setDone($chat_id);
      increaseScore($chat_id,((string)$from["id"]),($from["first_name"]." ".$from["last_name"]));
    }else if(isSimilar($singer,$singer_guess)){
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => 'خواننده ✅'));
    }else if(isSimilar($title,$title_guess)){
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => 'اسم آهنگ ✅'));
    }else{
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => "❌"));
    }
}
function giveAnswer($chat_id,$message_id){
      $lastAudio = getLastAudio($chat_id);
      $title = $lastAudio[0];
      $singer = $lastAudio[1];
      $output = "جواب: \n".$singer."-".$title;
      apiRequest("sendMessage", array('chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => $output));
      setDone($chat_id);
}
function processMessage($message) {
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    $from = $message['from'];
    if (isset($message['text'])) {
        $text = $message['text'];
        if (strpos($text, "/start") === 0) {
          $text = "سلام! این بات بازی شازم شب جمعه ست که باید از روی ۳۰ ثانیه اول هر آهنگ، خواننده و اسم آهنگ رو حدس بزنین. با دستور /new یک بازی جدید شروع کنید و اگر جوابو هیچکس نمیدونست، با دستور /answer جواب رو پیدا کنین.";
          apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $text));
        } else if (strpos($text, "/new") === 0) {
          newGame($chat_id,$message_id);
        } else if (strpos($text, "/scoreboard") === 0) {
          showScoreboard($chat_id,$message_id);
        } else if (strpos($text, "/answer") === 0) {
          giveAnswer($chat_id,$message_id);
        } else {
          guess($chat_id,$message_id,$text,$from);
        }
    } else {
        // not a text message
        // apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
}
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) {
    exit;
}
if (isset($update["message"])) {
    processMessage($update["message"]);
}
?>
