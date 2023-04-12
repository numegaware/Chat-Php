 
<?php
/**
 * ParticipantManager utility.
 * websocket chat (php ver)
 * @author Adamovich S
 * 
 */


class ParticipantsManager{
    private $connectedParticipants = array();
    private $readedParticipants = array();

    function __construct(){
    }


    public function setReadArray($currentConnection){
        foreach ($this->connectedParticipants as $participantInfo) {
            $readArray[] = $participantInfo['acceptID'];
        }
        $readArray[] = $currentConnection;
        $this->readedParticipants = $readArray;
        return $readArray;
    }

    public function selectConnect($read){
        $null = null;
        if (!socket_select($read, $null, $null, null)) {/*wait for sokets read*/
            break;
        }
        return $false;
    }

    public function getReadArray(){
        return $this->readedParticipants;
    }

    function onNewParticipant($socket, $read){
        if (in_array($socket->getSocket(), $read)) {
            $resourceID = $socket->getAccept();
            $httppHeaders = $this->getData($resourceID, "");
            if ( $particippantInfo = $this->handshake($httppHeaders, $resourceID) ){
                socket_write($resourceID, $particippantInfo['response']);
            }
            unset($read[ array_search($socket->getSocket(), $read) ]);
        }
        return $read;
    }

    function getData($connect, $altData){
        if( false !== $data = socket_read($connect, 2048) );    
        if (!$data) { /*соединение было закрыто*/
            $data = $altData;
        }
        return $data;
    }

    public function unsetReadArrayItem($soketResourceID){
        unset($this->readedParticipants[ array_search($soketResourceID, $this->readedParticipants) ]);
        return $this->readedParticipants;
    }

    public function isSetParticipant($participantInfo){
        for ($i = 0; $i < count($this->connectedParticipants); $i++) {
            if ($this->connectedParticipants[$i]['acceptID'] == $participantInfo['acceptID']){
                return true;
            }
        }
        return false;
    }
/**
    -- template headerHTTP:

    GET / HTTP/1.1
    Upgrade: websocket
    Connection: Upgrade
    Host: 127.0.0.1:25005
    Origin: http://my.local
    Sec-WebSocket-Protocol: soap, wamp
    Pragma: no-cache
    Cache-Control: no-cache
    Sec-WebSocket-Key: hZqqDwjXpBhoEnANwAeHfw==
    Sec-WebSocket-Version: 13
    Sec-WebSocket-Extensions: x-webkit-deflate-frame
    User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.1 Safari/603.1.30
*/
    function handshake($connect, $accept) {
        $info = array();
        $header = explode(PHP_EOL, $connect);
        for ($i = 0; $i < count($header); $i++){
            if (preg_match('/\A(\S+): (.*)\z/', $header[$i], $matches)) {
                $info[$matches[1]] = $matches[2];
            }
        }

        if (!empty($info['Sec-WebSocket-Key'])) {
            $hash = base64_encode(pack('H*', sha1(trim($info['Sec-WebSocket-Key']) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $info['acceptID'] = $accept;
            
            $info['defaultName'] = $this->setDefaultParticipantName($accept);
            $info['name'] = $this->setDefaultParticipantName($accept);
            $info['Sec-WebSocket-Accept'] = $hash;
            $info['response'] = 
            "HTTP/1.1 101 Switching Protocols\r\n".
            "Upgrade: websocket\r\n".
            "Connection: Upgrade\r\n".
            "Sec-WebSocket-Accept: ".$hash."\r\n\r\n";
            $this->setParticipant($info);
            return $info;
        }
        return false;
    }

    public function getParticipants(){
        return $this->connectedParticipants;
    }

    public function getParticipant($accept){
        for ($i = 0; $i < count($this->connectedParticipants); $i++) {
            if ($this->connectedParticipants[$i]['acceptID'] == $accept){
                return $this->connectedParticipants[$i];
            }
        }
        return false;
    }

    public function getParticipantsList(){// not using yet
        foreach ($this->connectedParticipants as $participantInfo) {
            $participantsList[] = $participantInfo['acceptID'];
        }
        return $participantsList;
    }

    public function setParticipant($participantInfo){
        if(!$this->isSetParticipant($participantInfo)){
            $this->connectedParticipants[] = $participantInfo;
        }
        return $this->connectedParticipants;
    }


    /**
     * function for help to check participants are
     * @param nop
     * @return true if participat is 'nd false if isn't
     */
    public function isSetedParticipants(){
       if (count($this->connectedParticipants) <= 0){
            return false;
       }
       return true;
    }

    public function unsetParticipant($accept){
        for ($i = 0; $i < count($this->connectedParticipants); $i++) {
            if(isset($this->connectedParticipants[$i]['acceptID'])){
            if ($this->connectedParticipants[$i]['acceptID'] == $accept){
                unset($this->connectedParticipants[$i]);
                sort($this->connectedParticipants);
                break;
            }
            }
        }
        return $this->connectedParticipants;
    }


    private function setDefaultParticipantName($accept){
        $nam = explode("#", $accept."");
        return "User ".$nam[1];
    }

    public function setParticipantName($accept, $name){
        for ($i = 0; count($this->connectedParticipants) > $i; $i++) {
            if ($this->connectedParticipants[$i]['acceptID'] == $accept){
                $this->connectedParticipants[$i]['name'] = $name;
                return true;
            } 
        }
        return false;
    }

    public function getDefaultParticipantName($accept){
        foreach ($this->connectedParticipants as $paticipant) {
            if ($paticipant['acceptID'] == $accept){
                return $paticipant['defaultName'];
            } 
        }
    }
    
    public function getParticipantName($accept){
        foreach ($this->connectedParticipants as $paticipant) {
            if ($paticipant['acceptID'] == $accept){
                return $paticipant['name'];
            } 
        }
    }

}