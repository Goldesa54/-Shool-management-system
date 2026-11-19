<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $form = trim((string)($_GET['form'] ?? ''));
  $stream = trim((string)($_GET['stream'] ?? ''));
  $sql = 'SELECT * FROM timetable';
  $params = [];
  $where = [];
  if ($form !== '') { $where[] = 'form = :form'; $params[':form'] = $form; }
  if ($stream !== '') { $where[] = 'stream = :stream'; $params[':stream'] = $stream; }
  if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
  $sql .= ' ORDER BY CASE day WHEN "Monday" THEN 1 WHEN "Tuesday" THEN 2 WHEN "Wednesday" THEN 3 WHEN "Thursday" THEN 4 WHEN "Friday" THEN 5 ELSE 6 END, id';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST') {
  $required = ['day', 'period', 'form', 'stream', 'subject'];
  foreach ($required as $field) {
    if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Missing required fields']);
      exit;
    }
  }
  $stmt = $pdo->prepare('INSERT INTO timetable (day, period, form, stream, subject, teacher, room) VALUES (:day, :period, :form, :stream, :subject, :teacher, :room)');
  $stmt->execute([
    ':day' => trim((string)$input['day']),
    ':period' => trim((string)$input['period']),
    ':form' => trim((string)$input['form']),
    ':stream' => trim((string)$input['stream']),
    ':subject' => trim((string)$input['subject']),
    ':teacher' => trim((string)($input['teacher'] ?? '')),
    ':room' => trim((string)($input['room'] ?? ''))
  ]);
  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  exit;
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing timetable ID']); exit; }
  $pdo->prepare('DELETE FROM timetable WHERE id = :id')->execute([':id' => $id]);
  echo json_encode(['success' => true]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
