<?php
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

$pdo = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form_type'] === 'train') {
        $stmt = $pdo->prepare("INSERT INTO trains(train_code, train_name, origin_station, destination_station, departure_time, arrival_time, fare, available_seats, status, created_at)
                               VALUES(?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            trim($_POST['train_code'] ?? ''),
            trim($_POST['train_name'] ?? ''),
            trim($_POST['origin_station'] ?? ''),
            trim($_POST['destination_station'] ?? ''),
            $_POST['departure_time'] ?? '',
            $_POST['arrival_time'] ?? '',
            (float)($_POST['fare'] ?? 0),
            (int)($_POST['available_seats'] ?? 0),
            $_POST['status'] ?? 'Active',
            date('Y-m-d H:i:s')
        ]);

        $msg = 'Train schedule added to the operations board.';
    }

    if (isset($_POST['form_type']) && $_POST['form_type'] === 'train') {
        $train_id = (int)($_POST['train_id'] ?? 0);
        $seat_count = max(1, (int)($_POST['seat_count'] ?? 1));

        $stmt = $pdo->prepare("SELECT fare FROM trains WHERE id=?");
        $stmt->execute([$train_id]);
        $fare = (float)$stmt->fetchColumn();
        $total = $fare * $seat_count;

        $stmt = $pdo->prepare("INSERT INTO tickets(passenger_name, contact, train_id, travel_date, seat_count, total_amount, booking_status, payment_status, created_at)
                               VALUES(?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            trim($_POST['passenger_name'] ?? ''),
            $_POST['contact'] ?? '',
            $train_id,
            $_POST['travel_date'] ?? '',
            $seat_count,
            $total,
            $_POST['booking_status'] ?? 'Pending',
            $_POST['payment_status'] ?? 'Unpaid',
            date('Y-m-d H:i:s')
        ]);

        if (($_POST['booking_status'] ?? '') === 'Confirmed') {
            $stmt = $pdo->prepare("UPDATE trains SET available_seats = available_seats - ? WHERE id=?");
            $stmt->execute([$seat_count, $train_id]);
        }

        $msg = 'Ticket booking added to the booking manifest.';
    }
}

$trains = $pdo->query("SELECT * FROM trains ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$tickets = $pdo->query("SELECT tickets.*, trains.train_code, trains.train_name, trains.origin_station, trains.destination_station
                        FROM tickets
                        JOIN trains ON trains.id = tickets.train_id
                        ORDER BY tickets.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$totalTrains = count($trains);
$totalTickets = count($tickets);
$totalSeats = 0;
$totalRevenue = 0;
foreach ($trains as $t) { $totalSeats += (int)$t['available_seats']; }
foreach ($tickets as $b) { $totalRevenue += (float)$b['total_amount']; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Railway Management System</title>
    <style>
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Consolas, "Segoe UI", Arial, sans-serif;
            background:#050816;
            color:#e5e7eb;
        }
        .app{
            display:grid;
            grid-template-columns:270px 1fr;
            min-height:100vh;
        }
        .sidebar{
            background:#0b1120;
            border-right:1px solid #1f2937;
            padding:24px 18px;
            position:sticky;
            top:0;
            height:100vh;
        }
        .logo{
            width:70px;
            height:70px;
            border-radius:18px;
            display:grid;
            place-items:center;
            background:#facc15;
            color:#111827;
            font-size:34px;
            font-weight:900;
            margin-bottom:18px;
        }
        .side-title{
            font-size:25px;
            line-height:1.05;
            font-weight:900;
            color:white;
            margin-bottom:8px;
        }
        .side-small{
            color:#9ca3af;
            font-size:13px;
            line-height:1.5;
            margin-bottom:25px;
        }
        .nav-block{
            background:#111827;
            border:1px solid #1f2937;
            border-radius:18px;
            padding:14px;
            margin-bottom:14px;
        }
        .nav-block b{
            display:block;
            color:#facc15;
            margin-bottom:5px;
        }
        .nav-block span{
            color:#d1d5db;
            font-size:13px;
        }
        .api a{
            display:block;
            color:#bbf7d0;
            text-decoration:none;
            font-size:13px;
            padding:7px 0;
            border-bottom:1px dashed #1f2937;
        }
        .main{
            padding:26px;
        }
        .station-board{
            background:#111827;
            border:1px solid #374151;
            border-radius:22px;
            overflow:hidden;
            margin-bottom:22px;
            box-shadow:0 18px 60px rgba(0,0,0,.35);
        }
        .board-top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            background:#020617;
            padding:18px 22px;
            border-bottom:1px solid #374151;
        }
        .board-top h1{
            margin:0;
            color:#facc15;
            font-size:28px;
            letter-spacing:2px;
            text-transform:uppercase;
        }
        .clock-badge{
            background:#064e3b;
            color:#dcfce7;
            padding:10px 14px;
            border-radius:999px;
            font-weight:900;
        }
        .stats{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:1px;
            background:#374151;
        }
        .stat{
            background:#111827;
            padding:18px;
        }
        .stat label{
            display:block;
            color:#9ca3af;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1px;
        }
        .stat strong{
            display:block;
            margin-top:8px;
            font-size:28px;
            color:white;
        }
        .message{
            background:#052e16;
            color:#bbf7d0;
            border:1px solid #166534;
            padding:13px 16px;
            border-radius:15px;
            margin-bottom:20px;
            font-weight:900;
        }
        .layout{
            display:grid;
            grid-template-columns:1.1fr .9fr;
            gap:22px;
            align-items:start;
        }
        .panel{
            background:#f8fafc;
            color:#111827;
            border-radius:22px;
            overflow:hidden;
            margin-bottom:22px;
            box-shadow:0 18px 55px rgba(0,0,0,.30);
        }
        .panel-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            background:#facc15;
            color:#111827;
            padding:14px 18px;
            font-weight:900;
            letter-spacing:.5px;
            text-transform:uppercase;
        }
        .panel-body{
            padding:18px;
        }
        .split-form{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        .field-full{grid-column:1 / -1}
        label{
            display:block;
            font-size:12px;
            text-transform:uppercase;
            color:#374151;
            font-weight:900;
            margin-bottom:5px;
        }
        input,select{
            width:100%;
            padding:11px 12px;
            border-radius:12px;
            border:1px solid #cbd5e1;
            background:white;
            font-size:14px;
        }
        input:focus,select:focus{
            outline:none;
            border-color:#22c55e;
            box-shadow:0 0 0 3px rgba(34,197,94,.18);
        }
        button{
            width:100%;
            border:0;
            border-radius:14px;
            background:#064e3b;
            color:white;
            padding:13px 15px;
            font-weight:900;
            margin-top:8px;
            cursor:pointer;
            letter-spacing:.5px;
        }
        button:hover{
            background:#111827;
            color:#facc15;
        }
        .route-list{
            display:grid;
            gap:12px;
        }
        .route-card{
            border:1px solid #e5e7eb;
            border-radius:16px;
            padding:14px;
            background:white;
            position:relative;
            overflow:hidden;
        }
        .route-card:before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            bottom:0;
            width:7px;
            background:#22c55e;
        }
        .route-title{
            display:flex;
            justify-content:space-between;
            gap:10px;
            padding-left:8px;
            font-weight:900;
            color:#111827;
        }
        .route-meta{
            padding-left:8px;
            margin-top:8px;
            color:#4b5563;
            font-size:13px;
        }
        .route-status{
            background:#dcfce7;
            color:#166534;
            padding:5px 9px;
            border-radius:999px;
            font-size:12px;
            white-space:nowrap;
        }
        .manifest{
            width:100%;
            border-collapse:separate;
            border-spacing:0 10px;
        }
        .manifest th{
            text-align:left;
            font-size:12px;
            text-transform:uppercase;
            color:#6b7280;
            padding:0 10px;
        }
        .manifest td{
            background:white;
            padding:12px 10px;
            border-top:1px solid #e5e7eb;
            border-bottom:1px solid #e5e7eb;
            font-size:13px;
        }
        .manifest td:first-child{
            border-left:1px solid #e5e7eb;
            border-radius:12px 0 0 12px;
            font-weight:900;
        }
        .manifest td:last-child{
            border-right:1px solid #e5e7eb;
            border-radius:0 12px 12px 0;
            font-weight:900;
            color:#166534;
        }
        @media(max-width:1050px){
            .app{grid-template-columns:1fr}
            .sidebar{height:auto;position:relative}
            .layout{grid-template-columns:1fr}
            .stats{grid-template-columns:1fr 1fr}
        }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="logo">↔</div>
        <div class="side-title">Railway<br>Control</div>
        <div class="side-small">A station operations board for managing train schedules and ticket reservations.</div>

        <div class="nav-block">
            <b>System Pair</b>
            <span>PHP Railway Management + C# Train Ticket Booking</span>
        </div>

        <div class="nav-block api">
            <b>API Test</b>
            <a href="api.php?action=ping">Ping API</a>
            <a href="api.php?action=list_trains">Train JSON Feed</a>
            <a href="api.php?action=list_tickets">Ticket JSON Feed</a>
        </div>
    </aside>

    <main class="main">
        <section class="station-board">
            <div class="board-top">
                <h1>Station Operations Board</h1>
                <div class="clock-badge">LOCAL SERVER : 8000</div>
            </div>
            <div class="stats">
                <div class="stat"><label>Total Trains</label><strong><?=htmlspecialchars($totalTrains)?></strong></div>
                <div class="stat"><label>Total Tickets</label><strong><?=htmlspecialchars($totalTickets)?></strong></div>
                <div class="stat"><label>Open Seats</label><strong><?=htmlspecialchars($totalSeats)?></strong></div>
                <div class="stat"><label>Revenue</label><strong>₱<?=number_format($totalRevenue,0)?></strong></div>
            </div>
        </section>

        <?php if($msg): ?><div class="message"><?=htmlspecialchars($msg)?></div><?php endif; ?>

        <div class="layout">
            <section>
                <div class="panel">
                    <div class="panel-head">
                        <span>Dispatch New Train</span>
                        <span>Schedule Form</span>
                    </div>
                    <div class="panel-body">
                        <form method="post" class="split-form">
                            <input type="hidden" name="form_type" value="train">

                            <div>
                                <label>Train Code</label>
                                <input name="train_code" required placeholder="RLY-505">
                            </div>

                            <div>
                                <label>Train Name</label>
                                <input name="train_name" required placeholder="Central Express">
                            </div>

                            <div>
                                <label>Origin Station</label>
                                <input name="origin_station" required placeholder="Manila Central">
                            </div>

                            <div>
                                <label>Destination Station</label>
                                <input name="destination_station" required placeholder="Baguio Terminal">
                            </div>

                            <div>
                                <label>Departure</label>
                                <input name="departure_time" placeholder="06:00 AM">
                            </div>

                            <div>
                                <label>Arrival</label>
                                <input name="arrival_time" placeholder="12:30 PM">
                            </div>

                            <div>
                                <label>Fare</label>
                                <input type="number" step="0.01" name="fare" value="500">
                            </div>

                            <div>
                                <label>Seats</label>
                                <input type="number" name="available_seats" value="100">
                            </div>

                            <div class="field-full">
                                <label>Status</label>
                                <select name="status">
                                    <option>Active</option>
                                    <option>Delayed</option>
                                    <option>Cancelled</option>
                                    <option>Maintenance</option>
                                </select>
                            </div>

                            <div class="field-full">
                                <button>Save Train Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">
                        <span>Live Route Cards</span>
                        <span><?=htmlspecialchars($totalTrains)?> routes</span>
                    </div>
                    <div class="panel-body">
                        <div class="route-list">
                            <?php foreach($trains as $t): ?>
                            <div class="route-card">
                                <div class="route-title">
                                    <span><?=htmlspecialchars($t['train_code'].' / '.$t['train_name'])?></span>
                                    <span class="route-status"><?=htmlspecialchars($t['status'])?></span>
                                </div>
                                <div class="route-meta">
                                    <?=htmlspecialchars($t['origin_station'])?> → <?=htmlspecialchars($t['destination_station'])?><br>
                                    Departure <?=htmlspecialchars($t['departure_time'])?> • Arrival <?=htmlspecialchars($t['arrival_time'])?> • ₱<?=number_format((float)$t['fare'],2)?> • Seats <?=htmlspecialchars($t['available_seats'])?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="panel">
                    <div class="panel-head">
                        <span>Ticket Counter</span>
                        <span>Booking Form</span>
                    </div>
                    <div class="panel-body">
                        <form method="post">
                            <input type="hidden" name="form_type" value="ticket">

                            <label>Passenger Name</label>
                            <input name="passenger_name" required placeholder="Juan Dela Cruz">

                            <label>Contact</label>
                            <input name="contact" placeholder="09XXXXXXXXX">

                            <label>Train Route</label>
                            <select name="train_id">
                                <?php foreach($trains as $t): ?>
                                <option value="<?=htmlspecialchars($t['id'])?>">
                                    <?=htmlspecialchars($t['train_code'].' - '.$t['train_name'].' | '.$t['origin_station'].' to '.$t['destination_station'].' (PHP '.$t['fare'].')')?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                            <label>Travel Date</label>
                            <input type="date" name="travel_date" required>

                            <label>Seat Count</label>
                            <input type="number" name="seat_count" value="1" min="1">

                            <label>Booking Status</label>
                            <select name="booking_status">
                                <option>Pending</option>
                                <option>Confirmed</option>
                                <option>Cancelled</option>
                            </select>

                            <label>Payment Status</label>
                            <select name="payment_status">
                                <option>Unpaid</option>
                                <option>Paid</option>
                                <option>Partial</option>
                            </select>

                            <button>Issue Ticket Booking</button>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">
                        <span>Passenger Manifest</span>
                        <span><?=htmlspecialchars($totalTickets)?> tickets</span>
                    </div>
                    <div class="panel-body">
                        <table class="manifest">
                            <tr><th>ID</th><th>Passenger</th><th>Train</th><th>Total</th><th>Status</th></tr>
                            <?php foreach($tickets as $b): ?>
                            <tr>
                                <td>#<?=htmlspecialchars($b['id'])?></td>
                                <td><?=htmlspecialchars($b['passenger_name'])?></td>
                                <td><?=htmlspecialchars($b['train_code'].' '.$b['train_name'])?></td>
                                <td>₱<?=number_format((float)$b['total_amount'],2)?></td>
                                <td><?=htmlspecialchars($b['booking_status'].' / '.$b['payment_status'])?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
