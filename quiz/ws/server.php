<?php
/**
 * Minimal WebSocket server for quiz real-time events.
 * Run: php quiz/ws/server.php
 */

require __DIR__ . '/../lib/bootstrap.php';

use Quiz\Lib\Database;

$config = require __DIR__ . '/../config/config.php';
$host = $config['realtime']['ws_host'];
$port = $config['realtime']['ws_port'];

$server = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "WebSocket bind failed: {$errstr}\n");
    exit(1);
}

stream_set_blocking($server, false);
$clients = [];
$meta = [];
$pdo = Database::pdo();

echo "Quiz WS server started on {$host}:{$port}\n";

while (true) {
    $read = array_merge([$server], $clients);
    $write = $except = null;
    stream_select($read, $write, $except, 0, 200000);

    foreach ($read as $sock) {
        if ($sock === $server) {
            $conn = stream_socket_accept($server, 0);
            if ($conn) {
                stream_set_blocking($conn, false);
                $clients[(int)$conn] = $conn;
                $meta[(int)$conn] = ['handshake' => false, 'student_id' => null, 'session_id' => null, 'last_ping_at_ms' => 0];
            }
            continue;
        }

        $data = fread($sock, 4096);
        if (!$data) {
            @fclose($sock);
            unset($clients[(int)$sock], $meta[(int)$sock]);
            continue;
        }

        $key = (int)$sock;
        if (!$meta[$key]['handshake']) {
            if (preg_match("#Sec-WebSocket-Key: (.*)\r\n#", $data, $m)) {
                $accept = base64_encode(sha1(trim($m[1]) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
                $upgrade = "HTTP/1.1 101 Switching Protocols\r\n"
                    . "Upgrade: websocket\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
                fwrite($sock, $upgrade);
                $meta[$key]['handshake'] = true;
                sendFrame($sock, json_encode(['type' => 'hello', 'server_ts_ms' => nowMs()]));
            }
            continue;
        }

        $payload = decodeFrame($data);
        if ($payload === null) {
            continue;
        }

        $message = json_decode($payload, true);
        if (!is_array($message) || empty($message['type'])) {
            continue;
        }

        if ($message['type'] === 'auth') {
            $meta[$key]['student_id'] = (int)($message['student_id'] ?? 0);
            $meta[$key]['session_id'] = (int)($message['session_id'] ?? 0);
            sendFrame($sock, json_encode(['type' => 'auth_ok']));
        }

        if ($message['type'] === 'ping') {
            $sentAt = (int)($message['sent_at_ms'] ?? nowMs());
            $rtt = max(0, nowMs() - $sentAt);
            $latency = (int) floor($rtt / 2);

            if ($meta[$key]['student_id'] && $meta[$key]['session_id']) {
                $stmt = $pdo->prepare('UPDATE quiz_session_students SET last_ping_ms = ? WHERE session_id = ? AND student_id = ?');
                $stmt->execute([$latency, $meta[$key]['session_id'], $meta[$key]['student_id']]);
            }

            sendFrame($sock, json_encode(['type' => 'pong', 'server_ts_ms' => nowMs(), 'rtt_ms' => $rtt]));
        }
    }
}

function nowMs(): int
{
    return (int) floor(microtime(true) * 1000);
}

function decodeFrame(string $data): ?string
{
    if (strlen($data) < 6) {
        return null;
    }
    $len = ord($data[1]) & 127;
    $offset = 2;

    if ($len === 126) {
        $len = unpack('n', substr($data, 2, 2))[1];
        $offset = 4;
    } elseif ($len === 127) {
        return null; // unsupported for simplicity
    }

    $mask = substr($data, $offset, 4);
    $offset += 4;
    $payload = substr($data, $offset, $len);
    $decoded = '';
    for ($i = 0; $i < $len; $i++) {
        $decoded .= $payload[$i] ^ $mask[$i % 4];
    }
    return $decoded;
}

function sendFrame($client, string $payload): void
{
    $len = strlen($payload);
    $head = chr(129);

    if ($len <= 125) {
        $head .= chr($len);
    } elseif ($len < 65536) {
        $head .= chr(126) . pack('n', $len);
    } else {
        return;
    }

    fwrite($client, $head . $payload);
}
