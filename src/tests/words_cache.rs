use tokio::net::{UnixListener, UnixStream};

use crate::data::parser::Database;
use std::collections::HashMap;
use std::{future, sync::Arc};

fn gen_string(size: usize) -> String {
    use rand::{distributions::Alphanumeric, Rng};
    rand::thread_rng()
        .sample_iter(&Alphanumeric)
        .take(size)
        .map(char::from)
        .collect()
}

/*
pub fn get_chain_reader() -> Result<ChainReader, Box<dyn std::error::Error>> {
    let reader = self.inner.try_read_for(self.timeout).ok_or("Poisoned")?;
    Ok(reader)
}*/


async fn references() {
    use parking_lot::RwLock;

    dbg!("Test");
    //42.9 mb+-
    let words = (0..10_000)
        .map(|_| Arc::new(gen_string(4096)))
        .collect::<Vec<Arc<String>>>();
    let mut map = RwLock::new(HashMap::new());
    let mut map = map.write();
    for i in 0..words.len() {
        map.insert(words[i].clone(), i);
    }
    tokio::time::sleep(std::time::Duration::from_secs(5)).await;
}
#[tokio::test]
async fn words_cached() -> Result<(), Box<dyn std::error::Error>> {
    use std::thread;
    let db = Arc::new(Database::new("../../sosurity/data.json")?);
    let total = std::time::Instant::now();
    let num_cores = std::thread::available_parallelism().unwrap().get();
    println!("Starting with {} threads", num_cores);
    let num_threads = num_cores;
    for x in 0..num_threads {
        let _db = db.clone();
        let handler = thread::spawn(move || {
            let now = std::time::Instant::now();
            let iterations = 100;
            let sentences = 1000;
            for _ in 0..iterations {
                for _ in 0..sentences {
                    let x = gen(&_db);
                }
            }
            println!(
                "Thread: [{}] {} sentences for {} ms",
                x,
                sentences * iterations,
                now.elapsed().as_millis()
            );
            println!("Total: {} ms", total.elapsed().as_millis());
        });
    }

    tokio::time::sleep(std::time::Duration::from_secs(20)).await;
    Ok(())
}

fn basic_test() -> Result<(), Box<dyn std::error::Error>> {
    let db = Database::new("/dev/shm/data.json")?;
    let a = gen(&db);
    let json = serde_json::to_string(&a)?;
    dbg!(json);
    Ok(())
}

async fn socket_test() -> Result<(), Box<dyn std::error::Error>> {
    use tokio::net::UnixStream;
    let total = std::time::Instant::now();
    let num_cores = std::thread::available_parallelism().unwrap().get();
    println!("Starting with {} threads", num_cores);
    for x in 0..num_cores {
        let _ = tokio::spawn(async move {
            let stream = UnixStream::connect("/tmp/socket/sosu.sock").await.unwrap();
            let now = std::time::Instant::now();
            let iterations = 100;
            let sentences = 1000;
            for _ in 0..iterations {
                for _ in 0..sentences {
                    let x = ping_pong(&stream).await;
                }
            }
            println!(
                "Thread: [{}] {} sentences for {} ms",
                x,
                sentences * iterations,
                now.elapsed().as_millis()
            );
            println!("Total: {} ms", total.elapsed().as_millis());
        });
    }
    tokio::time::sleep(std::time::Duration::from_secs(20)).await;
    Ok(())
}
async fn ping_pong(stream: &UnixStream) -> Result<(), Box<dyn std::error::Error>> {
    loop {
        stream.writable().await?;
        match stream.try_write(b"0LzQsNGC0Yw=") {
            Ok(x) => {
                break;
            }
            Err(ref e) if e.kind() == std::io::ErrorKind::WouldBlock => {
                continue;
            }
            Err(e) => {
                return Err(e.into());
            }
        }
    }
    let mut msg: Vec<u8> = vec![];
    loop {
        let mut buf = vec![0; 4096];
        stream.readable().await?;
        match stream.try_read(&mut buf) {
            Ok(0) => {
                break;
            }
            Ok(n) => {
                buf.truncate(n);
                msg.append(&mut buf);
            }
            Err(ref e) if e.kind() == std::io::ErrorKind::WouldBlock => {
                continue;
            }
            Err(e) => {
                return Err(e.into());
            }
        }
    }
    println!("{}", msg.len());
    Ok(())
}
fn gen(db: &Database) -> Vec<String> {
    db.gen("0LzQsNGC0Yw= 0LXQsdCw0Ls=".to_string()).unwrap()
}
