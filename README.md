# Shazam game bot
Shazam game is a game that was played in a Manoto TV show called **Shabe jome**. A group of people get together and guess the song and it's singer. Who guess it correctly and also faster, wins the round. Scoreboard is available to check the players' scores.

## Demo
Current live version here: [telegram.me/shazamgame_bot](http://telegram.me/shazamgame_bot)

## Getting started
These instructions will get you a copy of the project up and running on your local machine for compiling. For development and testing purposes you must have a **https** domain, create a bot at [BotFather](https://telegram.me/BotFather) and set webhook to *index.php* file on your host.

### Prerequisite
Install [WAMP server](http://www.wampserver.com/en/) to prepare localhost on you machine. Follow the instructions and make sure [localhost](localhost) is up and running.

### Installing
1. Open **phpMyAdmin** and log into root account.
2. Create **shazamgame** database for your bot.
3. Go to **Import** and upload *shazamgame_db.sql* to initialize tables.
4. Edit *config.php* like below
```
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define('SERVER_NAME', 'localhost');
define('SERVER_USER', 'root');
define('SERVER_PASS', '');
define('SERVER_DB', 'shazamgame');
define('SERVER_TABLE', 'game');
define('SERVER_TABLE_SCORE', 'scoreboard');
define('GAME_PATH', 'INDEX_PATH_ON_LOCALHOST');
define('SONGS_FOLDER', 'SONGS_PATH_ON_LOCALHOST');
```
5. Open [localhost](localhost) and see if your code has any error or not. Be aware that you can only compile your code in your localhost and for development and testing purposes you must
1. Have a **https** domain
2. Create a bot at [BotFather](https://telegram.me/BotFather)
3. Edit config
4. Set webhook to *index.php* file on your host.

## Built With
* PHP
* MySQL
* [Telegram bot api](https://core.telegram.org/bots/api)

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
