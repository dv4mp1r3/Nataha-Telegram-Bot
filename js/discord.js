const Discord = require('discord.js');
const { Intents } = require("discord.js");
const libsodium = require("libsodium-wrappers");
(async () => {
    await libsodium.ready;
})();

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
let alreadyJoined = false;
api.use(fileUpload());
api.listen(3000);

client.on('ready', () => {
    console.log(`Logged in as ${client.user.tag}!`);
});

const opusPlayer = createAudioPlayer();

api.post('/audio/:file', function (req, res) {
    if (!alreadyJoined) {
        console.log('new /audio/ request but alreadyJoined=false');
        res.send('');
        return;
    }
    console.debug('new /audio/ request', req.params.file);
    let soundFilePath = '/tmp/' + req.params.file;
    fs.writeFile(soundFilePath, req.files.voice.data, function (err,data) {
        if (err) {
            return console.log(`fs.writeFile error ${err}`);
        }
        try{
            opusPlayer.checkPlayable()
            console.debug('before createAudioResource');
            const resource = createAudioResource(fs.createReadStream(soundFilePath));
            console.debug('after createAudioResource');
            console.debug('before opusPlayer.play');
            opusPlayer.play(resource);
            console.debug('after opusPlayer.play');
        }catch (e) {console.log(e);}
        //fs.unlinkSync(soundFilePath);
    });
    res.send('');
});

client.on('messageCreate', async message => {
    if (!message.guild) return;
    if (message.content === '/join') {
        if (message.member.voice.channel) {
            const voiceConnection = joinVoiceChannel({
                channelId: message.member.voice.channel.id,
                guildId: message.guild.id,
                selfDeaf: false,
                adapterCreator: message.guild.voiceAdapterCreator
            })
            const networkStateChangeHandler = (oldNetworkState, newNetworkState) => {
                const newUdp = Reflect.get(newNetworkState, 'udp');
                clearInterval(newUdp?.keepAliveInterval);
            }
            voiceConnection.on('stateChange', (oldState, newState) => {
                const oldNetworking = Reflect.get(oldState, 'networking');
                const newNetworking = Reflect.get(newState, 'networking');
                console.debug('voiceConnection stateChange called');
                oldNetworking?.off('stateChange', networkStateChangeHandler);
                newNetworking?.on('stateChange', networkStateChangeHandler);
            });
            voiceConnection.subscribe(opusPlayer);
            alreadyJoined = true;
        }
    }
});
client.login(process.env.DISCORD_TOKEN);