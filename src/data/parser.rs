use parking_lot::RwLock;
use rand::distributions::WeightedIndex;
use rand::{prelude::Distribution, seq::IteratorRandom, Rng};
use std::sync::Arc;
use std::time::Duration;
use std::{collections::HashMap, fs};

pub struct DbItem {
    pub word_index: usize,
    pub weight: usize,
}
///
/// Data:
///     key: {
///        word: weight
///     }
///     1 : {
///         2: 69
///     }
///
///
pub struct WordsCached {
    inner: RwLock<HashMap<Arc<String>, usize>>,
    words: RwLock<Vec<Arc<String>>>,
    timeout: Duration,
}

pub struct Database {
    pub words: WordsCached,
    pub items: RwLock<HashMap<usize, Vec<DbItem>>>,
    options: DatabaseOptions,
}
pub struct DatabaseOptions {
    pub timeout: Duration,
    pub max_words: usize,
    pub min_words: usize,
}

impl Database {
    pub fn gen(&self, input: String) -> Result<Vec<String>, Box<dyn std::error::Error>> {
        let words_reader = &self.words.get_words_reader()?;
        let words = input.split_whitespace();
        let word = words.choose(&mut rand::thread_rng());
        let first: usize;
        match word {
            Some(x) => {
                let chain_reader = &self.words.get_chain_reader()?;
                first = match WordsCached::get_by_string(chain_reader, &x.to_string()) {
                    Ok(indx) => indx,
                    Err(_) => WordsCached::get_random_word(&words_reader)?,
                }
            }
            None => {
                first = WordsCached::get_random_word(&words_reader)?;
            }
        }
        let mut words = vec![first];
        let main_reader = &self.get_main_reader()?;
        while words.len() < rand::thread_rng().gen_range(10..100) {
            words.push(Self::continue_gen(
                main_reader,
                *words.last().ok_or("Fuck you")?,
            )?);
        }
        Ok(self.words.join_by_ids(words)?)
    }
    pub fn get_main_reader(&self) -> Result<MainReader, Box<dyn std::error::Error>> {
        let main_chain = self
            .items
            .try_read_for(self.options.timeout)
            .ok_or("Poisoned")?;
        Ok(main_chain)
    }

    pub fn get_main_writer(&self) -> Result<MainWriter, Box<dyn std::error::Error>> {
        let main_chain = self
            .items
            .try_write_for(self.options.timeout)
            .ok_or("Poisoned")?;
        Ok(main_chain)
    }

    pub fn continue_gen(
        reader: &MainReader,
        index: usize,
    ) -> Result<usize, Box<dyn std::error::Error>> {
        let items = match reader.get(&index) {
            Some(x) => x,
            None => {
                let i = reader
                    .keys()
                    .choose(&mut rand::thread_rng())
                    .ok_or("пиздец")?;
                reader.get(i).ok_or("пиздец")?
            }
        };
        let new_word = Self::weight(items)?;
        Ok(new_word)
    }
    pub fn weight(items: &Vec<DbItem>) -> Result<usize, Box<dyn std::error::Error>> {
        let w = WeightedIndex::new(items.iter().map(|item| item.weight))?;
        let rand_item = &items[w.sample(&mut rand::thread_rng())];
        Ok(rand_item.word_index)
    }
}
type MainReader<'a> =
    parking_lot::lock_api::RwLockReadGuard<'a, parking_lot::RawRwLock, HashMap<usize, Vec<DbItem>>>;
type MainWriter<'a> = parking_lot::lock_api::RwLockWriteGuard<
    'a,
    parking_lot::RawRwLock,
    HashMap<usize, Vec<DbItem>>,
>;

type WordsReader<'a> = parking_lot::lock_api::RwLockReadGuard<
    'a,
    parking_lot::RawRwLock,
    Vec<Arc<std::string::String>>,
>;
type WordsWriter<'a> = parking_lot::lock_api::RwLockWriteGuard<
    'a,
    parking_lot::RawRwLock,
    Vec<Arc<std::string::String>>,
>;

type ChainReader<'a> = parking_lot::lock_api::RwLockReadGuard<
    'a,
    parking_lot::RawRwLock,
    HashMap<Arc<std::string::String>, usize>,
>;

type ChainWriter<'a> = parking_lot::lock_api::RwLockWriteGuard<
    'a,
    parking_lot::RawRwLock,
    HashMap<Arc<std::string::String>, usize>,
>;

impl WordsCached {
    pub fn new(timeout: Duration) -> Self {
        Self {
            inner: RwLock::new(HashMap::new()),
            words: RwLock::new(vec![]),
            timeout,
        }
    }

    pub fn get_words_reader(&self) -> Result<WordsReader, Box<dyn std::error::Error>> {
        let reader = self.words.try_read_for(self.timeout).ok_or("Poisoned")?;
        Ok(reader)
    }
    pub fn get_words_writer(&self) -> Result<WordsWriter, Box<dyn std::error::Error>> {
        let writer = self.words.try_write_for(self.timeout).ok_or("Poisoned")?;
        Ok(writer)
    }
    pub fn get_chain_reader<'b>(&'b self) -> Result<ChainReader<'b>, Box<dyn std::error::Error>> {
        let reader = self.inner.try_read_for(self.timeout).ok_or("Poisoned")?;
        Ok(reader)
    }

    pub fn get_chain_writer<'b>(&'b self) -> Result<ChainWriter, Box<dyn std::error::Error>> {
        let writer = self.inner.try_write_for(self.timeout).ok_or("Poisoned")?;
        Ok(writer)
    }

    pub fn get_random_word(reader: &WordsReader) -> Result<usize, Box<dyn std::error::Error>> {
        let indx = rand::thread_rng().gen_range(0..reader.len());
        Ok(indx)
    }
    pub fn len(&self) -> (usize, usize) {
        return (self.inner.read().len(), self.words.read().len());
    }
    pub fn insert(&self, value: String) -> Result<usize, Box<dyn std::error::Error>> {
        let mut map = self.inner.try_write_for(self.timeout).ok_or("Posioned")?;
        let word = map.get(&value);
        match word {
            None => {
                let word = Arc::new(value);
                let mut words_vec = self.words.try_write_for(self.timeout).ok_or("Poisoned")?;
                let index = words_vec.len();
                words_vec.push(word.clone());
                map.insert(word, index);
                return Ok(index);
            }
            Some(item) => {
                return Ok(*item);
            }
        }
    }
    pub fn get(&self, id: usize) -> Result<String, Box<dyn std::error::Error>> {
        let words = self.words.try_read_for(self.timeout).ok_or("Poisoned")?;
        let output = words.get(id).ok_or("Data race occured")?;
        Ok(output.to_string())
    }

    pub fn get_by_string(
        reader: &ChainReader,
        value: &String,
    ) -> Result<usize, Box<dyn std::error::Error>> {
        let index = reader.get(value).ok_or("Not found")?;
        Ok(*index)
    }
    pub fn join_by_ids(&self, ids: Vec<usize>) -> Result<Vec<String>, Box<dyn std::error::Error>> {
        let words = self.words.try_read_for(self.timeout).ok_or("Poisoned")?;
        let mut output = vec![];
        for id in ids {
            match words.get(id) {
                None => {
                    println!("Joining is broken : {}", &id);
                }
                Some(word) => {
                    output.push(word.to_string());
                }
            }
        }
        Ok(output)
    }
}

impl Database {
    pub fn size(&self) -> (usize, (usize, usize)) {
        let chain_len = self.items.read().len();
        let words = self.words.len();
        println!("Chain len : {}", chain_len);
        println!("Total unique words: {}", words.1);
        (chain_len, words)
    }
    pub fn new(file_path: &str) -> Result<Self, Box<dyn std::error::Error>> {
        println!("[Parsing]\tReading file : {}", file_path);
        let file = fs::File::open(file_path)?;
        println!("[Parsing]\tFile is exists, reading...");
        let timeout = Duration::from_secs(1);
        let options = DatabaseOptions {
            timeout: timeout,
            max_words: 100,
            min_words: 10,
        };
        let json: serde_json::Value = serde_json::from_reader(file)?;
        let chain = json.as_object();
        let words_cache = WordsCached::new(timeout);
        let mut items_export = HashMap::new();
        let data = chain
            .and_then(|x| x.get("chain"))
            .and_then(|x| {
                return Some(x);
            })
            .ok_or("invalid file")?
            .as_object()
            .ok_or("Not an object?")?;

        println!("[Parsing]\tInitial parsing, might take a while");
        let now = std::time::Instant::now();
        data.into_iter().for_each(|(key, values)| {
            let main_index = match words_cache.insert(key.to_owned()) {
                Ok(index) => index,
                Err(_) => {
                    dbg!("error while inserting");
                    0
                }
            };

            let mut objects: Vec<DbItem> = vec![];
            for map in values.as_object() {
                map.into_iter().for_each(|(key, val)| {
                    let word_index = match words_cache.insert(key.to_owned()) {
                        Ok(index) => index,
                        Err(_) => 0,
                    };
                    let weight = match val {
                        serde_json::Value::Number(number) => number.as_u64().unwrap_or(0),
                        serde_json::Value::String(str) => match str.parse::<u64>() {
                            Ok(a) => a,
                            Err(_) => {
                                println!("Unable to parse value: {}, returning 0", val);
                                0
                            }
                        },
                        _ => {
                            dbg!(val);
                            dbg!("FUCK IS THAT?");
                            0
                        }
                    };
                    let weight = weight as usize;
                    objects.push(DbItem { word_index, weight });
                });
            }
            items_export.insert(main_index, objects);
        });
        let elapsed = now.elapsed().as_millis();
        println!("[Parsing]\tDone ({} ms)", elapsed);

        Ok(Self {
            words: words_cache,
            items: RwLock::new(items_export),
            options,
        })
    }
}
