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
    $out = array_rand($data['chain']); // initial word
    while ($out = weighAndSelect($data['chain'][$out])) {
        $text[] = base64_decode($out);
        if (count($text) > $maxWords) {
            break;
        }
    }
    return customTextProcessing(implode(" ", $text));
}

/**
 * Своя логика обработки сгенерированного текста
 * @param string $text результат выполнения generateText
 * @return string
 */
function customTextProcessing($text)
{
    $text = str_ireplace(' слава ', ' слава @uberhahn ', $text);
    return $text;
}

function sendMessage($chatId, $text, $method = 'sendMessage')
{
    $reply['method'] = $method;
    $reply['chat_id'] = $chatId;
    $reply['text'] = $text;//"ЧТО-ПОШЛО НЕ ТАК!!!! ДАМП ПОСЛЕДНЕГО СООБЩЕНИЯ\n:". json_encode($sJ);
    echo json_encode($reply);
}

/**
 * генерация/обновление цепи
 * @param string $message
 * @param array $data
 * @return boolean|int
 */
function train($message, $data) {
    $array = explode(" ", $message);

    foreach ($array as $num => $val) {
        $val = base64_encode($val);
        if (!$val)
        {
            continue;
        }
        $commit = (isset($data['chain'][$val]) ? $data['chain'][$val] : array()); // if there is already a block for this word, keep it, otherwise create one        
        if (empty($array[$num + 1])) {
            continue; // if this word is EOL, continue to the next word
        }
        $next = $array[$num + 1]; // the next word after the one currently selected
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

function isReply($message)
{
    if (empty($message['message']['reply_to_message']))
    {
        return false;
    }
    
    if (empty($message['message']['reply_to_message']['from']))
    {
        return false;
    }
    
    return $message['message']['reply_to_message']['from']['username'] === IDENT;
    
}

$writeHumanReadable = false;

if (!defined('IS_DEBUG') || !IS_DEBUG)
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
    "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
    "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
    "/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",    
];

$nataha_name = mb_strtolower($rawText);
header("Content-Type: application/json");
if ((string)$chatID !== AVAILABLE_CHAT_ID)
{
    sendMessage($chatID, "Я НЕ БУДУ ТУТ РАБОТАТЬ!");
    die();
}
foreach ($regExpData as $regExp => $value) 
{
    if (preg_match($regExp, $nataha_name))
    {
        sendMessage($chatID, $text, 'sendSticker');
        die();
    }
}

if (preg_match("/нат(.*)блог/i", $nataha_name) == true) { // chisto reklama
    sendMessage($chatID, "НЕТУ");
    die();
} 

$fp = null;
$fileName = "data.json";
$tryCount = 0;
$chain = json_decode(file_get_contents($fileName), true);
while($chain == false )
{
    if ($tryCount === MAX_DB_READ_TRY)
    {
        throw new \Exception("Can't get file content for $fileName");
    }
    $tryCount++;
    $chain = json_decode(file_get_contents($fileName), true);
    usleep(FLOCK_SLEEP_INTERVAL);
}

if (
    isReply($sJ) ||
    preg_match("/ресеп(.*)|ресепшен|ресептион|ресепшин|ресепшинъ|ресепшенъ/i", $nataha_name) == true) {

    $text = generateText(100, $chain);
    if (!$text)
    {
        $text = "Мне нечего сказать. Мало данных";
    }  
    sendMessage($chatID, $text);
}
else 
{         
    $preparedText = strtolower($rawText);
    foreach ($filterRegEx as $pattern) {
        $preparedText = preg_replace($pattern, " ", $preparedText);
    }
    $putData = "false";
    if (!empty($preparedText))
    {
        $putData = json_encode(train($preparedText, $chain));
        if ($putData !== "false")
        {
            file_put_contents($fileName, $putData, LOCK_EX);
            if ($writeHumanReadable) {
                file_put_contents($chatID . ".json.txt", print_r($chain, true));
            }
        }
        else
        {
            if (defined('IS_DEBUG') && IS_DEBUG)
            {
                sendMessage($chatID, "ЧТО-ПОШЛО НЕ ТАК!!!! ДАМП ПОСЛЕДНЕГО СООБЩЕНИЯ\n:". json_encode($sJ));
            }
        } 
    }  
    header('Content-Type: text/html; charset=utf-8');
}
  