const Discord = require('discord.js');
const process = require('process');
const fs = require('fs');
const soundFilePath = './t.ogg';
const pidFile = '/tmp/security.pid';
const client = new Discord.Client();
const myArgs = process.argv.slice(2);

client.on('ready', () => {
    console.log(`Logged in as ${client.user.tag}!`);
});

client.on('message', async message => {
    if (!message.guild) return;
    if (message.content === '/join') {
        if (message.member.voice.channel) {
            const connection = await message.member.voice.channel.join();
            process.on('SIGUSR2', () => {
                const d = connection.play(fs.createReadStream(soundFilePath), {
                    type: 'ogg/opus',
                });
            });
        }
    }
});

fs.writeFile(pidFile, process.pid, function (err) {
    if (err) return console.log(err);
});

client.login(myArgs[1]);