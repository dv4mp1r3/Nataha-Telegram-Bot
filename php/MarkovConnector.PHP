<?php
class MarkovUNIX{
    public string $socket_addr = "";
    public bool $valid = false;
    public int $timeout;
    public $connection; // Since PHP 7.4 cant have "resource" type we just going to use dynamic var, so nice to see PHP became better and better /s
    public function __construct(string $socket_addr, int $timeout)
    {
        $this->socket_addr = $socket_addr;
        $this->timeout = $timeout;
        
    }

    public function connect(){
        $sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->timeout, "usec" => 0)); // IDK wtf is this, found it in google
        try{
            $fp = socket_connect($sock, $this->socket_addr);
        }catch(Exception $e){
            print("Unable to connect : ". $e->getMessage(). "\n");
            exit();
        }
        if(!$fp){
            echo "unable to connect, fuck off";
            exit();
            return false;
        }
        $this->valid = true;
        $this->connection = $sock;
    }
    public function check_connection(){
        if (!$this->valid){
            $this->connect();
        }
    }
    public function send_data(string $data) : string{
        $this->check_connection();
        $len = strlen($data);
        if ($len == 0){
            $data = " ";
        }
        $sent = socket_write($this->connection, $data, strlen($data));
        if ($sent === false) {
            sprintf( "Unable to write to socket: %s", socket_strerror(socket_last_error()));
        }
        $read = socket_read( $this->connection, 1024 );
        return $read;
    }

    public function send_array(array $data){
        $this->check_connection();
    }

    public function send_multiple(string $data, int $n) : void{
        $this->check_connection();
        $start = microtime(true);
        for ($i = 0; $i < $n; $i++){
            $sent = socket_write($this->connection, $data, strlen($data));
            if ($sent === false) {
                sprintf( "Unable to write to socket: %s", socket_strerror(socket_last_error()));
            }
            $read = socket_read( $this->connection, 1024 );
        }
        $time_elapsed_secs = microtime(true) - $start;
        print("Took {$time_elapsed_secs} for {$n} iterations");
    }

    public function bench(int $interval){
        $i = 0;
        $start = microtime(true);
        while(true){
            $this->send_data("test");
            $time_elapsed_secs = microtime(true) - $start;
            $i += 1;
            if ($time_elapsed_secs > $interval){
                break;
            }
        }
        print("made {$i} requests (aka generating text) in ~{$interval} secs\n");
        
    }
}

function convert_data_to_human_read($data){
    $data = explode(" ", $data);
    $output = "";
    foreach($data as $item){
        $output .= base64_decode($item);
        $output .= " ";
    }
    return $output;
}
