"use strict";

(function () {

	var socket;
    

    var init = function () {
/*
https://searchengines.guru/showthread.php?t=746025
<?php
$back[host] = 'ws://127.0.0.1';
$back[port] = '25005';
echo json_encode( $back );
?>


JavaScript
http://api.jquery.com/jQuery.getJSON/

$.getJSON('/script.php', function(items) {
     host = items.host;
     port = items.port);
});

*/
		//socket = new WebSocket(document.getElementById("sock-addr").value);
        socket = new WebSocket("ws://127.0.0.1:25005");
		socket.onopen = connectionOpen; 
		socket.onmessage = messageReceived; 
		socket.onerror = function () {
                alert("arror...");
            };

        socket.onclose = connectionClose;
        /*document.getElementById("sock-send-butt").onclick = function () {
            socket.send(document.getElementById("sock-msg").value);
        };

        document.getElementById("sock-disc-butt").onclick = function () {
            connectionClose();
        };

        document.getElementById("sock-recon-butt").onclick = function () {

            // socket = new WebSocket(document.getElementById("sock-addr").value);
            connectionClose();
            socket = new WebSocket("ws://127.0.0.1:25005");
            socket.onopen = connectionOpen;
            socket.onmessage = messageReceived;
        };
        function myTrim(x) {
            return x.replace(/^\r\n+|\r\n+$/gm,'');
        }*/
        document.getElementById('sock-msg').onkeydown = function(event) {
            if (event.keyCode == 13) {
                socket.send(document.getElementById("sock-msg").value);
            }
        };

    };

	function connectionOpen() {
	   socket.send("new web user has connected");
	}



    function messageReceived(e) {
        var parseXml;
        if (typeof window.DOMParser != "undefined") {
            parseXml = function(xmlStr) {
                return ( new window.DOMParser() ).parseFromString(xmlStr, "text/xml");
            };
        } else if (typeof window.ActiveXObject != "undefined" && new window.ActiveXObject("Microsoft.XMLDOM")) {
            parseXml = function(xmlStr) {
                var xmlDoc = new window.ActiveXObject("Microsoft.XMLDOM");
                xmlDoc.async = "false";
                xmlDoc.loadXML(xmlStr);
                return xmlDoc;
            };
        }

        //var xml = parseXml("<?xml version='1.0' ?><root><message id='17' type='self'><user>Ryan Smith</user><text>This is an example of JSON</text><time>04:41</time></message></root>");
        var xml = parseXml(e.data);

        var id = xml.documentElement.childNodes[0].getAttribute('id')
        var type = xml.documentElement.childNodes[0].getAttribute('type')
        var user = xml.documentElement.childNodes[0].childNodes[0].textContent;
        var time = xml.documentElement.childNodes[0].childNodes[2].textContent;
        var mess = xml.documentElement.childNodes[0].childNodes[1].textContent;
            var console = document.getElementById('sock-info');
            var p = document.createElement('div');
            var innerDivUserTime = document.createElement('div');
            innerDivUserTime.innerHTML = user + " " + time;
            p.className = type;
            innerDivUserTime.className = 'messInfo';

            p.innerHTML = mess;
            p.appendChild(innerDivUserTime);
            console.appendChild(p);
            while (console.childNodes.length > 100) {
                console.removeChild(console.firstChild);
            }
            var scroll = document.getElementById('scroll-container');
            scroll.scrollTop = scroll.scrollHeight;
            document.getElementById('sock-msg').value = '';
    }

    function connectionClose() {
        socket.close();
        document.getElementById("sock-info").innerHTML += "<div class='info'> нет соединения </div>";
            var scroll = document.getElementById('scroll-container');
            scroll.scrollTop = scroll.scrollHeight;
    }

// ---- onload event ----
    return {
        load : function () {
            window.addEventListener('load', function () {
                init();
            }, false);
        }
    }
})().load();