<?php

if (defined('CONFIG_DB_USERNAME') && CONFIG_DB_USERNAME != '') {
    R::setup('mysql:\/\/localhost/tv_old', CONFIG_DB_USERNAME, CONFIG_DB_PASSWORD);
} else {
    R::setup('mysql:\/\/localhost/tv_old');
}

