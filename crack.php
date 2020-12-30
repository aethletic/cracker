<?php 

use Cracker\Crack;

require 'vendor/autoload.php';

$cracked = (new Crack('https://steamcommunity.com/public/captcha.php?gid=XXXXXXXXXXXXXXXXXXXXXXXX'))
    ->storage(__DIR__ . '/storage')
    ->data(__DIR__)
    ->model('steam')
    ->iterations(3)
    ->resolve(true);

print_r($cracked);