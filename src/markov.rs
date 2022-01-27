use std::collections::HashMap;
use std::fs::File;
use std::io::BufReader;
use rand::prelude::SliceRandom;
use serde::Deserialize;
use serde::de::Deserializer;

use rand::rngs::StdRng;
use rand::distributions::WeightedIndex;
use rand::SeedableRng;
use rand::distributions::Distribution;



#[derive(Deserialize, Debug)]
pub struct WordChain{
    #[serde(deserialize_with="value_to_items")]
    pub chain : HashMap<String, ChainObjects>,
    #[serde(default="create_empty_rng_because_serde_cant_see_i_already_implementing_it_inside_custom_deserializer_method_that_he_asks_for_and_skip_doesnt_help")]
    #[serde(skip)]
    pub rng_thread : StdRng,
    #[serde(skip)]
    pub keys: Vec<String>
}

impl WordChain{
    pub fn new(path : &str) -> Result<Self, Box<dyn std::error::Error>>{
        let file = File::open(path)?;
        let reader = BufReader::new(file);
        let mut val:WordChain = serde_json::from_reader(reader)?;
        val.rng_thread = SeedableRng::from_entropy();
        val.keys = val.chain.keys().cloned().map(|k| k).collect::<Vec<String>>();
        if val.keys.len() == 0{
            Err("Json file is empty? Quiting")?
        }else{
            Ok(val)
        }
    }
    pub fn get_random_init_word(&mut self) -> String{
        self.keys.choose(&mut self.rng_thread).unwrap().to_string()
    } 
    pub fn generate_answer(&mut self, income_text : &str) -> Result<String, Box<dyn std::error::Error>>{
        let mut answer : String = "".to_string();

        match &self.chain.get(income_text){
            Some(x) => {
                let str = x.get_random_sample_by_weight(&mut self.rng_thread);
                answer.push_str(&str);
                answer.push_str(" ");
            }
            None => {
                answer.push_str(&self.get_random_init_word());
                answer.push_str(" ");
            }
        }
        while answer.len() < 300{
            answer.push_str(&self.continue_sentence(&answer));
            answer.push_str(" ");
        }

        Ok(answer)
    }

    pub fn continue_sentence(&mut self, sentence : &str) -> String{
        let words = sentence.split_whitespace().collect::<Vec<&str>>();
        let lword = words.last();
        match lword{
            Some(last_word) => {
                match &self.chain.get(&last_word.to_string()){
                    Some(x) => {
                        let str = x.get_random_sample_by_weight(&mut self.rng_thread);
                        str
                    }
                    None => {
                        self.get_random_init_word()
                    }
                }
            }
            None => {
                self.get_random_init_word()
            }
        }


    }
}

#[derive(Debug)]
pub struct ChainObjects{
    pub items : Vec<(String, i64)>,
    pub weights: WeightedIndex<i64>
}


fn create_empty_rng_because_serde_cant_see_i_already_implementing_it_inside_custom_deserializer_method_that_he_asks_for_and_skip_doesnt_help() -> StdRng{
    SeedableRng::from_entropy()
}
impl ChainObjects{
    pub fn new(items : Vec<(String, i64)>) -> Option<Self>{
        let ws = WeightedIndex::new(
            items.iter()
            .map(|item| item.1));
        match ws{
            Ok(x) => {
                Some( Self{
                        items,
                        weights: x
                    } )
            }
            Err(e) => {
                println!("Unable to create WeightedIndex for element with error: {:?}", e);
                None
            }
        }


    }

    pub fn get_random_sample_by_weight(&self, thread_rng : &mut StdRng) -> String{
        let unknown = self.weights.sample(thread_rng);
        let item = &self.items[unknown].0;
        item.to_owned()
    }

}

fn value_to_items<'de, D>(deserializer: D) -> 
    Result<HashMap<String, ChainObjects>, D::Error>
    where D: Deserializer<'de>
{
    let mut items : HashMap<String, ChainObjects> = HashMap::new();
    let value: serde_json::Value = serde::Deserialize::deserialize(deserializer)?;
    let object = value
    .as_object()
    .ok_or(serde::de::Error::custom("unable to extract data from json file"))?;
    for (k, v) in object.iter(){
        match v.as_object(){
            Some(data) => {
                let objs : Vec<(String, i64)> = data.iter().map(|(s, val)|{
                    (s.to_string(), val.as_i64().unwrap()) // You already must be sure that your data is actually integers
                }).collect();
                if let Some(co) = ChainObjects::new(objs){
                    items.insert(k.to_string(), co);
                }
                
            }
            _ => {}
        }
    }
    Ok(items)
}

