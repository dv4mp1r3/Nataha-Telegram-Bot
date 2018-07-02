<?php

/**
 * Генерация текста сообщения
 * @param integer $maxWords
 * @param array $data
 * @return string
 */
function generateText($maxWords, $data) {
    if (empty($data)) {
        throw new \Exception('Bad data format');
    }
    $out = array_rand($data); // initial word
    while ($out = weighAndSelect($data[$out])) {
        $text[] = base64_decode($out);
        if (count($text) > $maxWords) {
            break;
        }
    }
    return implode(" ", $text);
}

/**
 * генерация/обновление цепи
 * @param string $message
 * @param array $data
 * @return boolean|int
 */
function train($message, $data) {
    if (empty($message)) {
        return false;
    }
    $array = explode(" ", $message);

    foreach ($array as $num => $val) {
        $val = base64_encode($val);
        $commit = (isset($data['chain'][$val]) ? $data['chain'][$val] : array()); // if there is already a block for this word, keep it, otherwise create one
        $next = $array[$num + 1]; // the next word after the one currently selected
        if (empty($next)) {
            continue; // if this word is EOL, continue to the next word
        }
        $next = base64_encode($next);
        if (isset($commit[$next])) {
            $commit[$next] ++; // if the word already exists, increase the weight
        } else {
            $commit[$next] = 1; // otherwise save the word with a weight of 1
        }
        $data['chain'][$val] = $commit; // commit to the chain
    }
    return $data;
}

/**
 * 
 * @param type $block
 * @return boolean
 */
function weighAndSelect($block) {
    if (empty($block)) {
        return false;
    }

    foreach ($block as $key => $weight) {
        for ($i = 1; $i <= $weight; $i++) {
            $tmp[] = $key;
        }
    }

    $rand = array_rand($tmp);
    return $tmp[$rand];
}

$writeHumanReadable = true;

if (!defined('IS_DEBUG') && !IS_DEBUG)
{
    $input = file_get_contents("php://input"); // Retrieve information sent by webhook
    $sJ = json_decode($input, true); // decode JSON supplied by webhook to PHP array
}
else
{
    $sJ = $testData;
}

if (!is_array($sJ) || !isset($sJ['message']['chat']['id'])) {
    throw new \Exception('Bad data format');
}

$filterRegEx = [
    "urlFilter" => "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
    "punctiationFilter" => "/(?<!\w)[.,!]/",
    "newlineFilter" => "/\r|\n/",
];

$chatID = $sJ['message']['chat']['id']; // copy for easier access
$rawText = $sJ['message']['text'];
if (strlen($rawText) > MAX_MESSAGE_LENGTH) {
    throw new \Exception('Data length is bigger then 300');
}
$regExpData = [
    "/натах(.*)сука|натах(.*)тупая|натах(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
    "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
    "/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",    
];

$nataha_name = mb_strtolower($rawText);
header("Content-Type: application/json");
foreach ($regExpData as $regExp => $value) 
{
    if (preg_match($regExp, $nataha_name))
    {
        $reply['method'] = "sendSticker";
        $reply['chat_id'] = $chatID;
        $reply['sticker'] = $value;
        echo json_encode($reply);
        die();
    }
}

if (preg_match("/нат(.*)блог/i", $nataha_name) == true) { // chisto reklama
    $reply['method'] = "sendMessage";
    $reply['chat_id'] = $chatID;
    $reply['text'] = "https://www.natalia-blog.ml/";    
    echo json_encode($reply); 
    die();
} 

$fp = null;
$fileName = $chatID . ".json";
$chain = [];
// только здесь необходима работа с файлом
if (file_exists($chatID . ".json") == true) {
    $fileSize = filesize($fileName);
    
    $fp = fopen($fileName, 'r+');
    if (!$fp)
    {
        throw new \Exception('fopen error');
    }
    // ждем снятия блокировки
    while (!flock($fp, LOCK_EX))
    {
        usleep(FLOCK_SLEEP_INTERVAL);
    }
    
    if ($fileSize > 0)
    {
        $data = fread($fp, $fileSize);
        rewind($fp);
        $chain = json_decode($data, true);
    }
    
    if (!$chain)
    {
        $chain = [];
    }       
} 

if (
    preg_match("/reply_to_message\"(.*)username\":\"WeatherDcBot\"/i", $input) == true ||
    preg_match("/ната(.*)|натах|наталия|наталья|наташа|наташка|касперский|анекдот/i", $nataha_name) == true) {
    $text = generateText(100, $chain);
    if (!$text)
        $text = "Мне нечего сказать. Мало данных";
    $reply['method'] = "sendMessage";
    $reply['chat_id'] = $chatID;
    $reply['text'] = $text;
    echo json_encode($reply);
}
else 
{   
    header('Content-Type: text/html; charset=utf-8');
    $preparedText = strtolower($rawText);
    foreach ($filterRegEx as $pattern) {
        $preparedText = preg_replace($pattern, " ", $preparedText);
    }
    ftruncate($fp, 0);
    fwrite($fp, json_encode(train($preparedText, $chain)));
    fflush($fp);    
    flock($fp, LOCK_UN);
    fclose($fp);
    if ($writeHumanReadable) {
        file_put_contents($chatID . ".json.txt", print_r($chain, true));
    }
}

