<?PHP
header('Content-Type: text/html; charset=utf-8');
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

if(file_exists($chatID . ".mar") == true){
	$chain = json_decode(file_get_contents($chatID . ".mar"),true);
}else{
	$chain = array();
}
	function generateText($maxWords,$data) {
		if(empty($data)) die('fuck off');
		$out = array_rand($data); // initial word
		while($out = weighAndSelect($data[$out])) {		
			$text[] = base64_decode($out);
			if(count($text) > $maxWords) break;
		}
		
		return implode(" ", $text);
	}
	
	function train($message,$data) {
		if(empty($message)) return false;
		$array = explode(" ", $message);
		
		foreach($array as $num => $val) {
			$val = base64_encode($val);
			$commit = (isset($data[$val]) ? $data[$val] : array()); // if there is already a block for this word, keep it, otherwise create one
			$next = $array[$num + 1]; // the next word after the one currently selected
			if(empty($next)) continue; // if this word is EOL, continue to the next word
			$next = base64_encode($next);
			if(isset($commit[$next])) $commit[$next]++; // if the word already exists, increase the weight
			else $commit[$next] = 1; // otherwise save the word with a weight of 1
			$data[$val] = $commit; // commit to the chain
		}
		return $data;
	}

	function weighAndSelect($block) {
		if(empty($block)) return false;
		
		foreach($block as $key => $weight) {
			for($i = 1; $i <= $weight; $i++) $tmp[] = $key;
		}
		
		$rand = array_rand($tmp);
		return $tmp[$rand];
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
        $text = generateText(100,$chain);
        $reply['method'] = "sendMessage";
        $reply['chat_id'] = $chatID;
        $reply['text'] = $text;
        header("Content-Type: application/json");
        echo json_encode($reply);
        die();
}

if(preg_match("/reply_to_message\"(.*)username\":\"WeatherDcBot\"/i",$input) == true){ // CHANGE USERNAME IF YOU NEED SO
	$text = generateText(100,$chain);
	if(!$text) $text = "Мне нечего сказать. Мало данных";
	$reply['method'] = "sendMessage";
	$reply['chat_id'] = $chatID;
	$reply['text'] = $text;
	
	header("Content-Type: application/json");
	echo json_encode($reply);
	die();
}

	$preparedText = strtolower($rawText);
	foreach($filterRegEx as $pattern) $preparedText = preg_replace($pattern, " ", $preparedText); 
	file_put_contents($chatID . ".mar", json_encode(train($preparedText,$chain)));
	if($writeHumanReadable) file_put_contents($chatID . ".mar.txt", print_r($chain, true));
	die();
