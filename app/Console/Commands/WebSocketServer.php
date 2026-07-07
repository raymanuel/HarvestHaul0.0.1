<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the custom ag-telemetry WebSocket broadcasting server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $port = 8080;
        $this->info("Starting HarvestHaul WebSocket Server on port {$port}...");

        $server = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$server) {
            $this->error("Failed to create socket: " . socket_strerror(socket_last_error()));
            return 1;
        }

        if (!@socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->error("Failed to set socket options: " . socket_strerror(socket_last_error()));
            return 1;
        }

        if (!@socket_bind($server, '0.0.0.0', $port)) {
            $this->error("Failed to bind socket to port {$port}: " . socket_strerror(socket_last_error()));
            return 1;
        }

        if (!@socket_listen($server)) {
            $this->error("Failed to listen on socket: " . socket_strerror(socket_last_error()));
            return 1;
        }

        $clients = [$server];
        $wsClients = [];

        $this->info("Listening for driver telemetry streams & viewer connections...");

        while (true) {
            $read = $clients;
            $write = null;
            $except = null;

            // Wait up to 1 second for socket activity
            if (@socket_select($read, $write, $except, 1) < 1) {
                continue;
            }

            if (in_array($server, $read)) {
                $client = @socket_accept($server);
                if ($client) {
                    $clients[] = $client;
                    $secKey = null;

                    $request = @socket_read($client, 5000);
                    if ($request && preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $request, $matches)) {
                        $secKey = trim($matches[1]);
                    }

                    if ($secKey) {
                        // Handshake with WebSocket client
                        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
                        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                                   "Upgrade: websocket\r\n" .
                                   "Connection: Upgrade\r\n" .
                                   "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
                        @socket_write($client, $upgrade, strlen($upgrade));
                        $wsClients[(int)$client] = $client;
                        $this->info("Map viewer client connected via WebSocket.");
                    } else {
                        // Raw TCP publish from local controller
                        $payload = trim($request);
                        if ($payload) {
                            $this->info("Telemetry broadcast: " . $payload);
                            $frame = $this->encode($payload);
                            foreach ($wsClients as $id => $wsClient) {
                                if (@socket_write($wsClient, $frame, strlen($frame)) === false) {
                                    @socket_close($wsClient);
                                    unset($wsClients[$id]);
                                    $clients = array_filter($clients, fn($c) => $c !== $wsClient);
                                }
                            }
                        }
                        @socket_close($client);
                        $clients = array_filter($clients, fn($c) => $c !== $client);
                    }
                }
                $read = array_filter($read, fn($c) => $c !== $server);
            }

            foreach ($read as $client) {
                $data = @socket_read($client, 2048);
                if ($data === false || strlen($data) === 0) {
                    @socket_close($client);
                    unset($wsClients[(int)$client]);
                    $clients = array_filter($clients, fn($c) => $c !== $client);
                    $this->info("Client disconnected.");
                } else {
                    $decoded = $this->decode($data);
                    if ($decoded) {
                        $this->info("Received: " . $decoded);
                    }
                }
            }
        }
    }

    private function encode($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $text;
    }

    private function decode($payload)
    {
        if (strlen($payload) < 6) return null;
        $length = ord($payload[1]) & 127;
        if ($length == 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        } elseif ($length == 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        } else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }
}
