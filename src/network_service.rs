use tokio::net::UnixListener;
use crate::markov::WordChain;
use std::io;
use std::str;
//use std::time::{Duration, Instant};

pub struct UnixSocketHandler{
    pub unix_listener : UnixListener,
    pub chain : WordChain
}

impl UnixSocketHandler{
    pub fn new(path : &String, chain : WordChain) -> Result<Self, Box<dyn std::error::Error>>{
        let listener = UnixListener::bind(path)?;
        Ok(Self{
            unix_listener: listener,
            chain
        })
    }

    pub async fn start_accept_connections(mut self) -> Result<(), Box<dyn std::error::Error>>{
        loop {
            match self.unix_listener.accept().await {
                Ok((stream, _addr)) => {
                    
                    loop{
                        stream.readable().await?;
                        let mut msg = vec![0; 1024]; // allocatting each time receiving text, not cool tho!
                        match stream.try_read(&mut msg) {
                            Ok(n) => {
                                if n == 0{

                                    break;
                                }
                                msg.truncate(n);
                                let response;
                                if let Ok(str) = str::from_utf8(&msg){ // Checking if incoming message is correct UTF-8, otherwise calling random init word
                                    response = self.chain.generate_answer(str).unwrap(); 
                                }else{
                                    // Should not normally happened, but anyway
                                    let rword = self.chain.get_random_init_word();
                                    response = self.chain.generate_answer(rword.as_str()).unwrap(); 
                                }
                                match stream.try_write(response.as_bytes()){
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
                                return Err(e.into());
                            }
                        }
                        
                    }
                
                }
                Err(e) => { 
                    println!("Error occured {:?}", e);
                 }
            }
        }
    }
}