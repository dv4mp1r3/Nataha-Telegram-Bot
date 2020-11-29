const Discord = require('discord.js');
const fs = require('fs');
const client = new Discord.Client();
const express = require('express');
let api = express();
api.listen(3000);

client.on('ready', () => {
    console.log(`Logged in as ${client.user.tag}!`);
});

client.on('message', async message => {
    if (!message.guild) return;
    if (message.content === '/join') {
        if (message.member.voice.channel) {
            const connection = await message.member.voice.channel.join();
            api.get('/audio/:file', function(req, res){
                let soundFilePath = process.env.PWD+'/audio/'+req.params.file;
                const d = connection.play(fs.createReadStream(soundFilePath), {
                    type: 'ogg/opus',
                });
            });
        }
    }
});
client.login(process.env.DISCORD_TOKEN);