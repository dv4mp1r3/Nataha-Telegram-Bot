<?php
// NA 75 linii nado menyat nick bota
require("markov-php/Markov.class.php");
$writeHumanReadable = true;

$input = file_get_contents("php://input"); // Retrieve information sent by webhook
$sJ = json_decode($input); // decode JSON supplied by webhook to PHP array
if(!is_object($sJ) || !isset($sJ->message->chat->id)) die("err");

$filterRegEx = [
	"urlFilter" 			=> "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
	"punctiationFilter"		=> "/(?<!\w)[.,!]/",
	"newlineFilter"			=> "/\r|\n/",
];

$chatID = $sJ->message->chat->id; // copy for easier access
$rawText = $sJ->message->text;
	if(strlen($rawText) > 300){
        die(); // Anti-flood (LOL)
	}

if(file_exists($chatID . ".mar")) {
	$chain = file_get_contents($chatID . ".mar"); // read serialized object with existing chain
	$markov = unserialize($chain);
} else {
	$markov = new Markov; // create a new chain
}

$nataha_name = mb_strtolower($rawText);
if(preg_match("/натах(.*)сука|натах(.*)тупая|натах(.*)несешь/i",$nataha_name) == true){ // Oskorblenya
	    $reply['method'] = "sendSticker";
        $reply['chat_id'] = $chatID;
        $reply['sticker'] = "CAADAgADCQADaJpdDDa9pygUaeHvAg";
        header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}
if(preg_match("/ахах/i",$nataha_name) == true){ // lol sticker s ispancem
		$reply['method'] = "sendSticker";
        $reply['chat_id'] = $chatID;
		$reply['sticker'] = "CAADAgADnQADaJpdDK2h3LaVb7oGAg";
		header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}


if(preg_match("/php|пых/i",$nataha_name) == true){ // PHP-GOVNO
		$reply['method'] = "sendSticker";
        $reply['chat_id'] = $chatID;
		$reply['sticker'] = "CAADAgADEwADmqwRGPffQIaMmNCbAg";
		header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}
if(preg_match("/нат(.*)блог/i", $nataha_name) == true){ // chisto reklama
        $reply['method'] = "sendMessage";
        $reply['chat_id'] = $chatID;
        $reply['text'] = "https://www.natalia-blog.ml/";
        header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}

if(preg_match("/ната(.*)|натах|наталия|наталья|наташа|наташка|касперский|анекдот/i",$nataha_name) == true){ // Na chto generit text
        $text = $markov->generateText(100);
        $reply['method'] = "sendMessage";
        $reply['chat_id'] = $chatID;
        $reply['text'] = $text;
        header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}

if(preg_match("/reply_to_message\"(.*)username\":\"WeatherDcBot\"/i",$input) == true){ // CHANGE USERNAME IF YOU NEED SO
	$text = $markov->generateText(100);
	if(!$text) $text = "Мне нечего сказать. Мало данных";
	$reply['method'] = "sendMessage";
	$reply['chat_id'] = $chatID;
	$reply['text'] = $text;
	
	header("Content-Type: application/json");
	echo json_encode($reply);
	die();
}

	$preparedText = strtolower($rawText); // copy the input and convert it to lowercase
	foreach($filterRegEx as $pattern) $preparedText = preg_replace($pattern, " ", $preparedText); // apply the filter regexes above
	$markov->train($preparedText); // add the text to the chain
	
	$chain = serialize($markov); // serialize the markov object to a string
	file_put_contents($chatID . ".mar", $chain); // write it to disk
	if($writeHumanReadable) file_put_contents($chatID . ".mar.txt", print_r($markov, true)); // if human writable is specified, also write a print_r output
	die();

