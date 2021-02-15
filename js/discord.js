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
            api.get('/audio/:file', function (req, res) {
                let soundFilePath = process.env.PWD + '/audio/' + req.params.file;
                console.log(`Voice file path: ${soundFilePath}`);
                if (!fs.existsSync(soundFilePath)) {
                    console.log(`File ${soundFilePath} doesn't exist`);
                } else {
                    const d = connection.play(fs.createReadStream(soundFilePath), {
                        type: 'ogg/opus',
                    });
                }
                res.send('');
            });
        }
    }
});
client.login(process.env.DISCORD_TOKEN_DEBUG);