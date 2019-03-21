# Artistic Portfolio

A basic symfony project using Flickr's API (useful for saving space on free webhosts lol)
Don't forget to do a ```composer update``` on the terminal to get all the dependencies

All you need to do to make it work is to create a file in ```public/``` named ```apiKey.php```
Just type the following with your API key [(Get it here)](https://www.flickr.com/services/apps/create/apply/)

``` php
<?php define('apiKey', 'your api key here'); ?>
```