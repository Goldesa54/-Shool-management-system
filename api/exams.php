<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Ranking endpoint
if ($method === 'GET' && ($_GET['action'] ?? '') === 'ranking') {
  $form = trim((string)($_GET['form'] ?? ''));
  $term = trim((string)($_GET['term'] ?? ''));
  if (!$form || !$term) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing form or term']);
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM exams WHERE form=:f AND term=:t ORDER BY average DESC');
  $stmt->execute([':f' => $form, ':t' => $term]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as &$r) {
    $r['subjects'] = json_decode($r['subjects'] ?? '[]', true);
  }
  unset($r);
  echo json_encode($rows);
  exit;
}

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM exams ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as &$r) {
    $r['subjects'] = json_decode($r['subjects'] ?? '[]', true);
  }
  unset($r);
  echo json_encode($rows);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($method === 'POST') {
  $records = isset($input[0]) ? $input : [$input];
  $ins = $pdo->prepare('INSERT INTO exams (adm,name,form,stream,term,subjects,total,average,grade) VALUES (:adm,:name,:form,:stream,:term,:subjects,:total,:average,:grade)');
  try {
    $pdo->beginTransaction();
    foreach ($records as $r) {
      $subjects = json_encode($r['subjects'] ?? []);
      $ins->execute([
        ':adm' => $r['adm'] ?? '',
        ':name' => $r['name'] ?? '',
        ':form' => $r['form'] ?? '',
        ':stream' => $r['stream'] ?? '',
        ':term' => $r['term'] ?? '',
        ':subjects' => $subjects,
        ':total' => $r['total'] ?? 0,
        ':average' => $r['average'] ?? 0,
        ':grade' => $r['grade'] ?? ''
      ]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
