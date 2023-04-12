<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

require_once 'app/cript.php';
require_once 'app/cript2.php';
require_once 'app/SocketCore.php';
require_once 'app/ParticipantsManager.php';
require_once 'app/ChatServer.php';

function checkServerLocal() {
    if (!ini_get('date.timezone')) {
        date_default_timezone_set('UTC');
    }
}

checkServerLocal();

new ChatServer();

echo "server has been closed successfully";
