use serde_json::{Result, Value, Number};
use std::collections::{HashMap};
use serde::{Deserialize, Serialize};
use std::{fs::File};
use std::io::BufReader;
use std::path::Path;

#[derive(Serialize, Deserialize, Debug)]
pub struct Chain{
    pub chain: HashMap<String, Value>
}
#[derive(Serialize, Deserialize, Debug)]
pub struct Message {
    pub message_id: Number,
    pub from: HashMap<String, Value>,
    pub chat: HashMap<String, Value>,
    pub date: Number,
    pub text: String
}
#[derive(Serialize, Deserialize, Debug)]
pub struct IncomingUpdate{
    pub update_id: Number,
    pub message: Message,
}
pub fn read_data_from_file<P: AsRef<Path>>(path: P) -> Result<Chain>{
    let file = File::open(path).unwrap();
    let reader = BufReader::new(file);
    let val:Chain = serde_json::from_reader(reader)?;
    Ok(val)
}

pub fn parse_json(data: &str) -> Result<IncomingUpdate>{
    let value : IncomingUpdate = serde_json::from_str(data)?;
    Ok(value)
}