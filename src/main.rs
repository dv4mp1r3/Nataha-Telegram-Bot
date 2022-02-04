mod markov;
mod network_service;
use std::io::BufRead;
mod evmap_wrapper;
mod markov_async;

use std::fs;

#[tokio::main]
async fn main(){
    println!("Started..");
    let data_path = std::env::args().nth(2).unwrap_or("/home/john/Coding/Rust/Data/data_fixed.json".to_string());
    let socket_path = std::env::args().nth(1).unwrap_or("/tmp/sosurity-gen.sock".to_string());

    if let Ok(_) = fs::metadata(&socket_path){
        addr_in_use(&socket_path);
    }

    match markov::WordChain::new(&data_path.as_str()){
        Ok(mut _wc) => {
            let unix_socket = network_service::UnixSocketHandler::new(&socket_path).unwrap();// Should be like this, panic! if unable to create unix socket
            let main_thread = unix_socket.start_accept_connections(_wc).await;
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
                println!("Unable to delete file {} , do it yourself, error: {}", &socket_path, e);
            }
        }
    }
}
