use serde_json::{Result, Value, Number};
use serde::{Deserialize, Serialize};
use std::{fs::File};
use std::io::BufReader;
use std::path::Path;
use std::collections::{HashMap, BTreeMap};
use base64;
use std::time::Instant;
use std::error::{self, Error};
use rand::prelude::*;
use rand::seq::SliceRandom;
use rand::distributions::{WeightedIndex,Uniform};
use indexmap::IndexMap;

#[derive(Serialize, Deserialize, Debug)]
pub struct Chain{
    pub chain: HashMap<String, Value>
}

pub struct IndexMapChain{
    pub chain : IndexMap<String, Value>
}

impl IndexMapChain{
    pub fn convert(&mut self, init_value : HashMap<String, Value>){
        let now = Instant::now();
        for Value in init_value{
            self.chain.insert(Value.0, Value.1);
        }
        println!("Time spend to convertation :{:?}", now.elapsed());
    }
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


pub fn add_data_to_database(input_text : &String){
    if input_text.len() < 500{
        let words = input_text.split(" ");
        for word in words{
            if word.len() > 3 && word.len() < 50{
                add_word(word);
            }
        }
    }
}
pub fn add_word(word : &str){
    println!("Adding {:?} to database", word);
}


pub fn read_data_from_file<P: AsRef<Path>>(path: P) -> Result<Chain>{
    let file = File::open(path).unwrap();
    let reader = BufReader::new(file);
    let val:Chain = serde_json::from_reader(reader)?;
    Ok(val)
}
pub fn random_text(input_text : &String, database : &Chain) -> String{
    let mut _tmp = input_text.clone();
    let mut _words : Vec<&str> = _tmp.split(" ").collect();
    let action_match  = vec!["сосур", "сасур"];
    let mut available_words : Vec<&str> = vec![];
    if _words.len() > 1{
        for word in _words{
            let mut _f = false;
            for _match in &action_match{
                if word.contains(_match) == true{
                    _f = true;
                }
            }
            
            if _f == false{
                available_words.push(word);
            }
        }
    }
    //println!("{:?}", available_words);

    
    let mut output_string : String = String::new();
    let mut last_word:String = String::new();
    for word in available_words{
        match intersect_database_recursive(word, database){
            Ok(out) =>{
                last_word = out;
                output_string.push_str(" ");
                output_string.push_str(&last_word.clone().as_str());
                
            }
            Err(x) => {}
        }
    }
   
    while output_string.len() < 300{
        /*
        if output_string.len() == 0{
            let mut rng = rand::thread_rng();
            let mut vals = database.chain.iter();
            let rnd = Uniform::from(1..20);
            let _rnd_sample = rnd.sample(&mut rng);
            let mut _i: i32 = 0;
            let mut random_val = vals.next();
            
            while _i < _rnd_sample{
                random_val = vals.next();
                _i += 1;
            }
            println!("Random first value:{:?}", random_val);
            
            match random_val {
                Some(val) => {
                    println!("Random value:{:?}", random_val);
                },
                None => {}
            }
            output_string.push_str("a");
        }*/
        match intersect_database_recursive(&last_word.as_str(), database){
            Ok(out) =>{
                output_string.push_str(" ");
                output_string.push_str(out.as_str());
                last_word = base64::encode(out);
            }
            Err(x) => {
                let mut _rnd_wrd = output_string.split_ascii_whitespace().choose(&mut rand::thread_rng());
                if last_word == _rnd_wrd.unwrap(){
                    let now = Instant::now();
                    last_word = get_random_word(database); // ~100us
                    println!("Spend on get_random_word: {:?}", now.elapsed());
                }else{
                    last_word = String::from(_rnd_wrd.unwrap());
                }
                
            }
        }
    }
    println!("{:?}", output_string);
    return String::from("aa");
}
pub fn get_random_word(database : &Chain) -> std::string::String{
    let mut rng = rand::thread_rng();
    let mut vals = database.chain.iter();
    let rnd = Uniform::from(1..20);
    let _rnd_sample = rnd.sample(&mut rng);
    let mut _i: i32 = 0;
    let mut random_val = vals.next();
    
    while _i < _rnd_sample{
        random_val = vals.next();
        _i += 1;
    }
    
    match random_val {
        Some(val) => {
            println!("Random first value:{:?}", val.0);
            return String::from_utf8(base64::decode(val.0.clone()).unwrap()).unwrap();
        },
        None => {
            return String::from("раст_хуета");
        }
    }
}
pub fn intersect_database_recursive(word : &str, database : &Chain) -> std::result::Result<std::string::String, &'static str>{
    let mut rng = thread_rng();
    let chain = database.chain.get(&base64::encode(word));
    match chain{
        Some(x) => {
            let mut hash_vec: Vec<(String, i64)> = vec![];
            for (key, val) in x.as_object().unwrap(){
                hash_vec.push((String::from_utf8(base64::decode(key).unwrap()).unwrap(), val.as_i64().unwrap()));
            }
            let distributed_by_weight = WeightedIndex::new(hash_vec.iter().map(|item| item.1)).unwrap();
            let _w = &hash_vec[distributed_by_weight.sample(&mut rng)].0;
            Ok(String::from(_w))
        },
        None => {
            Err("Not found")
        }
    }
}

pub fn check_if_bot_called(input_text : &String) -> bool{
    let mut _tmp = input_text.clone();
    let mut _c = _tmp.to_lowercase();
    let _first_word = _c.split(" ").nth(0).unwrap();
    let action_match : Vec<&str> = vec!["сосур", "сасур"];
    for word in action_match{
        if _first_word.contains(word){
            return true;
        }
    }
    return false;
}


pub fn parse_json(data: &str) -> Result<IncomingUpdate>{
    let value : IncomingUpdate = serde_json::from_str(data)?;
    Ok(value)
}