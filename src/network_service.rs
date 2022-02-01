use tokio::net::UnixListener;
use crate::markov::WordChain;
use std::io;
use std::str;
use crate::evmap_wrapper::EvMapHandlerAsync;
use std::sync::{Arc, Mutex};
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
    pub async fn start_accept_connections<'a>(self, mut chain : WordChain) -> Result<(), Box<dyn std::error::Error>>{
        
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
                                if let Ok(input_text) = str::from_utf8(&msg){
                                    response = chain.generate_answer(input_text).unwrap();
                                    /* 
                                    let cloned = Arc::clone(&chain.chain.writer);
                                    
                                    tokio::spawn(async move {
                                        let mut items : Vec<(String, i64)> = vec![];
                                        items.push(("A".to_string(), 1));
                                        items.push(("B".to_string(), 2));
                                        let objs = crate::markov::ChainObjects::new(items).unwrap();
                                        EvMapHandlerAsync::insert_item(&cloned, "A".to_string(), objs);
                                        tokio::time::sleep(tokio::time::Duration::from_secs(2)).await;
                                        println!("Done with inserting");
                                    });
                                    */
                                }else{
                                    let rword = &chain.get_random_init_word();
                                    response = chain.generate_answer(rword.as_str()).unwrap();
                                }
                                match stream.try_write(response.as_str().as_bytes()){
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