use std::collections::HashMap;
use std::fs::File;
use std::hash::Hash;
use std::io::BufReader;
use evmap_derive::ShallowCopy;
use serde::Deserialize;
use serde::de::Deserializer;

use rand::rngs::StdRng;
use rand::distributions::WeightedIndex;
use rand::SeedableRng;
use rand::distributions::Distribution;
use std::sync::Arc;

use crate::evmap_wrapper::EvMapHandlerAsync;



#[derive(Deserialize)]
pub struct WordChain{
    #[serde(deserialize_with="value_to_items")]
    pub chain : EvMapHandlerAsync<String, ChainObjects>,
    #[serde(default="create_empty_rng_because_serde_cant_see_i_already_implementing_it_inside_custom_deserializer_method_that_he_asks_for_and_skip_doesnt_help")]
    #[serde(skip)]
    pub rng_thread : StdRng,
    #[serde(skip)]
    pub keys: Vec<String>
}

impl WordChain{
    pub fn new<'a>(path : &str) -> Result<Self, Box<dyn std::error::Error>>{
        let file = File::open(path)?;
        let reader = BufReader::new(file);
        let mut val:WordChain = serde_json::from_reader(reader)?;
        val.rng_thread = SeedableRng::from_entropy();
        let cloned = val.chain.reader.clone();
        val.keys = EvMapHandlerAsync::keys(&cloned);
        println!("Keys: {}", val.keys.len());
        if val.keys.len() == 0{
            Err("Json file is empty? Quiting")?
        }else{
            Ok(val)
        }
    }
}
#[derive(Debug, ShallowCopy)]
pub struct ChainObjects{
    pub items : Vec<(String, i64)>,
    pub weights: Arc<WeightedIndex<i64>>
}


use std::hash::Hasher;
impl Hash for ChainObjects{
    fn hash<H: Hasher>(&self, state: &mut H) {
        self.items.hash(state);
    }
}
impl PartialEq for ChainObjects {
    fn eq(&self, _other: &Self) -> bool {
        false
    }
}
impl Eq for ChainObjects {}


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
                        weights: Arc::new(x)
                    } )
            }
            Err(e) => {
                println!("Unable to create WeightedIndex for element with error: {:?}", e);
                None
            }
        }


    }


    pub fn get_random_sample_by_weight_async(item : &ChainObjects,thread_rng : &mut StdRng) -> String{
        let unknown = item.weights.sample(thread_rng);
        let item = &item.items[unknown].0;
        item.to_owned()
    }

}

fn value_to_items<'a, 'de, D>(deserializer: D) -> 
    Result<EvMapHandlerAsync<String, ChainObjects>, D::Error>
    where D: Deserializer<'de>
{
    let mut ch : HashMap<String, ChainObjects> = HashMap::new();
    let mut _evmap : EvMapHandlerAsync<String, ChainObjects> = EvMapHandlerAsync::new();
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
                    ch.insert(k.to_string(), co);
                }
                
            }
            _ => {}
        }
    }
    let writer_clone = _evmap.writer.clone();
    for (k,v) in ch{
        EvMapHandlerAsync::insert_item(&writer_clone, k, v);
    }
    Ok(_evmap)
}