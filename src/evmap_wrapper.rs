use evmap::{self, ReadHandle, WriteHandle};
use std::sync::{Arc, Mutex};

pub struct EvMapHandlerAsync<K : Eq + std::hash::Hash + Clone,V: Eq  + std::hash::Hash + evmap::ShallowCopy>{
    pub reader : ReadHandle<K, V>,
    pub writer : Arc<Mutex<WriteHandle<K,V>>>
}

impl <'a, K : Eq + std::hash::Hash + Clone,V: Eq  + std::hash::Hash + evmap::ShallowCopy> 
    EvMapHandlerAsync<K,V>
    {
    ///Lmao fuck off
    pub fn new<'b>() -> Self{
        let (reader, writer)  : (ReadHandle<K,V>, WriteHandle<K,V>) = evmap::new();
        Self{
            reader,
            writer: Arc::new(Mutex::new(writer))
        }  
    }
    /// Just better call to get needed value
    /// 
    /// Pass clone of reader, no needed to be Arc
    /// 
    /// Also destroying ```ReadGuard``` in final so we can refresh
    /// 
    /// # Examples
    /// ```
    /// let cloned_reader = Evmap.reader().clone();
    /// thread::spawn(move || {
    ///     EvMapHandlerAsync::get_item(cloned_reader, "test");
    /// });
    /// ```
    /// 
    pub fn get_item<'b>(clone: &'a ReadHandle<K, V>, key : &'b K) -> Option<evmap::ReadGuard<'a, V>>{
        clone.get_one(key)
    }
    /// Get all items
    /// 
    /// Will clone items tho, be careful
    /// 
    /// Will block thread tho
    pub fn get_all_items<'b>(clone: &'a ReadHandle<K, V>) -> Option<Vec<(K,V)>>{
        //let mut items : Vec<(K,V)> = clone.map_into(|&k, vs|(k,vs));
        let mut items : Vec<(K,V)> = vec![];
        if items.len() == 0{
            None
        }else{
            Some(items)
        }

    }

    pub fn keys(clone : &ReadHandle<K,V>) -> Vec<K>{
        let mut items : Vec<K> = vec![];
        if let Some(handler) = &clone.read(){
            for (key, value) in handler {
                match value.get_one(){
                    Some(_x) => {
                        let k = key.clone();
                        items.push(k);
                    }
                    _ => {}
                }
            }
        }
        items
    }
    /// You should pass clone `Arc<Mutex<Writer>>`
    /// 
    /// Will call refresh in the end
    pub fn insert_item(clone : &Arc<Mutex<WriteHandle<K,V>>>, key : K, value: V){
        let mut writer = clone.lock().unwrap();
        writer.insert(key, value);
        writer.refresh();
    }
    /// You should pass clone `Arc<Mutex<Writer>>`
    /// Its really better to use this one
    /// Insert vector with `refresh`
    pub fn insert_multiple_items(clone : Arc<Mutex<WriteHandle<K,V>>>, items: Vec<(K, V)>){
        let mut writer = clone.lock().unwrap();
        for (key,val) in items{
            writer.insert(key, val);
        }
        writer.refresh();
    }
}