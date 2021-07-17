use async_std::io;
use async_std::net::{TcpListener, TcpStream};
use async_std::prelude::*;
use async_std::task;
mod text_generator;
use std::sync::Arc;
use std::time::Instant;
mod markov;

fn main()-> io::Result<()> {

    let file_path = "D:\\Coding\\Rust\\sosurity\\data\\data_fixed.json";
    let iMarkovChain = Arc::new(markov::MarkovChain::new(
        text_generator::read_data_from_file(file_path).unwrap().chain
    ));


    task::block_on(async {
        let listener = TcpListener::bind("127.0.0.1:1488").await?;
        
        
        println!("Listening on {}", listener.local_addr()?);

        let mut incoming = listener.incoming();
        
        while let Some(stream) = incoming.next().await {
            let stream = stream?;
            let _data = Arc::clone(&iMarkovChain);
            task::spawn(async move {
                handle_connection(stream, _data).await.unwrap();
            });
        }
        Ok(())
    }) 

}


async fn handle_connection(mut stream :TcpStream, database: Arc<markov::MarkovChain>) -> io::Result<()>{
    let buffer_size = 128;
    let mut request_len = 0usize;

    let mut request_buffer = vec![];
    loop {
        let mut buffer = vec![0; buffer_size];
        match stream.read(&mut buffer).await{
            Ok(n) => {
                if n == 0 {
                    break;
                }else{
                    request_len += n;
                    if n < buffer_size{
                        request_buffer.append(&mut buffer[..n].to_vec());
                        break;
                    }else{
                        request_buffer.append(&mut buffer);
                    }
                }
            },
            Err(e) => {
                println!("Error in reading stream data: {:?}", e);
                break;
            }
        }
    }
    let mut contents = String::from_utf8(request_buffer).unwrap();
    let input_data = contents.split("\r\n\r\n").nth(1);
    let mut _post_data : Option<text_generator::IncomingUpdate> = None;
    match input_data{
        Some(x) =>{
            if x.len() > 1{
                let json_data = text_generator::parse_json(x);
                match json_data{
                    Ok(y) => {
                        //println!("Json data is fine!");
                        _post_data = Some(y);
                    }
                    Err(_) => {
                        println!("Json data is broken!");
                    }
                }
            }
        },
        None => {}
    }

    match _post_data{
        Some(x) => {
            let now = Instant::now();
            contents = database.generate_text(&x.message.text);
            println!("Generated total time: {:?}", now.elapsed());
        },
        None => {}
    }
    let response = format!("{}{}", "HTTP/1.1 200 OK\r\n\r\n", contents);
    stream.write(response.as_bytes()).await.unwrap();
    stream.flush().await.unwrap();
    Ok(())
}