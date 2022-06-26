const Discord = require('discord.js');
const { Intents } = require("discord.js");
const { joinVoiceChannel, createAudioPlayer, createAudioResource } = require('@discordjs/voice');
const fs = require('fs');
const client = new Discord.Client({
    intents: [
        Intents.FLAGS.GUILDS,
        Intents.FLAGS.GUILD_MESSAGES,
        Intents.FLAGS.GUILD_MESSAGE_REACTIONS,
        Intents.FLAGS.GUILD_VOICE_STATES
    ],
    partials: ["MESSAGE" , "CHANNEL" , "REACTION"]
});
const express = require('express');
const fileUpload = require('express-fileupload');

let api = express();
api.use(fileUpload());
api.listen(3000);

client.on('ready', () => {
    console.log(`Logged in as ${client.user.tag}!`);
});

const opusPlayer = createAudioPlayer();

client.on('messageCreate', async message => {
    if (!message.guild) return;
    if (message.content === '/join') {
        if (message.member.voice.channel) {
            joinVoiceChannel({
                channelId: message.member.voice.channel.id,
                guildId: message.guild.id,
                selfDeaf: false,
                adapterCreator: message.guild.voiceAdapterCreator
            }).subscribe(opusPlayer);
            api.post('/audio/:file', function (req, res) {
                let soundFilePath = '/tmp/' + req.params.file;
                fs.writeFile(soundFilePath, req.files.voice.data, function (err,data) {
                    if (err) {
                        return console.log(`fs.writeFile error ${err}`);
                    }
                    try{
                        const resource = createAudioResource(fs.createReadStream(soundFilePath));
                        opusPlayer.play(resource);
                    }catch (e) {console.log(e);}
                    fs.unlinkSync(soundFilePath);
                });
                res.send('');
            });
        }
    }
});
client.login(process.env.DISCORD_TOKEN);