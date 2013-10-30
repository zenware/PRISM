<?php

namespace PRISM\Module\Telnet;
use PRISM\Module\SectionHandler;

/**
 * Handles telnet client connections.
 * 
 * @category   Submodule
 * @package    PRISM
 * @subpackage Module\Telnet
 * @author     unknown
 * @license    http://opensource.org/license/MIT MIT License
 */
class Handler extends SectionHandler // TelnetHandler
{
    private $telnetSock		= null;
    private $clients		= array();
    private $numClients		= 0;

    private $telnetVars		= array();

    public function __construct()
    {
        $this->iniFile = 'telnet.ini';
    }

    public function __destruct()
    {
        $this->close(true);
    }

    public function init()
    {
        global $PRISM;

        $this->telnetVars = array
        (
            'ip' => '',
            'port' => 0,
        );

        if ($this->loadIniFile($this->telnetVars, false)) {
            if ($PRISM->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
                console('Loaded '.$this->iniFile);
            }
        } else {
            # We ask the client to manually input the connection details here.
            require_once(ROOTPATH . '/modules/prism_interactive.php');
            Interactive::queryTelnet($this->telnetVars);

            # Then build a telnet.ini file based on these details provided.
            $extraInfo = <<<ININOTES
;
; Telnet listen details (for remote console access).
; 0.0.0.0 (default) will bind the socket to all available network interfaces.
; To limit the bind to one interface only, you can enter its IP address here.
; If you do not want to use the telnet feature, you can comment or remove the
; lines, or enter "" and 0 for the ip and port.
;

ININOTES;
            if ($this->createIniFile('Telnet Configuration (remote console)', array('telnet' => &$this->telnetVars), $extraInfo)) {
                console('Generated config/'.$this->iniFile);
            }
        }

        // Setup telnet socket to listen on
        if (!$this->setupListenSocket()) {
            return false;
        }

        return true;
    }

    private function setupListenSocket()
    {
        $this->close(false);

        if ($this->telnetVars['ip'] != '' && $this->telnetVars['port'] > 0) {
            $this->telnetSock = @stream_socket_server('tcp://'.$this->telnetVars['ip'].':'.$this->telnetVars['port'], $errNo, $errStr);

            if (!is_resource($this->telnetSock) || $this->telnetSock === FALSE || $errNo) {
                console('Error opening telnet socket : '.$errStr.' ('.$errNo.')');
                return false;
            } else {
                console('Listening for telnet input on '.$this->telnetVars['ip'].':'.$this->telnetVars['port']);
            }
        }

        return true;
    }

    private function close($all)
    {
        if (is_resource($this->telnetSock)) {
            fclose($this->telnetSock);
        }

        if (!$all) {
            return;
        }

        for ($k=0; $k<$this->numClients; $k++) {
            array_splice($this->clients, $k, 1);
            $k--;
            $this->numClients--;
        }
    }

    public function getSelectableSockets(array &$sockReads, array &$sockWrites)
    {
        // Add http sockets to sockReads
        if (is_resource($this->telnetSock)) {
            $sockReads[] = $this->telnetSock;
        }

        for ($k=0; $k<$this->numClients; $k++) {
            if (is_resource($this->clients[$k]->getSocket())) {
                $sockReads[] = $this->clients[$k]->getSocket();

                // If write buffer was full, we must check to see when we can write again
                if ($this->clients[$k]->getSendQLen() > 0) {
                    $sockWrites[] = $this->clients[$k]->getSocket();
                }
            }
        }
    }

    public function checkTraffic(array &$sockReads, array &$sockWrites)
    {
        $activity = 0;

        // telnetSock input (incoming telnet connection)
        if (in_array($this->telnetSock, $sockReads)) {
            $activity++;

            // Accept the new connection
            $peerInfo = '';
            $sock = @stream_socket_accept ($this->telnetSock, null, $peerInfo);

            if (is_resource($sock)) {
                //stream_set_blocking ($sock, 0);

                // Add new connection to clients array
                $exp = explode(':', $peerInfo);
                $this->clients[] = new PrismTelnet($sock, $exp[0], $exp[1]);
                $this->numClients++;
                console('Telnet Client '.$exp[0].':'.$exp[1].' connected.');
            }
            unset ($sock);
        }

        // telnet clients input
        for ($k=0; $k<$this->numClients; $k++) {
            // Recover from a full write buffer?
            if ($this->clients[$k]->getSendQLen() > 0 && in_array($this->clients[$k]->getSocket(), $sockWrites)) {
                $activity++;

                // Flush the sendQ (bit by bit, not all at once - that could block the whole app)
                if ($this->clients[$k]->getSendQLen() > 0) {
                    $this->clients[$k]->flushSendQ();
                }
            }

            // Did we receive something from a httpClient?
            if (!in_array($this->clients[$k]->getSocket(), $sockReads)) {
                continue;
            }

            $activity++;

            $data = $this->clients[$k]->read($data);

            // Did the client hang up?
            if ($data == '') {
                console('Closed telnet client (client initiated) '.$this->clients[$k]->getRemoteIP().':'.$this->clients[$k]->getRemotePort());
                array_splice ($this->clients, $k, 1);
                $k--;
                $this->numClients--;
                continue;
            }

            $this->clients[$k]->addInputToBuffer($data);
            $this->clients[$k]->processInput();

            if ($this->clients[$k]->getMustClose()) {
                $this->clients[$k]->__destruct();
                console('Closed telnet client (client ctrl-c) '.$this->clients[$k]->getRemoteIP().':'.$this->clients[$k]->getRemotePort());
                array_splice ($this->clients, $k, 1);
                $k--;
                $this->numClients--;
            }
        }

        return $activity;
    }
}
