About
=====
Yak is a command-line database migration tool written in PHP and designed to interact with MySQL 5.0 and above. It requires PHP 5.3.x with PDO_MySQL installed. Yak is written with Symfony2 Console.

Installation
============
Yak is distributed as an excutable Phar file with all of it's dependencies bundled. Once you install it initally, the
"update-yak" command can keep you up to date. To get your initial install, run the following command in the folder
where you'd like Yak to be installed (I recommend /usr/local/bin):

    curl https://raw.github.com/jimbojsb/yak/master/bin/installer.php | php

Usage
=====
Yak's usage is well documented in the command line help interface.

* To list available commands, just run "yak"
* For help on a specific command, run "yak help \[command\]"