<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:serve';

    protected $description = 'Start the ag-telemetry WebSocket broadcasting server with DB polling';

    private array $clients = [];
    private array $wsClients = [];
    private int $lastBroadcastId = 0;
    private int $lastHeartbeat = 0;
    private int $clientCounter = 0;
    private const HEARTBEAT_INTERVAL = 30; // seconds
    private const POLL_INTERVAL = 2;       // seconds
    private const MAX_CLIENT_AGE = 120;    // seconds — disconnect silent clients

    public function handle()
    {
        $port = 8080;
        $this->info("Starting HarvestHaul WebSocket Server on port {$port}...");

        $server = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$server) {
            $this->error("socket_create failed: " . socket_strerror(socket_last_error()));
            return 1;
        }

        @socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
        @socket_set_nonblock($server);

        if (!@socket_bind($server, '0.0.0.0', $port)) {
            $this->error("socket_bind failed: " . socket_strerror(socket_last_error()));
            return 1;
        }

        if (!@socket_listen($server)) {
            $this->error("socket_listen failed: " . socket_strerror(socket_last_error()));
            return 1;
        }

        $serverId = $this->clientCounter++;
        $this->clients[$serverId] = $server;
        $lastPoll = 0;
        $this->lastHeartbeat = time();
        $clientActivity = [];

        $this->info("Listening on port {$port}. Polling tracking_records every " . self::POLL_INTERVAL . "s.");

        while (true) {
            $read = $this->clients;
            $write = null;
            $except = null;

            if (@socket_select($read, $write, $except, 1) < 1) {
                $now = time();

                // Periodic DB poll for new tracking records
                if ($now - $lastPoll >= $this::POLL_INTERVAL) {
                    $this->broadcastNewRecords();
                    $lastPoll = $now;
                }

                // Periodic heartbeat + cleanup dead clients
                if ($now - $this->lastHeartbeat >= $this::HEARTBEAT_INTERVAL) {
                    $this->heartbeat();
                    $this->lastHeartbeat = $now;
                }

                continue;
            }

            // Accept new connections
            if (in_array($server, $read ?? [])) {
                $client = @socket_accept($server);
                if ($client) {
                    @socket_set_nonblock($client);
                    $clientId = $this->clientCounter++;
                    $this->clients[$clientId] = $client;
                    $clientActivity[$clientId] = time();

                    $request = @socket_read($client, 8192);
                    if ($request && preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $request, $matches)) {
                        // Validate token from query string
                        $token = null;
                        if (preg_match("/GET\s+\/\?token=([^\s]+)\s+HTTP/", $request, $tokenMatch)) {
                            $token = urldecode($tokenMatch[1]);
                        }
                        
                        // For now, accept connections with any token (implement proper validation as needed)
                        // In production, validate against a whitelist of valid tokens
                        if (!$token) {
                            $reject = "HTTP/1.1 401 Unauthorized\r\n\r\n";
                            @socket_write($client, $reject, strlen($reject));
                            @socket_close($client);
                            unset($this->clients[$clientId], $clientActivity[$clientId]);
                            continue;
                        }
                        
                        $secKey = trim($matches[1]);
                        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
                        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                                   "Upgrade: websocket\r\n" .
                                   "Connection: Upgrade\r\n" .
                                   "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
                        @socket_write($client, $upgrade, strlen($upgrade));
                        $this->wsClients[$clientId] = $client;
                        $this->info("Map viewer connected with token.");
                    }
                    // Non-WebSocket TCP connections (from publish endpoint) are handled
                    // by the DB poll approach — no need for per-ping connections from TrackingController.
                }
                $read = array_filter($read ?? [], fn($c) => $c !== $server);
            }

            // Read from existing clients
            foreach ($read ?? [] as $client) {
                $id = array_search($client, $this->clients, true);
                if ($id === false) continue;
                
                $data = @socket_read($client, 8192);
                if ($data === false || strlen($data) === 0) {
                    $this->disconnectClient($client, $id);
                    unset($clientActivity[$id]);
                } else {
                    $clientActivity[$id] = time();
                    $decoded = $this->decode($data);
                    if ($decoded === false) {
                        // Protocol error or close frame
                        $this->disconnectClient($client, $id);
                        unset($clientActivity[$id]);
                    }
                    // Ignore other inbound frames (chat not supported on telemetry channel)
                }
            }

            // Cleanup stale clients (no activity for MAX_CLIENT_AGE)
            $now = time();
            foreach ($clientActivity as $id => $lastActive) {
                if ($now - $lastActive > $this::MAX_CLIENT_AGE && isset($this->wsClients[$id])) {
                    $this->info("Client {$id} timed out (inactive " . ($now - $lastActive) . "s).");
                    $this->disconnectClient($this->clients[$id] ?? null, $id);
                    unset($clientActivity[$id]);
                }
            }
        }
    }

    /**
     * Poll tracking_records for new entries and broadcast to all WebSocket clients.
     */
    private function broadcastNewRecords(): void
    {
        try {
            $records = DB::table('tracking_records')
                ->where('id', '>', $this->lastBroadcastId)
                ->orderBy('id')
                ->limit(50)
                ->get();

            if ($records->isEmpty()) return;

            foreach ($records as $record) {
                $this->lastBroadcastId = max($this->lastBroadcastId, $record->id);
                $payload = json_encode([
                    'pooling_job_id' => (int) $record->pooling_job_id,
                    'driver_id'      => (int) $record->driver_id,
                    'latitude'       => (float) $record->latitude,
                    'longitude'      => (float) $record->longitude,
                    'speed_kmh'      => (float) ($record->speed_kmh ?? 0),
                    'bearing'        => (float) ($record->bearing ?? 0),
                    'posted_at'      => $record->posted_at,
                ]);

                $frame = $this->encode($payload);
                foreach ($this->wsClients as $wid => $wsClient) {
                    if (@socket_write($wsClient, $frame, strlen($frame)) === false) {
                        $this->disconnectClient($wsClient, $wid);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("DB poll error: " . $e->getMessage());
        }
    }

    /**
     * Send WebSocket ping frames to all connected clients.
     */
    private function heartbeat(): void
    {
        $pingFrame = chr(0x89) . chr(0x00); // ping frame with no payload
        foreach ($this->wsClients as $id => $client) {
            if (@socket_write($client, $pingFrame, strlen($pingFrame)) === false) {
                $this->disconnectClient($client, $id);
            }
        }
        $this->info("Heartbeat sent to " . count($this->wsClients) . " clients.");
    }

    private function disconnectClient($client, int $id): void
    {
        if ($client) {
            @socket_close($client);
        }
        unset($this->clients[$id], $this->wsClients[$id]);
        $this->info("Client {$id} disconnected.");
    }

    private function encode(string $text): string
    {
        $b1 = 0x80 | 0x01; // FIN + text opcode
        $length = strlen($text);
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $text;
    }

    /**
     * Decode a WebSocket frame. Returns the text payload, or false on
     * protocol errors, close frames, or fragmented frames.
     */
    private function decode(string $payload): string|false
    {
        if (strlen($payload) < 2) return false;

        $firstByte = ord($payload[0]);
        $opcode = $firstByte & 0x0f;
        $masked = ord($payload[1]) & 0x80;
        $length = ord($payload[1]) & 0x7f;

        // Handle control frames
        if ($opcode === 0x8) return false; // close frame
        if ($opcode === 0x9) return '';    // ping — response will be auto-ignored
        if ($opcode === 0xA) return '';    // pong — ignore
        if ($opcode !== 0x1) return false; // non-text frame (binary, continuation, etc.)

        $offset = 2;
        if ($length === 126) {
            $length = unpack('n', substr($payload, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            $length = unpack('J', substr($payload, 2, 8))[1];
            $offset = 10;
        }

        if ($masked) {
            $mask = substr($payload, $offset, 4);
            $offset += 4;
        }

        $data = substr($payload, $offset, $length);
        if ($masked) {
            $text = '';
            for ($i = 0; $i < strlen($data); ++$i) {
                $text .= chr(ord($data[$i]) ^ ord($mask[$i % 4]));
            }
            return $text;
        }
        return $data;
    }
}
