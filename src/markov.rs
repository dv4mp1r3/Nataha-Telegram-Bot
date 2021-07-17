use std::collections::{HashMap};
use serde_json::{Value};
use rand::Rng;
use rand::prelude::*;
use rand::seq::SliceRandom;
use indexmap::IndexMap;
use std::time::Instant;
use rand::distributions::{WeightedIndex};

#[path = "text_generator.rs"] mod text_generator;

pub struct MarkovChain{
    pub chain: HashMap<String,Value>,
    pub iChain: IndexMap<String, Value>
}

impl MarkovChain{
    pub fn new(hm : HashMap<String, Value>) -> MarkovChain{
        let now = Instant::now();
        let mut im = IndexMap::new();
        let _hm = hm;
        for value in _hm.clone(){
            im.insert(value.0, value.1);
        }
        println!("Time spend to convertation :{:?}", now.elapsed());
        MarkovChain{
            chain: _hm,
            iChain: im
        }
    }
    fn find_init_word(&self, word : &str) -> Vec<String>{
        let _ch = self.get_from_chain(word);
        match _ch {
            Some(x) => {
                self.get_random_from_markov_chain(x)
            }
            None => {
                self.find_random_chain()
            }
        }
    }
    fn get_from_chain(&self, word : &str) -> Option<&Value>{
        self.chain.get(base64::encode(word).as_str())
    }

    fn vector_to_human(&self, vec : &Vec<&String>) -> String{
        let mut _s : Vec<String> = vec![];
        for item in vec{
            _s.push(String::from_utf8(base64::decode(item).unwrap()).unwrap());
        }
        _s.join(" ")
    }

    fn get_random_from_markov_chain(&self, x : &Value) -> Vec<String>{
        let mut rng = thread_rng();
        let mut continue_vec : Vec<String> = vec![];
        let mut hash_vec: Vec<(&String, i64)> = vec![];
        for (key,val) in x.as_object().unwrap(){
            hash_vec.push((key, val.as_i64().unwrap()));
        }
        let distributed_by_weight = WeightedIndex::new(hash_vec.iter().map(|item| item.1)).unwrap();
        let _w = &hash_vec[distributed_by_weight.sample(&mut rng)].0;
        continue_vec.push(_w.to_string());
        continue_vec
    }
    fn find_random_chain(&self) -> Vec<String>{
        let mut rng = thread_rng();

        let range = rand::thread_rng().gen_range(0..self.chain.len());
        let start_word = self.iChain.get_index(range);
        let _s: Vec<String> = vec![];
        let mut start_sentence : Vec<String> = vec![];
        match start_word {
                None =>{}
                Some(x) => {
                    start_sentence.push(x.0.to_string());
                    let mut hash_vec: Vec<(&String, i64)> = vec![];
                    for (key,val) in x.1.as_object().unwrap(){
                        hash_vec.push((key, val.as_i64().unwrap()));
                    }
                    let distributed_by_weight = WeightedIndex::new(hash_vec.iter().map(|item| item.1)).unwrap();
                    let _w = &hash_vec[distributed_by_weight.sample(&mut rng)].0;
                    start_sentence.push(_w.to_string());
                }
        }
        start_sentence
    }

    fn get_non_action_words<'a>(&'a self, v : &'a  Vec<&str>) -> Vec<&str>{
        let action_match  = vec!["сосур", "сасур"];
        let mut available_words : Vec<&str> = vec![];
        if v.len() > 1{
            for word in v{
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
        available_words
    }

    pub fn continue_sentence_from_chain(&self, word : &String) -> Vec<String>{
        let _ch = self.chain.get(word);
        match _ch{
            Some(x) => {
                self.get_random_from_markov_chain(x)
            }
            None => {
                self.find_random_chain()
            }
        }
    }

    pub fn generate_text(&self, incoming_message : &String) -> String{
        let start_words:Vec<&str> = incoming_message.split_ascii_whitespace().collect();
        let available_words:Vec<&str> = self.get_non_action_words(&start_words);
        let mut start_word : &str = "";
        if available_words.len() > 0{
            start_word = available_words.choose(&mut rand::thread_rng()).unwrap();
        }
        let mut sentence : Vec<String> = vec![];
        if start_word != "" {
            sentence.append(&mut self.find_init_word(start_word));
        }else{
            sentence.append(&mut self.find_random_chain());
        }

        while sentence.join(" ").len() < 300{
            let word = sentence.last().unwrap();
            sentence.append(&mut self.continue_sentence_from_chain(&word));
        }
        let s : Vec<String> = sentence.iter().map(|word| String::from_utf8(base64::decode(word).unwrap()).unwrap()).collect();
        String::from(s.join(" "))
    }

}
