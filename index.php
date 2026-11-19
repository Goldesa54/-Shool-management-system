<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>USHINDI BOYS' SCHOOL ERP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="styles.css">
  <!-- backend now handled via PHP APIs -->
</head>
<body>

<!-- ===================== AUTH SCREEN ===================== -->
<div class="auth-screen" id="authScreen">
  <div class="auth-container">
    <h1 class="auth-logo">USHINDI BOYS' SCHOOL</h1>
    <p class="auth-sub">Modern School Management System</p>

    <div class="demo-hint" style="display: none;">
      <strong>Demo Login Credentials</strong><br>
      Admin &nbsp;&nbsp;&rarr; admin@school.com &nbsp;/ admin123<br>
      Teacher &rarr; teacher@school.com / teacher123<br>
      Student &rarr; student@school.com / student123<br>
      Parent &nbsp;&nbsp;&rarr; parent@school.com &nbsp;/ parent123
    </div>

    <div class="roles">
      <div class="role-card" onclick="openLogin('Admin')">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin">
        <h2>Admin</h2>
        <p>Full System Access</p>
      </div>
      <div class="role-card" onclick="openLogin('Teacher')">
        <img src="https://cdn-icons-png.flaticon.com/512/1995/1995574.png" alt="Teacher">
        <h2>Teacher</h2>
        <p>Teacher Portal</p>
      </div>
      <div class="role-card" onclick="openLogin('Student')">
        <img src="https://cdn-icons-png.flaticon.com/512/2784/2784445.png" alt="Student">
        <h2>Student</h2>
        <p>Student Portal</p>
      </div>
      <div class="role-card" onclick="openLogin('Parent')">
        <img src="https://cdn-icons-png.flaticon.com/512/921/921347.png" alt="Parent">
        <h2>Parent</h2>
        <p>Parent Portal</p>
      </div>
    </div>
  </div>
</div>

<!-- ===================== LOGIN MODAL ===================== -->
<div class="login-modal" id="loginModal">
  <div class="login-box">
    <div class="close-btn" onclick="closeLogin()">&times;</div>
    <h2 id="loginTitle">Login</h2>
    <div class="error-message" id="errorMessage"></div>

    <!-- Login Form -->
    <div id="loginForm">
      <input type="email" id="loginEmail" placeholder="Email address" autocomplete="email">
      <input type="password" id="loginPassword" placeholder="Password" autocomplete="current-password">
      <button onclick="loginUser()">Sign In</button>
      <div class="switch-auth">Don't have an account? <span onclick="showSignup()">Create Account</span></div>
    </div>

    <!-- Signup Form -->
    <div id="signupForm" class="hidden">
      <input type="email" id="signupEmail" placeholder="Create email address" autocomplete="email">
      <input type="password" id="signupPassword" placeholder="Create password (min 6 chars)" autocomplete="new-password">
      <button onclick="signupUser()">Create Account</button>
      <div class="switch-auth">Already have an account? <span onclick="showLoginForm()">Sign In</span></div>
    </div>
  </div>
</div>

<!-- ===================== ERP SYSTEM ===================== -->
<div class="erp-system" id="erpSystem">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <img src="images/logo.jpeg" alt="School Logo" onerror="this.style.display='none'">
    </div>
    <div class="logo-text">USHINDI BOYS</div>
    <ul class="menu">
      <!-- Populated dynamically by applyRoleAccess() -->
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div id="pageTitle">Dashboard</div>
      <div class="profile">
        <button id="themeToggle" class="icon-btn" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">&#9790;</button>
        <span>&#128100;</span>
        <span id="profileRole">Admin</span>
      </div>
    </header>

    <!-- ── DASHBOARD PAGE ── -->
    <div id="dashboard" class="page active-page">
      <div class="cards">
        <div class="card">
          <h3>Total Students</h3>
          <span id="totalStudents">0</span>
        </div>
        <div class="card">
          <h3>Total Teachers</h3>
          <span id="totalTeachers">0</span>
        </div>
        <div class="card">
          <h3>Fees Collected</h3>
          <span id="feeCollected">KES 0</span>
        </div>
        <div class="card">
          <h3>Attendance Rate</h3>
          <span id="attendanceRate">0%</span>
        </div>
      </div>

      <div class="dashboard-grid">
        <!-- Performance Chart -->
        <div class="chart-box">
          <div class="chart-header">
            <h3>Academic Performance</h3>
            <button class="btn" onclick="refreshPerformanceChart()">&#8635; Refresh</button>
          </div>
          <canvas id="chart"></canvas>
        </div>

        <!-- Right Widgets -->
        <div class="right-widgets">
          <!-- Calendar -->
          <div class="mini-widget calendar-widget">
            <div class="calendar-header">
              <div class="calendar-title">
                <h2 id="monthYear"></h2>
                <p>School Calendar</p>
              </div>
              <div class="calendar-nav">
                <button id="prevBtn" aria-label="Previous month">&#8249;</button>
                <button id="nextBtn" aria-label="Next month">&#8250;</button>
              </div>
            </div>
            <div class="calendar-weekdays">
              <div>S</div><div>M</div><div>T</div><div>W</div>
              <div>T</div><div>F</div><div>S</div>
            </div>
            <div class="calendar-days" id="calendarDays"></div>
          </div>

          <!-- Students Analytics -->
          <div class="mini-widget students-analytics">
            <div class="analytics-header">
              <h2>Enrolment by Form</h2>
              <span>2024/25</span>
            </div>
            <div class="analytics-stats">
              <div class="analytics-box"><h3>Form 1</h3><p id="form1Count">0</p></div>
              <div class="analytics-box"><h3>Form 2</h3><p id="form2Count">0</p></div>
              <div class="analytics-box"><h3>Form 3</h3><p id="form3Count">0</p></div>
              <div class="analytics-box"><h3>Form 4</h3><p id="form4Count">0</p></div>
            </div>
            <canvas id="studentsChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <!-- ── END DASHBOARD ── -->

    <!-- ── STUDENTS PAGE ── -->
    <div id="students" class="page">
      <div class="section-header">
        <h2>Students</h2>
        <button class="btn" onclick="openAdmissions()">+ New Student</button>
      </div>
      <div class="student-toolbar">
        <input type="text" placeholder="&#128269; Search student..." oninput="filterStudents(this.value)">
      </div>
      <div class="student-table">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Name</th><th>Adm No</th><th>Form</th>
              <th>Stream</th><th>Parent</th><th>Phone</th><th>Status</th>
            </tr>
          </thead>
          <tbody id="studentsTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── ADMISSIONS PAGE ── -->
    <div id="admissions" class="page">
      <div class="section-header">
        <h2>Student Admissions</h2>
      </div>
      <div class="glass">
        <h3 style="margin-bottom:18px">Register New Student</h3>
        <div class="form-grid">
          <input id="studentName"      placeholder="Student Full Name">
          <input id="admNo"            placeholder="Admission Number">
          <input id="studentFormLevel" placeholder="Form / Grade (e.g. Form 1)">
          <input id="studentStream"    placeholder="Stream (e.g. East)">
          <input id="parentName"       placeholder="Parent / Guardian Name">
          <input id="parentPhone"      placeholder="Parent Phone Number">
        </div>
        <button class="btn" onclick="registerStudent()">&#10003; Register Student</button>
      </div>
    </div>

    <!-- ── TEACHERS PAGE ── -->
    <div id="teachers" class="page">
      <div class="section-header">
        <h2>Teachers</h2>
        <div>
          Count: <strong id="teacherCount">0</strong> &nbsp;|&nbsp;
          Active: <strong id="activeTeachers">0</strong>
        </div>
      </div>

      <!-- Register Teacher Form -->
      <div class="glass">
        <h3 style="margin-bottom:16px">Register New Teacher</h3>
        <div class="form-grid">
          <input id="tName"    placeholder="Full Name">
          <input id="tSubject" placeholder="Subject(s)">
          <input id="tPhone"   placeholder="Phone Number">
          <input id="tEmail"   placeholder="Email Address" type="email">
        </div>
        <button class="btn" onclick="registerTeacher()">&#10003; Register Teacher</button>
      </div>

      <!-- Teachers List -->
      <div class="student-table">
        <table>
          <thead>
            <tr><th>#</th><th>Name</th><th>Subject</th><th>Phone</th><th>Status</th></tr>
          </thead>
          <tbody id="teachersTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── FINANCE PAGE ── -->
    <div id="finance" class="page">
      <div class="section-header">
        <h2>Finance &amp; Fees</h2>
        <button class="btn" onclick="downloadFinancePDF()">&#128196; Download PDF</button>
        <button class="btn" onclick="downloadFinanceExcel()">&#128190; Download Excel</button>
      </div>
      <div class="student-table">
        <table>
          <thead>
            <tr><th>#</th><th>Student Name</th><th>Balance</th><th>Status</th></tr>
          </thead>
          <tbody id="financeTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── ATTENDANCE PAGE ── -->
    <div id="attendance" class="page">
      <div class="attendance-shell">
        <div class="section-header attendance-header">
          <h2>Attendance</h2>
          <div class="attendance-actions">
            <button class="btn" onclick="saveAttendance()">&#10003; Save Attendance</button>
            <button class="btn" onclick="downloadAttendancePDF()">&#128196; Download PDF</button>
            <button class="btn" onclick="downloadAttendanceExcel()">&#128190; Download Excel</button>
          </div>
        </div>

        <div class="attendance-summary">
          <div class="attendance-stat present">
            <span class="stat-label">Present</span>
            <strong id="attendancePresent">0</strong>
          </div>
          <div class="attendance-stat absent">
            <span class="stat-label">Absent</span>
            <strong id="attendanceAbsent">0</strong>
          </div>
          <div class="attendance-stat late">
            <span class="stat-label">Late</span>
            <strong id="attendanceLate">0</strong>
          </div>
          <div class="attendance-stat rate">
            <span class="stat-label">Rate</span>
            <strong id="attendanceSummaryRate">0%</strong>
          </div>
        </div>

        <div class="glass attendance-panel">
          <div class="attendance-filters">
            <div class="attendance-field">
              <label for="attendanceDate">Date</label>
              <input id="attendanceDate" type="date" value="">
            </div>
            <div class="attendance-field">
              <label for="attendanceFormSelect">Form</label>
              <select id="attendanceFormSelect">
                <option value="Form 1">Form 1</option>
                <option value="Form 2">Form 2</option>
                <option value="Form 3">Form 3</option>
                <option value="Form 4">Form 4</option>
              </select>
            </div>
            <div class="attendance-field">
              <label for="attendanceStreamSelect">Stream</label>
              <select id="attendanceStreamSelect">
                <option value="East">East</option>
                <option value="West">West</option>
                <option value="North">North</option>
                <option value="South">South</option>
              </select>
            </div>
            <div class="attendance-field">
              <label for="attendanceSearch">Search</label>
              <input id="attendanceSearch" type="text" placeholder="Search student...">
            </div>
          </div>

          <div class="attendance-calendar-and-legend">
            <div class="attendance-calendar-card">
              <div class="calendar-controls">
                <button id="prevBtn2" onclick="prevMonth()" aria-label="Previous month">&#9664;</button>
                <span id="monthYear2"></span>
                <button id="nextBtn2" onclick="nextMonth()" aria-label="Next month">&#9654;</button>
              </div>
              <div class="calendar-weekdays">
                <div>S</div><div>M</div><div>T</div><div>W</div>
                <div>T</div><div>F</div><div>S</div>
              </div>
              <div id="calendarDays2" class="calendar-days"></div>
            </div>

            <div class="attendance-legend">
              <h3>Attendance Status</h3>
              <div class="attendance-legend-item"><span class="legend-dot present-dot"></span> Present</div>
              <div class="attendance-legend-item"><span class="legend-dot absent-dot"></span> Absent</div>
              <div class="attendance-legend-item"><span class="legend-dot late-dot"></span> Late</div>
            </div>
          </div>
        </div>

        <div class="glass attendance-table-panel">
          <div class="attendance-table-header">
            <h3>Student Register</h3>
            <span id="attendanceCount">0 students</span>
          </div>
          <div class="student-table">
            <table>
              <thead>
                <tr><th>Name</th><th>Adm No</th><th>Form</th><th>Stream</th><th>Status</th></tr>
              </thead>
              <tbody id="attendanceTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- ── END ATTENDANCE ── -->

    <!-- ── EXAMS PAGE ── -->
    <div id="exams" class="page">
      <div class="section-header">
        <h2>Exams &amp; Results</h2>
      </div>

      <!-- Controls -->
      <div class="glass">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div>
            <label style="font-size:12px;opacity:.7;display:block;margin-bottom:5px">Form</label>
            <select id="examFormSelect">
              <option value="Form 1">Form 1</option>
              <option value="Form 2">Form 2</option>
              <option value="Form 3">Form 3</option>
              <option value="Form 4">Form 4</option>
            </select>
          </div>
          <div>
            <label style="font-size:12px;opacity:.7;display:block;margin-bottom:5px">Stream</label>
            <select id="examStreamSelect">
              <option value="East">East</option>
              <option value="West">West</option>
              <option value="North">North</option>
              <option value="South">South</option>
            </select>
          </div>
          <div>
            <label style="font-size:12px;opacity:.7;display:block;margin-bottom:5px">Term</label>
            <select id="examTermSelect">
              <option value="Term 1">Term 1</option>
              <option value="Term 2">Term 2</option>
              <option value="Term 3">Term 3</option>
            </select>
          </div>
          <button class="btn" onclick="loadExamStudents()">Load Students</button>
          <button class="btn" onclick="saveExamResults()">&#10003; Save Results</button>
          <button class="btn" onclick="generateClassReport()">&#128202; Class Report</button>
          <button class="btn" onclick="downloadClassPDF()">&#128196; Download PDF</button>
          <button class="btn" onclick="downloadClassExcel()">&#128190; Download Excel</button>
        </div>
      </div>

      <!-- Enter Marks Table -->
      <div class="exam-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Name</th><th>Adm</th>
              <th>Math</th><th>Eng</th><th>Kisw</th><th>Bio</th>
              <th>Chem</th><th>Phy</th><th>Hist</th><th>Geo</th>
              <th>CRE</th><th>Agri</th><th>Bus</th><th>Comp</th>
            </tr>
          </thead>
          <tbody id="examStudentsTable">
            <tr><td colspan="14" style="text-align:center;opacity:.6;padding:24px">Select form, stream &amp; term, then click "Load Students"</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Class Ranking -->
      <div class="glass" style="margin-top:22px">
        <div class="section-header">
          <h3>Class Ranking</h3>
        </div>
        <div class="cards" style="margin-bottom:14px">
          <div class="card"><h3>Candidates</h3><span id="examCandidates">—</span></div>
          <div class="card"><h3>Class Average</h3><span id="examAverage">—</span></div>
          <div class="card"><h3>Top Student</h3><span id="topStudent">—</span></div>
          <div class="card"><h3>Best Grade</h3><span id="bestGrade">—</span></div>
        </div>
        <table>
          <thead>
            <tr><th>Pos</th><th>Name</th><th>Adm</th><th>Total</th><th>Average</th><th>Grade</th><th>Report</th></tr>
          </thead>
          <tbody id="rankingTable">
            <tr><td colspan="7" style="text-align:center;opacity:.6;padding:20px">Generate a class report to see ranking.</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Individual Report Output -->
      <div id="reportResult" style="margin-top:18px"></div>
    </div>
    <!-- ── END EXAMS ── -->

    <!-- ── TIMETABLE PAGE ── -->
    <div id="timetable" class="page">
      <div class="section-header"><h2>Timetable</h2></div>
      <div class="glass">
        <h3>Add Timetable Entry</h3>
        <div class="form-grid">
          <select id="ttDay"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option></select>
          <select id="ttPeriod"><option>Period 1</option><option>Period 2</option><option>Period 3</option><option>Period 4</option><option>Period 5</option><option>Period 6</option><option>Period 7</option><option>Period 8</option></select>
          <select id="ttForm"><option>Form 1</option><option>Form 2</option><option>Form 3</option><option>Form 4</option></select>
          <select id="ttStream"><option>East</option><option>West</option><option>North</option><option>South</option></select>
          <input id="ttSubject" placeholder="Subject">
          <input id="ttTeacher" placeholder="Teacher">
          <input id="ttRoom" placeholder="Room">
        </div>
        <button class="btn" type="button" onclick="addTimetableEntry()">Add Entry</button>
      </div>

      <div class="glass" style="margin-top:22px">
        <div class="section-header">
          <h3>Weekly Schedule</h3>
          <div style="display:flex;gap:8px">
            <select id="ttFilterForm"><option value="">All Forms</option><option>Form 1</option><option>Form 2</option><option>Form 3</option><option>Form 4</option></select>
            <select id="ttFilterStream"><option value="">All Streams</option><option>East</option><option>West</option><option>North</option><option>South</option></select>
            <button class="btn" type="button" onclick="loadTimetable()">Filter</button>
          </div>
        </div>
        <div class="student-table"><table><thead><tr><th>Day</th><th>Period</th><th>Form</th><th>Stream</th><th>Subject</th><th>Teacher</th><th>Room</th><th>Action</th></tr></thead><tbody id="timetableTableBody"><tr><td colspan="8">No timetable entries loaded.</td></tr></tbody></table></div>
      </div>
    </div>
    <!-- ── END TIMETABLE ── -->

    <!-- ── SETTINGS PAGE ── -->
    <div id="settings" class="page">
      <div class="section-header"><h2>System Settings</h2></div>
      <div class="glass">
        <h3>School Profile</h3>
        <div class="form-grid">
          <input id="settingSchoolName" placeholder="School name">
          <input id="settingAcademicYear" placeholder="Academic year (e.g. 2026/2027)">
          <select id="settingTerm"><option value="Term 1">Term 1</option><option value="Term 2">Term 2</option><option value="Term 3">Term 3</option></select>
          <select id="settingCurrency"><option value="KES">KES - Kenyan Shilling</option><option value="USD">USD - US Dollar</option><option value="EUR">EUR - Euro</option></select>
        </div>
        <button class="btn" type="button" onclick="saveSystemSettings()">Save Settings</button>
      </div>
      <div class="cards" style="margin-top:22px">
        <div class="card"><h3>Appearance</h3><button class="btn" type="button" onclick="toggleTheme()">Switch Light / Dark</button></div>
        <div class="card"><h3>Database Backup</h3><button class="btn" type="button" onclick="downloadDatabaseBackup()">Download SQLite Backup</button></div>
        <div class="card">
          <h3>Change Password</h3>
          <input id="oldPassword" type="password" placeholder="Current password" autocomplete="current-password">
          <input id="newPassword" type="password" placeholder="New password (min 6 chars)" autocomplete="new-password" style="margin-top:8px">
          <button class="btn" type="button" onclick="changePassword()" style="margin-top:8px">Update Password</button>
        </div>
      </div>
      <div class="glass" style="margin-top:22px">
        <div class="section-header"><h3>Recent Activity</h3><button class="btn" type="button" onclick="loadAuditLog()">Refresh</button></div>
        <div class="student-table"><table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody id="auditTableBody"><tr><td colspan="4">No activity loaded.</td></tr></tbody></table></div>
      </div>
    </div>
    <!-- ── END SETTINGS ── -->

    <!-- ── COMMUNICATION PAGE ── -->
    <div id="communication" class="page">
      <div class="section-header">
        <h2>Communication</h2>
      </div>

      <!-- Stats -->
      <div class="comm-stats">
        <div class="comm-stat-box">
          <div class="comm-icon">&#128244;</div>
          <h3>SMS</h3>
          <p id="smsCount">0</p>
        </div>
        <div class="comm-stat-box">
          <div class="comm-icon">&#128140;</div>
          <h3>Email</h3>
          <p id="emailCount">0</p>
        </div>
        <div class="comm-stat-box">
          <div class="comm-icon">&#128226;</div>
          <h3>Parent Alerts</h3>
          <p id="parentAlertCount">0</p>
        </div>
        <div class="comm-stat-box">
          <div class="comm-icon">&#128227;</div>
          <h3>Announcements</h3>
          <p id="announcementCount">0</p>
        </div>
      </div>

      <!-- Compose -->
      <div class="glass">
        <h3 style="margin-bottom:14px">Compose Message</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
          <select id="messageType" style="flex:1;min-width:160px">
            <option value="SMS">SMS</option>
            <option value="Email">Email</option>
            <option value="Parent Alert">Parent Alert</option>
            <option value="Announcement">Announcement</option>
          </select>
          <select id="messageTarget" style="flex:1;min-width:160px">
            <option value="All Students">All Students</option>
            <option value="All Teachers">All Teachers</option>
            <option value="All Parents">All Parents</option>
            <option value="Form 1">Form 1</option>
            <option value="Form 2">Form 2</option>
            <option value="Form 3">Form 3</option>
            <option value="Form 4">Form 4</option>
          </select>
        </div>
        <textarea id="messageContent" placeholder="Type your message here..."></textarea>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
          <button class="btn" onclick="sendMessage()">&#128228; Send Message</button>
          <button class="btn" style="background:rgba(255,255,255,.1)" onclick="clearMessages()">&#128465; Clear History</button>
        </div>
      </div>

      <!-- Quick Notices -->
      <div class="glass">
        <h3 style="margin-bottom:14px">Quick Notices</h3>
        <div class="quick-actions">
          <div class="quick-box" onclick="sendQuickNotice('Fee Reminder')">
            <div class="quick-icon">&#128176;</div>
            <h3>Fee Reminder</h3>
            <p>Notify parents about outstanding fees</p>
          </div>
          <div class="quick-box" onclick="sendQuickNotice('Exam Notice')">
            <div class="quick-icon">&#128221;</div>
            <h3>Exam Notice</h3>
            <p>Send upcoming exam timetable</p>
          </div>
          <div class="quick-box" onclick="sendQuickNotice('Event Notice')">
            <div class="quick-icon">&#128197;</div>
            <h3>Event Notice</h3>
            <p>Announce school events &amp; activities</p>
          </div>
          <div class="quick-box" onclick="sendQuickNotice('Emergency Alert')">
            <div class="quick-icon">&#128680;</div>
            <h3>Emergency Alert</h3>
            <p>Urgent broadcast to all stakeholders</p>
          </div>
        </div>
      </div>

      <!-- Message History -->
      <div class="glass">
        <div class="section-header" style="margin-bottom:12px">
          <h3>Message History</h3>
          <input type="text" id="searchCommunication" placeholder="&#128269; Search..."
            oninput="searchCommunication()"
            style="background:rgba(255,255,255,.08);border:none;outline:none;border-radius:10px;padding:8px 14px;color:white;font-family:Poppins,sans-serif">
        </div>
        <table>
          <thead>
            <tr><th>Date</th><th>Type</th><th>Target</th><th>Message</th></tr>
          </thead>
          <tbody id="communicationTableBody"></tbody>
        </table>
      </div>
    </div>
    <!-- ── END COMMUNICATION ── -->
  </div><!-- .main-content -->
</div><!-- .erp-system -->

<script>
  /* ── Attendance page calendar (separate from dashboard calendar) ── */
  let attDate = new Date();

  function renderAttCal() {
    const monthYear2  = document.getElementById("monthYear2");
    const calDays2    = document.getElementById("calendarDays2");
    if (!monthYear2 || !calDays2) return;

    const year  = attDate.getFullYear();
    const month = attDate.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    monthYear2.textContent = attDate.toLocaleString("default", { month:"long", year:"numeric" });
    calDays2.innerHTML = "";

    for (let i = 0; i < firstDay; i++) calDays2.innerHTML += "<div></div>";

    const today = new Date();
    for (let day = 1; day <= lastDate; day++) {
      const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      calDays2.innerHTML += `<div class="${isToday ? "today" : ""}">${day}</div>`;
    }
  }

  function prevMonth() {
    attDate.setMonth(attDate.getMonth() - 1);
    renderAttCal();
  }

  function nextMonth() {
    attDate.setMonth(attDate.getMonth() + 1);
    renderAttCal();
  }

  document.addEventListener("DOMContentLoaded", renderAttCal);

  /* ── Student search filter ── */
  function filterStudents(query) {
    const q = query.toLowerCase();
    const rows = document.querySelectorAll("#studentsTableBody tr");
    rows.forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? "" : "none";
    });
  }
</script>

<script src="main.js"></script>
</body>
</html>
