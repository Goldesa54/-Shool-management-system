<?php
// Simple SQLite connection and schema init
$dir = __DIR__ . '/../data';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$path = $dir . '/database.sqlite';
$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON;');

// Create tables if they don't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT UNIQUE,
  password TEXT,
  role TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS students (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  adm TEXT UNIQUE,
  form TEXT,
  stream TEXT,
  parent TEXT,
  phone TEXT,
  status TEXT,
  email TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  subject TEXT,
  phone TEXT,
  email TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS fees (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  adm TEXT,
  amount REAL DEFAULT 0,
  balance REAL DEFAULT 0
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT,
  adm TEXT,
  name TEXT,
  status TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS exams (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  adm TEXT,
  name TEXT,
  form TEXT,
  stream TEXT,
  term TEXT,
  subjects TEXT,
  total REAL,
  average REAL,
  grade TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS communication (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT,
  type TEXT,
  target TEXT,
  message TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  created_at TEXT,
  user_email TEXT,
  action TEXT,
  details TEXT
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS timetable (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  day TEXT NOT NULL,
  period TEXT NOT NULL,
  form TEXT NOT NULL,
  stream TEXT NOT NULL,
  subject TEXT NOT NULL,
  teacher TEXT,
  room TEXT
);");

$defaults = [
  'school_name' => "USHINDI BOYS' SCHOOL",
  'academic_year' => '2026/2027',
  'current_term' => 'Term 1',
  'currency' => 'KES',
  'theme' => 'light'
];
$insertSetting = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
foreach ($defaults as $key => $value) {
  $insertSetting->execute([':key' => $key, ':value' => $value]);
}

$cols = $pdo->query("PRAGMA table_info(students)")->fetchAll(PDO::FETCH_ASSOC);
$hasEmail = false;
foreach ($cols as $col) {
  if (($col['name'] ?? '') === 'email') $hasEmail = true;
}
if (!$hasEmail) {
  $pdo->exec('ALTER TABLE students ADD COLUMN email TEXT');
}

// Seed demo users if empty
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$count = $stmt->fetchColumn();
if ($count == 0) {
  $users = [
    ['admin@school.com','admin123','Admin'],
    ['teacher@school.com','teacher123','Teacher'],
    ['student@school.com','student123','Student'],
    ['parent@school.com','parent123','Parent']
  ];
  $ins = $pdo->prepare('INSERT OR IGNORE INTO users (email,password,role) VALUES (:e,:p,:r)');
  foreach ($users as $u) {
    $hash = password_hash($u[1], PASSWORD_DEFAULT);
    $ins->execute([':e'=>$u[0],':p'=>$hash,':r'=>$u[2]]);
  }
}

$studentCheck = $pdo->prepare('SELECT id FROM students WHERE LOWER(email)=LOWER(:email) LIMIT 1');
$studentCheck->execute([':email' => 'student@school.com']);
if (!$studentCheck->fetch()) {
  $pdo->exec("INSERT OR IGNORE INTO students (name, adm, form, stream, parent, phone, status, email) VALUES ('Student User', 'STU-1001', 'Form 4', 'East', 'Parent User', '0712345678', 'Active', 'student@school.com')");
  $pdo->exec("INSERT OR IGNORE INTO fees (name, adm, amount, balance) VALUES ('Student User', 'STU-1001', 45000, 0)");
}

return $pdo;
