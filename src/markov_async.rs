use crate::evmap_wrapper::EvMapHandlerAsync;
use evmap::ReadHandle;
use crate::markov::ChainObjects;
use rand::rngs::StdRng;
use rand::prelude::SliceRandom;
use std::sync::{Arc, Mutex};
use rand::SeedableRng;
pub struct MarkovAsync;

impl MarkovAsync{

    pub fn get_random_init_word(keys : Vec<String>, mut rng_thread : StdRng) -> String{
        keys.choose(&mut rng_thread).unwrap().to_string()
    }

    pub fn generate_answer(keys: Arc<Mutex<Vec<String>>>, cloned : ReadHandle<String, ChainObjects>, income_text: &str) -> Result<String, Box<dyn std::error::Error>>{
        let mut rng_thread = SeedableRng::from_entropy();
        let mut answer : String = String::new();
        match EvMapHandlerAsync::get_item(&cloned, &income_text.to_string()){
            Some(x) => {
                let str = ChainObjects::get_random_sample_by_weight_async(x.as_ref(), &mut rng_thread);
                answer.push_str(&str);
                answer.push_str(" ");
            }
            None => {// Locking only if we really need it
                let locked_keys = keys.lock().unwrap().clone(); // Locking only if we really need it
                let init_word = MarkovAsync::get_random_init_word(locked_keys, rng_thread);
                answer.push_str(&init_word);
                answer.push_str(" ");
            }
        }
        while answer.len() < 300{
            let locked_keys = keys.clone();
            let cloned_r = cloned.clone();
            let rng = SeedableRng::from_entropy();
            answer.push_str(MarkovAsync::continue_sentence(locked_keys, &answer, cloned_r, rng).as_str());
            answer.push_str(" ");
        }

        Ok(answer)
    }

    pub fn continue_sentence(keys: Arc<Mutex<Vec<String>>>, sentence : &str, cloned_reader: ReadHandle<String, ChainObjects>, mut rng_thread : StdRng) -> String{
        let words = sentence.split_whitespace().collect::<Vec<&str>>();
        let lword = words.last();
        match lword{
            Some(last_word) => {
                match EvMapHandlerAsync::get_item(&cloned_reader, &last_word.to_string()){
                    Some(blocked_object) => {
                        let str = ChainObjects::get_random_sample_by_weight_async(blocked_object.as_ref(), &mut rng_thread);
                        str
                    }
                    None => {
                        let locked_keys = keys.lock().unwrap().clone(); // Locking only if we really need it
                        MarkovAsync::get_random_init_word(locked_keys, rng_thread)
                    }
                }
            }
            None => {
                let locked_keys = keys.lock().unwrap().clone(); // Locking only if we really need it
                MarkovAsync::get_random_init_word(locked_keys, rng_thread)
            }
        }
    }

}