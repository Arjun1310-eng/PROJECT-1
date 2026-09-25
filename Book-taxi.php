<?php
session_start();
include "db.php";

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: login.html");
    exit();
}

$message = "";

// Cumulative distance in KM from the starting point (Manadu)
$locations = [
    "Manadu"               => 0,
    "Paramankuruchi"       => 3,
    "Nalumulai kinaru"     => 8,
    "Tiruchendur"          => 13,
    "Virapandianpatanam"   => 15,
    "Kayalpatanam"         => 22,
    "Arumuganeri"          => 27,
    "Sagupuram"            => 30,
    "authoor"              => 35,
    "Mukani"               => 37,
    "Palayakayal"          => 42
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username    = $_SESSION["username"];
    $pickup      = trim($_POST["pickup"]);
    $dropoff     = trim($_POST["dropoff"]);
    $pickup_time = $_POST["pickup_time"];

    if ($pickup === $dropoff) {
        $message = "<p style='color: red;'>Pickup and Drop-off locations cannot be the same!</p>";
    } elseif (!isset($locations[$pickup]) || !isset($locations[$dropoff])) {
        $message = "<p style='color: red;'>Please select valid locations.</p>";
    } else {
        // Automatic distance calculation
        $distance_km = abs($locations[$dropoff] - $locations[$pickup]);

        // Base ₹50 + ₹15 per KM
        $base_fare   = 50.00;
        $rate_per_km = 15.00;
        $fare        = $base_fare + ($distance_km * $rate_per_km);

        $stmt = $conn->prepare("INSERT INTO bookings (username, pickup, dropoff, distance_km, fare, pickup_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdds", $username, $pickup, $dropoff, $distance_km, $fare, $pickup_time);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Taxi booked successfully!<br>
                        <b>Pickup:</b> " . htmlspecialchars($pickup) . "<br>
                        <b>Dropoff:</b> " . htmlspecialchars($dropoff) . "<br>
                        <b>Distance:</b> " . $distance_km . " KM<br>
                        <b>Estimated Fare:</b> ₹" . number_format($fare, 2) . "</p>";
        } else {
            $message = "<p style='color: red;'>Booking failed: " . $stmt->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Taxi - Call Taxi Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script>
        // Exact cumulative locations for real-time frontend calculation
        const places = {
            "Manadu": 0,
            "Paramankuruchi": 3,
            "Nalumulai kinaru": 8,
            "Tiruchendur": 13,
            "Virapandianpatanam": 15,
            "Kayalpatanam": 22,
            "Arumuganeri": 27,
            "Sagupuram": 30,
            "authoor": 35,
            "Mukani": 37,
            "Palayakayal": 42
        };

        function updateFare() {
            const pickup = document.getElementById("pickup").value;
            const dropoff = document.getElementById("dropoff").value;

            if (!pickup || !dropoff) {
                document.getElementById("dist_preview").innerText = "0 KM";
                document.getElementById("fare_preview").innerText = "₹0.00";
                return;
            }

            if (pickup === dropoff) {
                document.getElementById("dist_preview").innerText = "0 KM";
                document.getElementById("fare_preview").innerText = "Select different places";
                return;
            }

            const km = Math.abs(places[dropoff] - places[pickup]);
            const baseFare = 50;
            const ratePerKm = 15;
            const total = baseFare + (km * ratePerKm);

            document.getElementById("dist_preview").innerText = km + " KM";
            document.getElementById("fare_preview").innerText = "₹" + total.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <h2>User Panel</h2>
            <a href="book-taxi.php">Book Taxi</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="content">
            <h1>Book a Taxi</h1>
            <?php echo $message; ?>
            
            <form action="book-taxi.php" method="post" style="max-width: 480px;">
                <label><b>Pickup Location:</b></label><br>
                <select id="pickup" name="pickup" onchange="updateFare()" required style="width: 100%; padding: 10px; margin: 8px 0;">
                    <option value="">-- Select Pickup Location --</option>
                    <?php foreach ($locations as $place => $dist): ?>
                        <option value="<?php echo htmlspecialchars($place); ?>"><?php echo htmlspecialchars($place); ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label><b>Dropoff Location:</b></label><br>
                <select id="dropoff" name="dropoff" onchange="updateFare()" required style="width: 100%; padding: 10px; margin: 8px 0;">
                    <option value="">-- Select Dropoff Location --</option>
                    <?php foreach ($locations as $place => $dist): ?>
                        <option value="<?php echo htmlspecialchars($place); ?>"><?php echo htmlspecialchars($place); ?></option>
                    <?php endforeach; ?>
                </select><br>

                <div style="background: #f4f7f6; padding: 12px; border-radius: 6px; margin: 10px 0;">
                    <p style="margin: 0; color: #333;">Estimated Distance: <b id="dist_preview" style="color: #1f4e79;">0 KM</b></p>
                    <p style="margin: 5px 0 0 0; color: #333;">Total Fare: <b id="fare_preview" style="color: #27ae60; font-size: 18px;">₹0.00</b></p>
                </div>

                <label><b>Date & Time:</b></label><br>
                <input type="datetime-local" name="pickup_time" required style="width: 100%; padding: 10px; margin: 8px 0;"><br><br>

                <button type="submit" style="padding: 12px 20px; cursor: pointer; width: 100%;">Confirm Booking</button>
            </form>
        </main>
    </div>
</body>
</html>