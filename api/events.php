<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once "../config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['type']) && $_GET['type'] === 'notifications') {
                // Fetch notifications
                $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
                $stmt->execute();
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($notifications);
            } else {
                // Fetch events (existing code)
                $stmt = $db->prepare("SELECT * FROM events");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($events);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            if (!$data || !isset($data->title) || !isset($data->date)) {
                throw new Exception("Invalid input data");
            }
            
            $stmt = $db->prepare("INSERT INTO events (title, date) VALUES (:title, :date)");
            $stmt->bindParam(":title", $data->title);
            $stmt->bindParam(":date", $data->date);
            
            if ($stmt->execute()) {
                $response = [
                    "id" => $db->lastInsertId(),
                    "title" => $data->title,
                    "date" => $data->date
                ];
                
                // Add notification
                $notificationStmt = $db->prepare("INSERT INTO notifications (message, type) VALUES (:message, :type)");
                $message = "New event added: " . $data->title;
                $type = "add";
                $notificationStmt->bindParam(":message", $message);
                $notificationStmt->bindParam(":type", $type);
                $notificationStmt->execute();
                
                echo json_encode($response);
            } else {
                throw new Exception("Failed to create event");
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents("php://input"));
            if (!$data || !isset($data->id) || !isset($data->title) || !isset($data->date)) {
                throw new Exception("Invalid input data");
            }

            $stmt = $db->prepare("UPDATE events SET title = :title, date = :date WHERE id = :id");
            $stmt->bindParam(":title", $data->title);
            $stmt->bindParam(":date", $data->date);
            $stmt->bindParam(":id", $data->id);
            
            if ($stmt->execute()) {
                // Add notification
                $notificationStmt = $db->prepare("INSERT INTO notifications (message, type) VALUES (:message, :type)");
                $message = "Event updated: " . $data->title;
                $type = "edit";
                $notificationStmt->bindParam(":message", $message);
                $notificationStmt->bindParam(":type", $type);
                $notificationStmt->execute();
                
                echo json_encode(["message" => "Event updated successfully"]);
            } else {
                throw new Exception("Failed to update event");
            }
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                throw new Exception("No ID provided");
            }

            // Get the event title before deleting
            $getEventStmt = $db->prepare("SELECT title FROM events WHERE id = :id");
            $getEventStmt->bindParam(":id", $id);
            $getEventStmt->execute();
            $event = $getEventStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("DELETE FROM events WHERE id = :id");
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                // Add notification
                $notificationStmt = $db->prepare("INSERT INTO notifications (message, type) VALUES (:message, :type)");
                $message = "Event deleted: " . $event['title'];
                $type = "delete";
                $notificationStmt->bindParam(":message", $message);
                $notificationStmt->bindParam(":type", $type);
                $notificationStmt->execute();
                
                echo json_encode(["message" => "Event deleted successfully"]);
            } else {
                throw new Exception("Failed to delete event");
            }
            break;

        default:
            throw new Exception("Invalid request method");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error occurred"]);
}
?>

