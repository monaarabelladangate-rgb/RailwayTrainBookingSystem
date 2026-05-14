<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$dbFile = __DIR__ . '/railway_booking.sqlite';

function db() {
    global $dbFile;

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS trains (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        train_code TEXT NOT NULL,
        train_name TEXT NOT NULL,
        origin_station TEXT NOT NULL,
        destination_station TEXT NOT NULL,
        departure_time TEXT NOT NULL,
        arrival_time TEXT NOT NULL,
        fare REAL NOT NULL DEFAULT 0,
        available_seats INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        passenger_name TEXT NOT NULL,
        contact TEXT,
        train_id INTEGER NOT NULL,
        travel_date TEXT NOT NULL,
        seat_count INTEGER NOT NULL DEFAULT 1,
        total_amount REAL NOT NULL DEFAULT 0,
        booking_status TEXT NOT NULL DEFAULT 'Pending',
        payment_status TEXT NOT NULL DEFAULT 'Unpaid',
        created_at TEXT NOT NULL
    )");

    $count = $pdo->query("SELECT COUNT(*) FROM trains")->fetchColumn();

    if ((int)$count === 0) {
        $stmt = $pdo->prepare("INSERT INTO trains(train_code, train_name, origin_station, destination_station, departure_time, arrival_time, fare, available_seats, status, created_at)
                               VALUES(?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute(['RLY-101', 'North Express', 'Manila Central', 'Baguio Terminal', '06:00 AM', '12:30 PM', 850, 80, 'Active', date('Y-m-d H:i:s')]);
        $stmt->execute(['RLY-202', 'Coastal Runner', 'Manila Central', 'Batangas Station', '08:00 AM', '11:00 AM', 450, 100, 'Active', date('Y-m-d H:i:s')]);
        $stmt->execute(['RLY-303', 'Southern Link', 'Quezon Hub', 'Naga Terminal', '09:30 AM', '05:00 PM', 1100, 75, 'Active', date('Y-m-d H:i:s')]);
        $stmt->execute(['RLY-404', 'Metro Shuttle', 'North Avenue', 'Alabang Station', '07:15 AM', '08:45 AM', 120, 150, 'Active', date('Y-m-d H:i:s')]);
    }

    return $pdo;
}

function ok($data = []) {
    echo json_encode(['success' => true] + $data);
    exit;
}

function fail($msg) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$pdo = db();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list_trains';

try {
    if ($action === 'ping') {
        ok(['message' => 'Railway API is running']);
    }

    if ($action === 'list_trains') {
        $rows = $pdo->query("SELECT * FROM trains ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        ok(['trains' => $rows]);
    }

    if ($action === 'add_train') {
        $train_code = trim($_POST['train_code'] ?? '');
        $train_name = trim($_POST['train_name'] ?? '');
        $origin = trim($_POST['origin_station'] ?? '');
        $destination = trim($_POST['destination_station'] ?? '');

        if ($train_code === '' || $train_name === '' || $origin === '' || $destination === '') {
            fail('Train code, train name, origin, and destination are required.');
        }

        $stmt = $pdo->prepare("INSERT INTO trains(train_code, train_name, origin_station, destination_station, departure_time, arrival_time, fare, available_seats, status, created_at)
                               VALUES(?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $train_code,
            $train_name,
            $origin,
            $destination,
            $_POST['departure_time'] ?? '',
            $_POST['arrival_time'] ?? '',
            (float)($_POST['fare'] ?? 0),
            (int)($_POST['available_seats'] ?? 0),
            $_POST['status'] ?? 'Active',
            date('Y-m-d H:i:s')
        ]);

        ok(['message' => 'Train record added successfully.']);
    }

    if ($action === 'update_train_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id <= 0 || $status === '') {
            fail('Train ID and status are required.');
        }

        $stmt = $pdo->prepare("UPDATE trains SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);

        ok(['message' => 'Train status updated.']);
    }

    if ($action === 'list_tickets') {
        $sql = "SELECT tickets.id, tickets.passenger_name, tickets.contact, tickets.travel_date,
                       tickets.seat_count, tickets.total_amount, tickets.booking_status,
                       tickets.payment_status, tickets.created_at,
                       trains.train_code, trains.train_name, trains.origin_station,
                       trains.destination_station, trains.departure_time, trains.arrival_time, trains.fare
                FROM tickets
                JOIN trains ON trains.id = tickets.train_id
                ORDER BY tickets.id DESC";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        ok(['tickets' => $rows]);
    }

    if ($action === 'create_ticket') {
        $passenger = trim($_POST['passenger_name'] ?? '');
        $train_id = (int)($_POST['train_id'] ?? 0);
        $travel_date = trim($_POST['travel_date'] ?? '');
        $seat_count = max(1, (int)($_POST['seat_count'] ?? 1));

        if ($passenger === '' || $train_id <= 0 || $travel_date === '') {
            fail('Passenger, train, and travel date are required.');
        }

        $stmt = $pdo->prepare("SELECT fare, available_seats FROM trains WHERE id=?");
        $stmt->execute([$train_id]);
        $train = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$train) {
            fail('Train not found.');
        }

        if ((int)$train['available_seats'] < $seat_count) {
            fail('Not enough available seats.');
        }

        $total = (float)$train['fare'] * $seat_count;

        $stmt = $pdo->prepare("INSERT INTO tickets(passenger_name, contact, train_id, travel_date, seat_count, total_amount, booking_status, payment_status, created_at)
                               VALUES(?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $passenger,
            $_POST['contact'] ?? '',
            $train_id,
            $travel_date,
            $seat_count,
            $total,
            $_POST['booking_status'] ?? 'Pending',
            $_POST['payment_status'] ?? 'Unpaid',
            date('Y-m-d H:i:s')
        ]);

        if (($_POST['booking_status'] ?? 'Pending') === 'Confirmed') {
            $stmt = $pdo->prepare("UPDATE trains SET available_seats = available_seats - ? WHERE id=?");
            $stmt->execute([$seat_count, $train_id]);
        }

        ok(['message' => 'Ticket booking created successfully.', 'total_amount' => $total]);
    }

    if ($action === 'update_ticket_status') {
        $id = (int)($_POST['id'] ?? 0);
        $booking_status = trim($_POST['booking_status'] ?? '');
        $payment_status = trim($_POST['payment_status'] ?? '');

        if ($id <= 0 || $booking_status === '' || $payment_status === '') {
            fail('Ticket ID, booking status, and payment status are required.');
        }

        $stmt = $pdo->prepare("UPDATE tickets SET booking_status=?, payment_status=? WHERE id=?");
        $stmt->execute([$booking_status, $payment_status, $id]);

        ok(['message' => 'Ticket status updated.']);
    }

    fail('Invalid action.');
} catch (Exception $e) {
    fail($e->getMessage());
}
?>