use sosurity::data::parser::Database;
use tokio::net::UnixListener;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let args: Vec<_> = std::env::args().collect();
    if args.len() < 3 {
        println!("./sosurity /file/path.json /tmp/socket/sosu.sock");
        return Err("я твою мать пиздошил ногами".into());
    }
    let file_path = "./data.json".to_string();
    let socket_path = "/tmp/socket/sosu.sock".to_string();
    let file_path = args.get(1).unwrap_or(&file_path);
    let socket_path = args.get(2).unwrap_or(&socket_path);

    let db = Database::new(&file_path)?;
    let _ = std::fs::remove_file(socket_path);
    let listener = UnixListener::bind(socket_path)?;
    loop {
        let (socket, _) = listener.accept().await?;
        let input = match read_data(&socket).await {
            Ok(input) => input,
            Err(e) => {
                write_error(&socket).await?;
                dbg!(e);
                continue;
            }
        };
        match db.gen(input) {
            Ok(sentence) => {
                let json_data = if let Ok(json_data) = serde_json::to_string(&sentence) {
                    json_data
                } else {
                    write_error(&socket).await?;
                    dbg!("Error while serializing data");
                    continue;
                };

                match write_data(&socket, json_data.as_bytes()).await {
                    Ok(x) => {}
                    Err(e) => {
                        println!("Error while writing good data : ");
                        dbg!(e);
                    }
                }
            }
            Err(e) => {
                write_error(&socket).await?;
                dbg!(e);
                continue;
            }
        };
    }
}
async fn write_error(stream: &tokio::net::UnixStream) -> Result<(), Box<dyn std::error::Error>> {
    write_data(stream, b"0x503").await?;
    Ok(())
}
async fn write_data(
    stream: &tokio::net::UnixStream,
    data: &[u8],
) -> Result<usize, Box<dyn std::error::Error>> {
    loop {
        stream.writable().await?;
        match stream.try_write(data) {
            Ok(bytes) => {
                return Ok(bytes);
            }
            Err(ref e) if e.kind() == std::io::ErrorKind::WouldBlock => {
                continue;
            }
            Err(e) => {
                return Err(e.into());
            }
        }
    }
}
async fn read_data(stream: &tokio::net::UnixStream) -> Result<String, Box<dyn std::error::Error>> {
    let mut msg = vec![];
    loop {
        stream.readable().await?;
        let mut data = vec![0; 4096];
        match stream.try_read(&mut data) {
            Ok(n) => {
                data.truncate(n);
                msg.append(&mut data);
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
    let string = String::from_utf8(msg)?;

    Ok(string)
}
