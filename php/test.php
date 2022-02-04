<?php
include("MarkovConnector.PHP");
$test_data = base64_encode("анализ");
$con = new MarkovUNIX("/tmp/sosurity-gen.sock", 5);
$con->connect();
$start = microtime(true);
for ($i = 0; $i < 10000; $i++){
    $data = $con->send_data($test_data);
    //$output = convert_data_to_human_read($data);
    //print($output.PHP_EOL);
    
}
print(microtime(true) - $start.PHP_EOL);
//$ valgrind --tool=callgrind --dump-instr=yes --collect-jumps=yes --simulate-cache=yes /home/john/Coding/Rust/Nataha-Telegram-Bot/target/debug/sosurity
?>



