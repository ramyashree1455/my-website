
<!DOCTYPE html>
<html>
<head>
    <title>Medical Store</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php
session_start();

include 'db.php';
include 'image_helper.php';
?>

<div class="navbar">
    <h1>💊 MediCare</h1>
    <?php if(isset($_SESSION['customer_id'])): ?>
        <div class="nav-right">
            Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> | <a href="logout.php">Logout</a>
        </div>
    <?php else: ?>
        <div class="nav-right">
            <a href="login.php">Login</a> | <a href="register.php">Register</a>
        </div>
    <?php endif; ?>
</div>

<div class="search-box">
    <form action="search.php" method="GET">
        <input type="text" name="disease" placeholder="Search disease (Cold, Fever...)">
        <button type="submit">Search</button>
    </form>
</div>

<div class="hero">
    <h2>Welcome to MediCare - Your Online Medical Store</h2>
    <p>Order medicines online with ease and get them delivered to your doorstep.</p>
</div>

<div class="categories">
    <h2>Shop by Category</h2>
    <div class="category-list">
        <a href="category.php?cat=cold-flu" class="category">Cold & Flu</a>
        <a href="category.php?cat=pain-relief" class="category">Pain Relief</a>
        <a href="category.php?cat=digestive-health" class="category">Digestive Health</a>
        <a href="category.php?cat=skin-care" class="category">Skin Care</a>
        <a href="category.php?cat=vitamins-supplements" class="category">Vitamins & Supplements</a>
    </div>
</div>

<div class="featured">
    <h2>Featured Medicines</h2>
    <div class="container">
        <?php
        $sql = "SELECT DISTINCT m.medicine_id, m.medicine_name, m.price, s.quantity, s.expiry_date
                FROM Medicine m
                JOIN Stock s ON m.medicine_id = s.medicine_id
                WHERE LOWER(m.medicine_name) NOT LIKE '%cetirizine%'
                LIMIT 6";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            if (strpos(strtolower($row['medicine_name']), 'cetirizine') !== false) {
                continue;
            }
            $image = getMedicineImage($row['medicine_name']);
            $class = ($row['quantity'] > 0) ? "available" : "out";
            echo "<a href='product.php?id=" . $row['medicine_id'] . "' class='card-link'>";
            echo "<div class='card'>";
            echo "<div class='card-image'><img src='$image' alt='" . $row['medicine_name'] . "'></div>";
            echo "<div class='card-body'>";
            echo "<h3>" . $row['medicine_name'] . "</h3>";
            echo "<p class='price'>₹" . number_format($row['price'], 2) . "</p>";
            echo "<p class='stock $class'>" . ($row['quantity'] > 0 ? "Stock: " . $row['quantity'] . " available" : "Out of stock") . "</p>";
            echo "<p class='expiry'>Expiry: " . $row['expiry_date'] . "</p>";
            echo "</div>";
            echo "</div>";
            echo "</a>";
        }
        ?>
    </div>
</div>

</body>
</html>