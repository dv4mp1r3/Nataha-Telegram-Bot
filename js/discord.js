const Discord = require('discord.js');
const fs = require('fs');
const client = new Discord.Client();
const express = require('express');
const fileUpload = require('express-fileupload');
let api = express();
api.use(fileUpload());
api.listen(3000);

client.on('ready', () => {
    console.log(`Logged in as ${client.user.tag}!`);
});

client.on('message', async message => {
    if (!message.guild) return;
    if (message.content === '/join') {
        if (message.member.voice.channel) {
            const connection = await message.member.voice.channel.join();
            api.post('/audio/:file', function (req, res) {
                let soundFilePath = '/tmp/' + req.params.file;
                fs.writeFile(soundFilePath, req.files.voice.data, function (err,data) {
                    if (err) {
                        return console.log(`fs.writeFile error ${err}`);
                    }
                    try{
                        const d = connection.play(fs.createReadStream(soundFilePath), {
                            type: 'ogg/opus',
                        });
                    }catch (e) {console.log(e);}
                    //fs.unlinkSync(soundFilePath);
                });
                res.send('');
            });
        }
    }
});
client.login(process.env.DISCORD_TOKEN);