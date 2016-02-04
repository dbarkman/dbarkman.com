#!/usr/local/bin/php -q
<?php

// defaults...
                                // location URL where to find the IPplan XML-RPC server.php script
define('URL', '/~richarde/ipplanapi/server.php');
define('SERVER', 'localhost');  // the webserver name
define('RPCPORT', 80);             // the XML-RPC server port (normally 80)
define('USER', 'user');     // the IPplan userid to use
define('PASSWD', 'passwd');      // the IPplan password to use

define('MAXLINE', 1024);        // how much to read from a socket at a time
define('LISTENQ', 10);          // listening queue
define('PORT', 43);             // the default port for whois server to run on
define('FD_SETSIZE', 5);        // file descriptor set size (max number of concurrent clients)...

define('DEBUG', FALSE);          // do some debugging?

//------------------- initialize things here ------------------//

set_time_limit(0);
// turn off those pesky quotes
set_magic_quotes_runtime(0);

if(!extension_loaded("sockets")) {
   die("php does not have sockets compiled in - recompile with\n  --with-sockets\n");
}

require_once("../xmlrpc/xmlrpc.inc");

//------------------- daemon section starts here ------------------//

// for kill the biatch...

function killDaemon() {
    global $listenfd, $client;

    socket_close($listenfd);
    $msg = "Daemon going down!\n";
    for ($i = 0; $i < FD_SETSIZE; $i++) {
        if ($client[$i] != null) {
            socket_write($client[$i], $msg, strlen($msg));
            socket_close($client[$i]);
        }
    }
    
    if (DEBUG) {
       print "Shutting down the daemon\n";
    }
    exit;
}


// whenever a client disconnects...

function closeClient($i) {
    global $client, $remote_host, $remote_port;

    if (DEBUG) {
        print "closing client[$i] ({$remote_host[$i]}:{$remote_port[$i]})\n";
    }

    socket_close($client[$i]);
    $client[$i] = null;
    unset($remote_host[$i]);
    unset($remote_port[$i]);
}


// set up the file descriptors and sockets...

// $listenfd only listens for a connection, it doesn't handle anything
// but initial connections, after which the $client array takes over...

$listenfd = socket_create(AF_INET, SOCK_STREAM, 0);
if ($listenfd) {
    if (DEBUG) {
       print "Listening on port " . PORT . "\n";
    }
}
else {
    die("AIEE -- socket died!\n");
}

socket_setopt($listenfd, SOL_SOCKET, SO_REUSEADDR, 1);
if (!socket_bind($listenfd, "0.0.0.0", PORT)) {
    socket_close($listenfd);
    die("AIEE -- Couldn't bind!\n");
}
socket_listen($listenfd, LISTENQ);


// set up our clients. After listenfd receives a connection,
// the connection is handed off to a $client[]. $maxi is the
// set to the highest client being used, which is somewhat
// unnecessary, but it saves us from checking each and every client
// if only, say, the first two are being used.

$maxi = -1;
for ($i = 0; $i < FD_SETSIZE; $i++) {
    $client[$i] = null;
}


// the main loop.

while (1) {
    $rfds[0] = $listenfd;

    for ($i = 0; $i < FD_SETSIZE; $i++) {
        if ($client[$i] != null)
            $rfds[$i + 1] = $client[$i];
    }


    // block indefinitely until we receive a connection...

    $nready = socket_select($rfds, $null, $null, null);


    // if we have a new connection, stick it in the $client array,

    if (in_array($listenfd, $rfds)) {
        if (DEBUG) {
           print "listenfd heard something, setting up new client\n";
        }

        for ($i = 0; $i < FD_SETSIZE; $i++) {
            if ($client[$i] == null) {
                $client[$i] = socket_accept($listenfd);
                socket_setopt($client[$i], SOL_SOCKET, SO_REUSEADDR, 1);
                socket_getpeername($client[$i], $remote_host[$i], $remote_port[$i]);
                if (DEBUG) {
                   print "Accepted {$remote_host[$i]}:{$remote_port[$i]} as client[$i]\n";
                }
                break;
            }

            if ($i == FD_SETSIZE - 1) {
                trigger_error("too many clients", E_USER_ERROR);
                exit;
            }
        }
        if ($i > $maxi)
            $maxi = $i;

        if (--$nready <= 0)
            continue;
    }


    // check the clients for incoming data.

    for ($i = 0; $i <= $maxi; $i++) {
        if ($client[$i] == null)
            continue;

        if (in_array($client[$i], $rfds)) {
            $n = trim(socket_read($client[$i], MAXLINE));

            if (!$n) {
                closeClient($i);
            }
            else {
                // if a client has sent some data, do one of these:
                socket_write($client[$i], "IPplan whoisd v1.0\r\n------------------\r\n\n");

                if ($n == "?" or $n == "help") {
                    $res=help();
                    socket_write($client[$i], "$res\r\n");
                    closeClient($i);
                }
                else if (substr($n, 0, 3) == "AS ") {
                    $res=searchCustomer(substr($n, 3, 80));
                    socket_write($client[$i], "$res\r\n");
                    closeClient($i);
                }
                else if (testIP($n) == FALSE) {
                    // print something on the server, then echo the incoming
                    // data to all of the clients in the $client array.

                    if (DEBUG) {
                        print "From {$remote_host[$i]}:{$remote_port[$i]}, client[$i]: $n\n";
                    }
                    //socket_write($client[$i], "From client[$i]: $n\r\n");
                    $res=searchIP($n);

                    socket_write($client[$i], "$res\r\n");
                    closeClient($i);
                }
                else {
                    $res=help();
                    $res.="Invalid query\n";
                    socket_write($client[$i], "$res\r\n");
                    closeClient($i);
                }

            }

            if  (--$nready <= 0)
                break;
        }
    }
}

function help() {

    $help="Enter a query in the following formats:\n\n";
    $help.="Query\t\tResult\n";
    $help.="-----\t\t------\n";
    $help.="10.0.0.0\tto search for a network address\n";
    $help.="AS <as>\t\tto search for Autonomous system/Customer <as>\n";

    return $help;

}

//------------------- daemon section ends here ------------------//

//------------------- whois section starts here ------------------//

// test for ip addresses between 1.0.0.0 and 255.255.255.255
function testIP($a, $allowzero=FALSE) {
    $t = explode(".", $a);

    if (sizeof($t) != 4)
       return 1;

    for ($i = 0; $i < 4; $i++) {
        // first octet may not be 0
        if ($t[0] == 0 && $allowzero == FALSE)
           return 1;
        if ($t[$i] < 0 or $t[$i] > 255)
           return 1;
        if (!is_numeric($t[$i]))
           return 1;
    };
    return 0;
}

// returns the number of bits in the mask cisco style
function inet_bits($n) {

    if ($n == 1)
       return 32;
    else
       return 32-strlen(decbin($n-1));
}

// searches for customers to which a network belongs
function searchIP($ip) {

    $f=new xmlrpcmsg('ipplan.SearchIP', array(new xmlrpcval($ip, "string")));
    $c=new xmlrpc_client(URL, SERVER, RPCPORT);
    $c->setCredentials(USER, PASSWD);
    $c->setDebug(DEBUG ? 1 : 0);
    $r=$c->send($f);

    if ($r->faultCode() <= 0) {
        $v=xmlrpc_decode($r->value());

        $res="";
        foreach ($v as $value) {
            $res.=sprintf("%s/%s\t%s\n\tCustomer/AS: %s\n", $value["baseaddr"], inet_bits($value["subnetsize"]),
                                            $value["descrip"], $value["custdescrip"]);
        }
        return $res;
    } else {
        if (DEBUG) {
            print "Fault! Code: ".$r->faultCode()." Reason '" .$r->faultString()."'\n";
        }
        return "Could not query server";
    }
}


// searches for customers to which a network belongs
function searchCustomer($descrip) {

    // first fetch all the customers
    $f=new xmlrpcmsg('ipplan.FetchCustomer', array());
    $c=new xmlrpc_client(URL, SERVER, RPCPORT);
    $c->setCredentials(USER, PASSWD);
    $c->setDebug(DEBUG ? 1 : 0);
    $r=$c->send($f);

    if ($r->faultCode() <= 0) {
        $v=xmlrpc_decode($r->value());

        // now loop through all customers and find a match from whois query
        // entered by user
        $res="";
        $cust=0;
        foreach ($v as $value) {
            if ($value["custdescrip"] == $descrip) {
                $cust=$value["customer"];
                break;
            }
        }

        // got a match, get the customers information via another RPC query
        if ($cust > 0) {
            $f=new xmlrpcmsg('ipplan.FetchCustomerInfo', array(new xmlrpcval($cust, "int")));
            //$c=new xmlrpc_client(URL, SERVER, RPCPORT);
            //$c->setCredentials(USER, PASSWD);
            //$c->setDebug(DEBUG ? 1 : 0);
            $r=$c->send($f);
            if ($r->faultCode() > 0) { 
                // send failed
                if (DEBUG) {
                    print "Fault! Code: ".$r->faultCode()." Reason '" .$r->faultString()."'\n";
                }
                return "Could not query server";
            }
            $v=xmlrpc_decode($r->value());

            //	org, street, city, state, zipcode, cntry, nichandl
            // lname, fname, mname, torg, tstreet, tcity, tstate
            // tzipcode, tcntry

            if ($r->faultCode() <= 0) {
                $res.='
 Customer/AS: '.$descrip.'

  Contact details
  ---------------
   Organization: '.$v["org"].'
   Street: '.$v["street"].'
   City: '.$v["city"].'
   State: '.$v["state"].'
   Zipcode: '.$v["zipcode"].'
   Country: '.$v["cntry"].'

  Technical contact
  -----------------
   Nickname: '.$v["nichandl"].'
   First name: '.$v["fname"].'
   Last name: '.$v["lname"].'
   Middle name: '.$v["mname"].'

   Organization: '.$v["torg"].'
   Street: '.$v["tstreet"].'
   City: '.$v["tcity"].'
   State: '.$v["tstate"].'
   Zipcode: '.$v["tzipcode"].'
   Country: '.$v["tcntry"].'

  Name servers
  ------------
';
            }

            // step through DNS servers
            foreach ($v["dns"] as $value) {
                $res.=sprintf("    %s (%s)\n", $value["hname"], $value["ipaddr"]);
            }
        }
        else {
            return "No such AS/customer";
        }

        return $res;
    } else {
        if (DEBUG) {
            print "Fault! Code: ".$r->faultCode()." Reason '" .$r->faultString()."'\n";
        }
        return "Could not query server";
    }
}

//------------------- whois section ends here ------------------//
?>
