<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT id, created_at, user_email, action, details FROM audit_logs ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

if ($method === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $action = trim((string)($input['action'] ?? 'System action'));
  $details = trim((string)($input['details'] ?? ''));
  $userEmail = trim((string)($input['user_email'] ?? 'Unknown'));
  $stmt = $pdo->prepare('INSERT INTO audit_logs (created_at, user_email, action, details) VALUES (:created_at, :email, :action, :details)');
  $stmt->execute([
    ':created_at' => date('c'),
    ':email' => $userEmail,
    ':action' => $action,
    ':details' => $details
  ]);
  echo json_encode(['success' => true]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
