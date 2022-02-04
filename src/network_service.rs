use tokio::net::UnixListener;
use tokio::net::UnixStream;
use crate::markov::WordChain;
use rand::SeedableRng;
use std::io;

use std::str;
use std::sync::{Arc, Mutex};
use std::vec;
use crate::markov_async::MarkovAsync;
//use std::time::{Duration, Instant};
pub struct UnixSocketHandler{
    pub unix_listener : UnixListener,
}

impl UnixSocketHandler{
    pub fn new(path : &String) -> Result<Self, Box<dyn std::error::Error>>{
        let listener = UnixListener::bind(path)?;
        Ok(Self{
            unix_listener: listener,
        })
    }
    pub async fn start_accept_connections<'a>(self, chain : WordChain) -> Result<(), Box<dyn std::error::Error>>{
        let _writer = Arc::new(Mutex::new(chain.chain.writer));
        let keys = Arc::new(Mutex::new(chain.keys));
        loop {
            match self.unix_listener.accept().await {
                Ok((stream, _addr)) => {
                    let reader = chain.chain.reader.clone();
                    let vec_keys = keys.clone(); // Blocking?
                    tokio::spawn(async move {
                        //MarkovAsync::get_item(reader, "abcd", rng_clone).unwrap();
                        loop{
                            stream.readable().await;
                            let mut msg = vec![0; 1024]; // allocatting each time receiving text, not cool tho!
                            match stream.try_read(&mut msg) {
                                Ok(n) => {
                                    if n == 0{
                                        break;
                                    }
                                    msg.truncate(n);
                                    let response;
                                    if let Ok(input_text) = str::from_utf8(&msg){
                                        response = MarkovAsync::generate_answer(&vec_keys, &reader.clone(), input_text).unwrap();
                                    }else{
                                        let rng_thread = SeedableRng::from_entropy();
                                        let rword = MarkovAsync::get_random_init_word(&vec_keys, rng_thread);
                                        response = MarkovAsync::generate_answer(&vec_keys,&reader.clone(),rword.as_str()).unwrap();
                                    }
                                    match stream.try_write(&response.as_str().as_bytes()){
                                        Ok(_x) => {
                                            
                                        },
                                        Err(e) => {
                                            println!("Error occured while send data back, prob you fucked up with client: {:?}", e);
                                        }
                                    }
                                }
                                Err(ref e) if e.kind() == io::ErrorKind::WouldBlock => {
                                    continue;
                                }
                                Err(e) => {
                                    println!("Closing connection! {:?}", e);
                                    break;
                                }
                            }
                            
                        } 
                    });
                
                }
                Err(e) => { 
                    println!("Error occured {:?}", e);
                 }
            }
        }
    }

}