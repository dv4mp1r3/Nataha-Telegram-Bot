mod markov;
mod network_service;
//use std::io::{BufRead};


//use std::fs;
//use std::time::{Duration, Instant};
#[tokio::main]
async fn main(){

    
    let data_path = std::env::args().nth(2).unwrap_or("/home/john/Coding/Rust/Nataha-Telegram-Bot/data_fixed.json".to_string());
    //let text_data = "0YXRg9GP0YHQtQ==";

    //let mut wc : markov::WordChain;
    match markov::WordChain::new(&data_path.as_str()){
        Ok(mut _wc) => {
            let socket_path = std::env::args().nth(1).unwrap_or("/tmp/sosurity-gen.sock".to_string());
            let unix_socket = network_service::UnixSocketHandler::new(&socket_path, _wc).unwrap(); // Should be like this, panic! if unable to create unix socket
            let main_thread = unix_socket.start_accept_connections().await;
            match main_thread{
                Ok(_x) => {
                    println!("Ended");
                }
                Err(e) => {
                    println!("Error while spawning main thread occured : {:?}", e);
                } 
            }
        }
        Err(e) => {
            println!("Unable to read data file {} Error: {}", &data_path, e);
        }
    }

 


    
    /*     if let Err(e) = create_socket(&socket_path){
        println!("Starting at {}", &socket_path);
        let kind = e.kind();
        if kind == ErrorKind::AddrInUse{
            addr_in_use(&socket_path);
        }else{
            println!("Critical error occured, quiting : {}", e);
        }
    }
    
    */

}
/* 
fn bench(wc : &mut markov::WordChain, number: i64){
    let mut i = 0;
    while i < number{
        wc.generate_answer("benchmark").unwrap();
        i = i + 1;
    }
}
fn addr_in_use(socket_path : &String) {

    println!("OS returned that {} is in use, I can try to delete it if you want", &socket_path);
    println!("Press Y to delete, otherwise I dont give a fuck, solve it yourself");

    let stdin = std::io::stdin();
    let mut line = String::new();
    stdin.lock().read_line(&mut line).expect("Could not read line");
    line = line.trim().to_string();

    if line == "Y" || line=="y"{
        println!("rm -rf /");
        match fs::remove_file(&socket_path){
            Ok(_) => {
                println!("File {} should be deleted, at least OS returned that it was deleted", &socket_path);
            }
            Err(e) => {
                println!("Unable to delete file {} , do it yourself", &socket_path);
            }
        }
    }
}
*/