<?php
session_start();
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');

function normalizeRole(?string $role): string {
  return strtolower(trim((string)($role ?? '')));
}

function findUserByEmailAndRole(PDO $pdo, string $email, string $role = ''): ?array {
  if ($role !== '') {
    $stmt = $pdo->prepare('SELECT id, role, password FROM users WHERE LOWER(email)=LOWER(:e) AND LOWER(role)=LOWER(:r) LIMIT 1');
    $stmt->execute([':e' => $email, ':r' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
  }

  $stmt = $pdo->prepare('SELECT id, role, password FROM users WHERE LOWER(email)=LOWER(:e) LIMIT 1');
  $stmt->execute([':e' => $email]);
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function validateStoredPassword(PDO $pdo, array $user, string $password): bool {
  $stored = (string)($user['password'] ?? '');

  if ($stored !== '' && password_verify($password, $stored)) {
    return true;
  }

  if ($stored !== '' && hash_equals($stored, $password)) {
    $pdo->prepare('UPDATE users SET password = :hash WHERE id = :id')
      ->execute([
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $user['id']
      ]);
    return true;
  }

  return false;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST' && $action === 'signup') {
  $email = trim((string)($input['email'] ?? ''));
  $password = (string)($input['password'] ?? '');
  $role = trim((string)($input['role'] ?? 'Admin'));

  $allowedRoles = ['Admin', 'Teacher', 'Student', 'Parent'];
  if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
  }

  if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
  }

  try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email,password,role) VALUES (:e,:p,:r)');
    $stmt->execute([':e' => $email, ':p' => $hash, ':r' => $role]);
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'POST' && $action === 'login') {
  $email = trim((string)($input['email'] ?? ''));
  $password = (string)($input['password'] ?? '');
  $role = trim((string)($input['role'] ?? ''));

  if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
  }

  $user = findUserByEmailAndRole($pdo, $email, $role);

  if ($user && validateStoredPassword($pdo, $user, $password)) {
    $normalizedRole = $user['role'] ?? $role;
    $_SESSION['auth_user'] = ['email' => $email, 'role' => $normalizedRole];
    echo json_encode(['success' => true, 'role' => $normalizedRole]);
    exit;
  }

  http_response_code(401);
  echo json_encode(['error' => 'Invalid credentials']);
  exit;
}

if ($method === 'POST' && $action === 'change_password') {
  $email = trim((string)(($_SESSION['auth_user']['email'] ?? '') ?: ''));
  if ($email === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in again']);
    exit;
  }

  $oldPassword = (string)($input['old_password'] ?? '');
  $newPassword = (string)($input['new_password'] ?? '');
  if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 6 characters']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT id, password FROM users WHERE LOWER(email)=LOWER(:email) LIMIT 1');
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user || !validateStoredPassword($pdo, $user, $oldPassword)) {
    http_response_code(401);
    echo json_encode(['error' => 'Current password is incorrect']);
    exit;
  }

  $pdo->prepare('UPDATE users SET password = :password WHERE id = :id')->execute([
    ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
    ':id' => $user['id']
  ]);
  echo json_encode(['success' => true]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $users = $pdo->query('SELECT id,email,role FROM users')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($users);
  exit;
}

http_response_code(404);
echo json_encode(['error' => 'Unsupported action']);
