<?php
// test_connection.php
echo "<h1>Database Connection Test</h1>";

try {
    // Test basic MySQLi connection
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "food_order_db";
    
    echo "<h2>Testing MySQLi Connection...</h2>";
    
    $connection = new mysqli($host, $username, $password, $database);
    
    if ($connection->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $connection->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connected successfully to database: " . $database . "</p>";
        
        // Test if we can query
        $result = $connection->query("SHOW TABLES");
        if ($result) {
            $tableCount = $result->num_rows;
            echo "<p style='color: green;'>✅ Database has " . $tableCount . " table(s)</p>";
            
            echo "<h3>Tables in database:</h3>";
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Could not fetch tables: " . $connection->error . "</p>";
        }
        
        // Test our Database class
        echo "<h2>Testing Database Class...</h2>";
        
        require_once 'includes/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($conn) {
            echo "<p style='color: green;'>✅ Database class connected successfully</p>";
            
            // Test a simple query
            $testQuery = $conn->query("SELECT 1 as test");
            if ($testQuery) {
                echo "<p style='color: green;'>✅ Test query executed successfully</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Test query failed: " . $conn->error . "</p>";
            }
            
            // Check if tables exist
            echo "<h3>Checking required tables:</h3>";
            $requiredTables = ['users', 'categories', 'menu_items', 'orders', 'order_items', 'user_addresses'];
            
            foreach ($requiredTables as $table) {
                $check = $conn->query("SHOW TABLES LIKE '$table'");
                if ($check->num_rows > 0) {
                    echo "<p style='color: green;'>✅ Table '$table' exists</p>";
                    
                    // Show table structure
                    $desc = $conn->query("DESCRIBE $table");
                    if ($desc) {
                        echo "<details><summary>View $table structure</summary>";
                        echo "<table border='1' cellpadding='5'>";
                        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                        while ($row = $desc->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['Field'] . "</td>";
                            echo "<td>" . $row['Type'] . "</td>";
                            echo "<td>" . $row['Null'] . "</td>";
                            echo "<td>" . $row['Key'] . "</td>";
                            echo "<td>" . $row['Default'] . "</td>";
                            echo "<td>" . $row['Extra'] . "</td>";
                            echo "</tr>";
                        }
                        echo "</table></details>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Table '$table' is missing!</p>";
                }
            }
            
        } else {
            echo "<p style='color: red;'>❌ Database class failed to connect</p>";
        }
    }
    
    // Close connection
    $connection->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

// Test database configuration
echo "<h2>Checking Configuration:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>MySQLi Available: " . (function_exists('mysqli_connect') ? '✅ Yes' : '❌ No') . "</li>";
echo "<li>PDO Available: " . (class_exists('PDO') ? '✅ Yes' : '❌ No') . "</li>";
echo "<li>Error Reporting: " . ini_get('error_reporting') . "</li>";
echo "<li>Display Errors: " . ini_get('display_errors') . "</li>";
echo "</ul>";

// Test with sample data insertion
echo "<h2>Testing Data Operations:</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test INSERT
    $testEmail = "test_" . time() . "@test.com";
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $username = "testuser";
    $password = password_hash("test123", PASSWORD_DEFAULT);
    
    if ($stmt) {
        $stmt->bind_param("sss", $username, $testEmail, $password);
        if ($stmt->execute()) {
            $lastId = $conn->insert_id;
            echo "<p style='color: green;'>✅ Insert test successful (ID: $lastId)</p>";
            
            // Test SELECT
            $stmt2 = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
            $stmt2->bind_param("i", $lastId);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                echo "<p style='color: green;'>✅ Select test successful - Found user: " . $user['username'] . "</p>";
            }
            
            // Test DELETE (cleanup)
            $stmt3 = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt3->bind_param("i", $lastId);
            $stmt3->execute();
            echo "<p style='color: green;'>✅ Cleanup test successful</p>";
            
        } else {
            echo "<p style='color: orange;'>⚠️ Insert test failed: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Prepare statement failed: " . $conn->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Data operation test failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Fixes:</h2>";
echo "<ol>";
echo "<li>Check if MySQL service is running</li>";
echo "<li>Verify database credentials in includes/database.php</li>";
echo "<li>Create database: CREATE DATABASE food_order_db;</li>";
echo "<li>Import database.sql file in phpMyAdmin</li>";
echo "<li>Check file permissions for includes/ directory</li>";
echo "</ol>";
?>