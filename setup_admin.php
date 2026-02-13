<?php
/**
 * Setup Admin User in Database
 * Run this file once: http://localhost/BARCODEMENU/setup_admin.php
 * 
 * Alternative: Import cafemenu.sql directly (already includes admin table)
 */

require 'config.php';

try {
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "✅ Connected to database: " . DB_NAME . "<br><br>";
    
    // Step 1: Create admins table
    echo "📝 Creating admins table...<br>";
    
    $sql_create_table = "CREATE TABLE IF NOT EXISTS `admins` (
      `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `username` varchar(50) NOT NULL UNIQUE,
      `password` varchar(255) NOT NULL,
      `email` varchar(100),
      `role` varchar(50) DEFAULT 'admin',
      `is_active` tinyint DEFAULT 1,
      `last_login` datetime,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY `username_idx` (`username`),
      KEY `is_active_idx` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    if ($conn->query($sql_create_table) === TRUE) {
        echo "✅ Table 'admins' created/verified<br>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Step 2: Hash password
    echo "<br>🔐 Setting up admin user...<br>";
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@teazzi.id';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>$password</strong><br>";
    echo "Email: <strong>$email</strong><br>";
    echo "Password Hash: <code>" . substr($password_hash, 0, 20) . "...</code><br><br>";
    
    // Step 3: Insert or update admin user
    echo "📥 Inserting admin user into database...<br>";
    
    $sql_insert = "INSERT INTO `admins` 
                   (`username`, `password`, `email`, `role`, `is_active`) 
                   VALUES (?, ?, ?, 'admin', 1)
                   ON DUPLICATE KEY UPDATE 
                   `password` = VALUES(`password`),
                   `email` = VALUES(`email`),
                   `is_active` = 1";
    
    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sss", $username, $password_hash, $email);
    
    if ($stmt->execute()) {
        echo "✅ Admin user inserted/updated successfully<br>";
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Step 4: Verify user was created
    echo "<br>✓ Verifying admin user in database...<br>";
    
    $sql_verify = "SELECT id, username, email, role, is_active FROM admins WHERE username = ?";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bind_param("s", $username);
    $stmt_verify->execute();
    $result = $stmt_verify->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Admin user found in database:<br>";
        echo "<ul>";
        echo "<li>ID: " . htmlspecialchars($row['id']) . "</li>";
        echo "<li>Username: " . htmlspecialchars($row['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($row['email']) . "</li>";
        echo "<li>Role: " . htmlspecialchars($row['role']) . "</li>";
        echo "<li>Active: " . ($row['is_active'] ? 'YES ✅' : 'NO ❌') . "</li>";
        echo "</ul>";
    } else {
        throw new Exception("Admin user not found after insertion!");
    }
    
    echo "<br><hr>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>You can now login with:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='admin_login.php' class='btn btn-primary'>Go to Login Page →</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Database connection info in .env</li>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Database '" . DB_NAME . "' exists</li>";
    echo "<li>User '" . DB_USER . "' has proper permissions</li>";
    echo "</ul>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup Admin User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px; margin: 0 auto;">
</body>
</html>
