<?php
class SocketCore{
    private $socket;
    private $soketHostName;
    private $socketPortNumber;

    function __construct( $soketHostName = '127.0.0.1', $socketPortNumber = 25005){
        $this->soketHostName = $soketHostName;
        $this->socketPortNumber = $socketPortNumber;
        $this->create();
    }

    function create(){
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $bind = socket_bind($this->socket, $this->soketHostName, $this->socketPortNumber);//привязываем его к указанным ip и порту
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);//разрешаем использовать один порт для нескольких соединений
        $listen = socket_listen($this->socket, 5);//слушаем сокет
    }
    
    function getAccept(){
        return $accept = @socket_accept($this->socket); //Зависаем пока не получим ответа;
    }

    function getSocket(){
        return $this->socket;
    }    

    function close(){
        socket_close($this->socket);
    }


}