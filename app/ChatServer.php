<?php
/**
 * Chat Server.
 * websocket chat (php ver)
 * @author Adamovich S
 * 
 */


class ChatServer{
    private $serverStartTime;
    private $timeForServerShutdown;
    private $workingTime;
    private $serverMessagesExmpl;
    private $exitFlag;
    private $user;
    private $adminCommandPass = 's';

    function __construct( ){
        $this->serverStartTime = round(microtime(true),2);
        $this->start();
    }

    /**
     * main server cycle
     * this is endless cycle wich never end
     * @param servers ip or host name, servers port
     * @return nop
     */
    function start( $soketHostName = '127.0.0.1', $socketPortNumber = 25005 ){
        $this->user = new ParticipantsManager();
        $socket = new SocketCore();    
        while (true) {
            $read = $this->user->setReadArray($socket->getSocket());
            $null = null;
            if (!socket_select($read, $null, $null, null)) {/*wait for sokets read*/
                break;
            }
            $read = $this->user->onNewParticipant($socket, $read);
            $this->onRecieveBroadcastMessages($read);
            if ($this->user->isSetedParticipants()) {
                $this->sendBroadcastXMLMessages();
            }
            if ($this->exitFlag) {
                break;
            }
        }
        $socket->close();
    }
    
    /**
     * get messages from all participants
     * @param previous messeges, participants entity, resources ID for read array
     * @return nop
     */
    function onRecieveBroadcastMessages($read){
        foreach ($read as $connect) {
            $data = $this->getData($connect, "connectionHasBeenClosed");
            if ($data == 'connectionHasBeenClosed') {
                $this->makeMessagges( $connect, $this->user->getParticipantName($connect).': has been disconnected', "technicalServerMessage" );
                $this->user->unsetParticipant($connect);     
            }
            else { 
                $this->makeMessagges($connect, decode($data)['payload']);
            }
            $this->onServerAdminCommand($connect, $data);         
        }
    }


    function unsetAllMessagges() {
        $this->serverMessagesExmpl = array();
    }
     
    /**
     * This function make message and collects them in the Array
     * @param participant ID (and it can be connect, accept ID, accept... it's participantInfo['acceptID'])
     * @param message that has txt format, it will show in the chat
     * @param serverAttr (private, technicalServerMessage, chat)
     * @return nop
    */
    function makeMessagges($owner, $message, $serverAttr = null) {
        $tempArr['mess'] = $message;
        $tempArr['messOwner'] = $owner;
        $tempArr['author'] = null;
        $tempArr['client'] = null;
        $tempArr['messOwnerName'] = $this->user->getParticipantName($owner);
        $tempArr['serverAttr'] = $serverAttr;
        $this->serverMessagesExmpl[] = $tempArr;
    }

    /**
     * This function helps user manage some chat services
     * if someone texted message with password, after password was texted white space and then:
     * 1. "shut" this command set class var "exitFlag" to "1" value wich means that app will close
     * 2. "list" this command add to serverMessage array list of the current connected participants
     * 3. "setName" this command set nicname for the current participant
     * forexample in massage field you can type "s list" 
     * wich means "password_whiteSpase_command" and you will see the list of participants
     * @param participant ID, message data
     * @return nop
     */
    function onServerAdminCommand($connect, $data){
        $adminCommand = explode(' ', decode($data)['payload']);
        if($adminCommand[0] == $this->adminCommandPass){
            switch ($adminCommand[1]) {
                case 'list':
                $countParticipant = 0;
                foreach ($this->user->getParticipants() as $participantInfo) {
                    $this->makeMessagges($connect, ++$countParticipant.'. '.$participantInfo['name'].' '.$participantInfo['Host'], "private");
                }
                break;
                case 's':
                $this->exitFlag = 1;
                break;
                case 'setName':
                if ($this->user->setParticipantName($connect, $adminCommand[2])){
                    $this->makeMessagges($connect, $this->user->getDefaultParticipantName($connect).' seted his name as '.$adminCommand[2], "technicalServerMessage");
                }
                break;
            }
        }
        $this->checkAdminCommandInMessagessArray();
    }

    function sendMessage($resourceID, $data){
        $sent = socket_write($resourceID, encode($data));
        if ($sent === false) {
            $this->user->unsetParticipant($resourceID);
        }
    }

    function getData($connect, $altData){
        if( false !== $data = socket_read($connect, 2048) );    
        if (!$data) { /*соединение было закрыто*/
            $data = $altData;
        }
        return $data;
    }

    /**
     * This function deletes all users messages with admin password from broadcast
     * @param nop
     * @return nop
    */
    function checkAdminCommandInMessagessArray(){
        for ($i = 0; count($this->serverMessagesExmpl) > $i; $i++ ) {
            $adminCommand = explode(' ', $this->serverMessagesExmpl[$i]['mess']);
            if($adminCommand[0] == $this->adminCommandPass){
                unset($this->serverMessagesExmpl[$i]);
            }
        }
    }

    function sendBroadcastXMLMessages(){
        foreach ($this->user->getParticipants() as $participantInfo) {
            foreach ($this->serverMessagesExmpl as $mesg) {
                if (isset($participantInfo['acceptID'])){
                    if($participantInfo['acceptID'] == $mesg['messOwner']){
                        $this->sendMessage($participantInfo['acceptID'], "<?xml version='1.0' ?><root><message id='17' type='user-sock-info'><user>you: </user><text>".$mesg['mess']."</text><time>".$this->getServerTime()."</time></message></root>");
                    }else{
                        $this->sendMessage($participantInfo['acceptID'], "<?xml version='1.0' ?><root><message id='17' type='someone-sock-info'><user>".$mesg['messOwnerName'].": </user><text>".$mesg['mess']."</text><time>".$this->getServerTime()."</time></message></root>");
                    }
                }else{
                    $this->user->unsetParticipant($participantInfo['acceptID']);
                }
            }
        }
        $this->unsetAllMessagges();
    }

    function getServerWorkingTime(){
      return (round(microtime(true),2) - $this->serverStartTime);
    }

    function setTimeForServerShutdown($timeForServerShutdown){
        $this->timeForServerShutdown = $timeForServerShutdown;
    }

    function getServerTime(){
        return date("h:m");
    }

    function isTimeForServerShutdown(){
        return ($this->getServerWorkingTime() > $this->timeForServerShutdown);
    }

}