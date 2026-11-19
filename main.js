/* ================================================
   USHINDI BOYS SCHOOL ERP — main.js  (FIXED)
   ================================================ */

/* ---------- STORAGE (server-backed) ---------- */
let users = [];
let students = [];
let teachers = [];
let feeRecords = [];
let attendanceRecords = [];
let examRecords = [];
let communicationRecords = [];

async function apiFetch(path, options = {}) {
  let base;
  if (window.location.protocol === 'file:') {
    base = 'http://localhost:8000/';
  } else {
    const directory = window.location.pathname.replace(/\/[^/]*$/, '/');
    base = new URL(directory, window.location.origin).toString();
  }

  const url = new URL('api/' + path, base).toString();
  const res = await fetch(url, options);

  if (!res.ok) {
    const txt = await res.text();
    throw new Error(txt || res.statusText);
  }

  const contentType = res.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    return res.json();
  }
  return res.text();
}

let currentRole = "Admin";
let currentUser = null;

/* ---------- DOM REFERENCES ---------- */
const authScreen    = document.getElementById("authScreen");
const loginModal    = document.getElementById("loginModal");
const erpSystem     = document.getElementById("erpSystem");
const loginTitle    = document.getElementById("loginTitle");
const profileRole   = document.getElementById("profileRole");
const pageTitle     = document.getElementById("pageTitle");
const loginEmail    = document.getElementById("loginEmail");
const loginPassword = document.getElementById("loginPassword");
const signupEmail   = document.getElementById("signupEmail");
const signupPassword = document.getElementById("signupPassword");
const errorMessage  = document.getElementById("errorMessage");
const pages         = document.querySelectorAll(".page");

/* ---------- HELPERS ---------- */
function saveUsers() {
  // Not used for server-backed mode; users are persisted via API
}

async function loadAllData() {
  try {
    const [studentData, teacherData, feeData, attendanceData, examData, communicationData] = await Promise.all([
      apiFetch('students.php'),
      apiFetch('teachers.php'),
      apiFetch('finance.php'),
      apiFetch('attendance.php'),
      apiFetch('exams.php'),
      apiFetch('communication.php')
    ]);

    students = Array.isArray(studentData) ? studentData : [];
    teachers = Array.isArray(teacherData) ? teacherData : [];
    feeRecords = Array.isArray(feeData) ? feeData : [];
    attendanceRecords = Array.isArray(attendanceData) ? attendanceData : [];
    examRecords = Array.isArray(examData) ? examData : [];
    communicationRecords = Array.isArray(communicationData) ? communicationData : [];

    dedupeStudents();
    renderStudents();
    renderTeachers();
    renderFinance();
    updateDashboard();
    updateEnrollmentByForm();
    renderCommunication();
    renderAttendanceTable();
    return true;
  } catch (err) {
    console.error('Failed to load app data:', err);
    return false;
  }
}

function showError(msg) {
  errorMessage.textContent = msg;
  errorMessage.style.display = "block";
}

function hideError() {
  errorMessage.style.display = "none";
}

function clearInputs() {
  loginEmail.value    = "";
  loginPassword.value = "";
  signupEmail.value   = "";
  signupPassword.value= "";
}

/* ---------- AUTH ---------- */
function openLogin(role) {
  currentRole = role;
  loginTitle.textContent = role + " Login";
  loginModal.style.display = "flex";
  showLoginForm();
  clearInputs();
  hideError();
}

function closeLogin() {
  loginModal.style.display = "none";
  clearInputs();
  hideError();
}

function showSignup() {
  document.getElementById("loginForm").style.display  = "none";
  document.getElementById("signupForm").style.display = "block";
  hideError();
}

function showLoginForm() {
  document.getElementById("signupForm").style.display = "none";
  document.getElementById("loginForm").style.display  = "block";
  hideError();
}

/* Keep old name in case anything references it */
function showLogin() { showLoginForm(); }

async function signupUser() {
  const email    = signupEmail.value.trim();
  const password = signupPassword.value.trim();

  if (!email || !password) {
    showError("Please fill in all fields.");
    return;
  }
  if (password.length < 6) {
    showError("Password must be at least 6 characters.");
    return;
  }

  try {
    await apiFetch('auth.php?action=signup', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password, role: currentRole })
    });
    alert('Account created! You can now log in.');
    showLoginForm();
  } catch (err) {
    showError(err.message || (err && err.toString()) || 'Signup failed');
  }
}

function getCurrentStudentProfile() {
  const email = currentUser && currentUser.email ? String(currentUser.email).trim().toLowerCase() : '';
  if (!email) return null;
  return students.find(s => String(s.email || '').trim().toLowerCase() === email) || null;
}

function getStudentVisibleRecords() {
  if (currentRole !== 'Student' || !currentUser || !currentUser.email) return {
    students: students,
    finance: feeRecords,
    attendance: attendanceRecords,
    exams: examRecords,
    communication: communicationRecords
  };

  const profile = getCurrentStudentProfile();
  if (!profile) return {
    students: [],
    finance: [],
    attendance: [],
    exams: [],
    communication: []
  };

  return {
    students: students.filter(s => String(s.adm || '').trim() === String(profile.adm || '').trim()),
    finance: feeRecords.filter(r => String(r.adm || '').trim() === String(profile.adm || '').trim()),
    attendance: attendanceRecords.filter(r => String(r.adm || '').trim() === String(profile.adm || '').trim()),
    exams: examRecords.filter(r => String(r.adm || '').trim() === String(profile.adm || '').trim()),
    communication: communicationRecords.filter(r => {
      const target = String(r.target || '').toLowerCase();
      return target.includes('student') || target.includes(profile.name.toLowerCase()) || target.includes(profile.adm.toLowerCase());
    })
  };
}

async function loginUser() {
  const email    = loginEmail.value.trim();
  const password = loginPassword.value.trim();

  if (!email || !password) {
    showError("Please enter your email and password.");
    return;
  }

  try {
    const roleToUse = (currentRole || 'Admin').trim();
    const resp = await apiFetch('auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password, role: roleToUse })
    });

    currentUser = { email, role: resp.role || roleToUse };
    hideError();
    authScreen.style.display = 'none';
    loginModal.style.display = 'none';
    erpSystem.style.display = 'flex';
    profileRole.textContent = resp.role || roleToUse;
    applyRoleAccess(resp.role || roleToUse);
  } catch (err) {
    const msg = (err && err.message) ? err.message : 'Invalid email or password for the selected role.';
    showError(msg);
  }
}

/* ---------- LOGOUT ---------- */
function logout() {
  currentUser = null;
  currentRole = "Admin";
  erpSystem.style.display  = "none";
  authScreen.style.display = "flex";

  pages.forEach(p => p.classList.remove("active-page"));
  document.getElementById("dashboard").classList.add("active-page");

  clearInputs();
}

/* ---------- PAGE NAVIGATION ---------- */
function openPage(pageId) {
  pages.forEach(p => p.classList.remove("active-page"));
  const target = document.getElementById(pageId);
  if (target) target.classList.add("active-page");

  pageTitle.textContent =
    pageId.charAt(0).toUpperCase() + pageId.slice(1);

  /* Lazy-load page content */
  if (pageId === "students")     renderStudents();
  if (pageId === "teachers")     renderTeachers();
  if (pageId === "finance")      renderFinance();
  if (pageId === "attendance")   loadAttendanceStudents();
  if (pageId === "timetable")    loadTimetable();
  if (pageId === "communication") renderCommunication();
  if (pageId === "settings") {
    loadSystemSettings();
    loadAuditLog();
  }
}

function setupMenuNavigation() {
  document.querySelectorAll(".menu li[data-page]").forEach(item => {
    item.onclick = () => {
      document.querySelectorAll(".menu li[data-page]")
        .forEach(li => li.classList.remove("active"));
      item.classList.add("active");
      openPage(item.dataset.page);
    };
  });
}

/* ---------- ROLE ACCESS ---------- */
function applyRoleAccess(role) {
  const menu = document.querySelector(".menu");

  const menus = {
    Admin:   ["dashboard","students","admissions","teachers","timetable","attendance","finance","exams","communication","settings"],
    Teacher: ["dashboard","students","timetable","attendance","exams","communication"],
    Student: ["dashboard","attendance","finance","exams","communication"],
    Parent:  ["dashboard","attendance","finance","communication"]
  };

  const allowed = menus[role] || menus.Admin;
  let html = "";

  allowed.forEach(page => {
    html += `<li data-page="${page}">${page.charAt(0).toUpperCase() + page.slice(1)}</li>`;
  });

  html += `<li class="logout-btn" onclick="logout()">&#128274; Logout</li>`;
  menu.innerHTML = html;

  setupMenuNavigation();
  openPage("dashboard");
  updateDashboard();
}

/* ---------- DASHBOARD ---------- */
function updateDashboard() {
  const el = id => document.getElementById(id);

  if (currentRole === 'Student') {
    const profile = getCurrentStudentProfile();
    const visible = getStudentVisibleRecords();
    if (profile) {
      if (el("totalStudents")) el("totalStudents").textContent = 1;
      if (el("totalTeachers")) el("totalTeachers").textContent = teachers.length;
      if (el("feeCollected")) el("feeCollected").textContent = `KES ${visible.finance.reduce((sum, item) => sum + Number(item.amount || 0), 0).toLocaleString()}`;
      if (el("attendanceRate")) {
        const total = visible.attendance.length || 1;
        const present = visible.attendance.filter(r => String(r.status).toLowerCase() === 'present').length;
        el("attendanceRate").textContent = `${Math.round((present / total) * 100)}%`;
      }
      return;
    }
  }

  if (el("totalStudents")) el("totalStudents").textContent = students.length;
  if (el("totalTeachers")) el("totalTeachers").textContent = teachers.length;

  const totalFees = feeRecords.reduce((sum, item) => sum + Number(item.amount || 0), 0);
  if (el("feeCollected")) el("feeCollected").textContent = "KES " + totalFees.toLocaleString();

  updateAttendanceRate();
}

/* ---------- STUDENTS ---------- */
function renderStudents() {
  const tbody = document.getElementById("studentsTableBody");
  if (!tbody) return;

  const visible = getStudentVisibleRecords().students;

  tbody.innerHTML = visible.length === 0
    ? `<tr><td colspan="8" style="text-align:center;opacity:.6;padding:30px">No student profile available for this account.</td></tr>`
    : "";

  visible.forEach((s, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${i + 1}</td>
        <td>${s.name}</td>
        <td>${s.adm}</td>
        <td>${s.form}</td>
        <td>${s.stream}</td>
        <td>${s.parent}</td>
        <td>${s.phone}</td>
        <td><span class="status active-status">${s.status || "Active"}</span></td>
      </tr>`;
  });
}

/* ---------- REGISTER STUDENT ---------- */
async function registerStudent() {
  const val = id => document.getElementById(id)?.value.trim();
  const name   = val("studentName");
  const adm    = val("admNo");
  const form   = val("studentFormLevel");
  const stream = val("studentStream");
  const parent = val("parentName");
  const phone  = val("parentPhone");

  if (!name || !adm || !form) {
    alert("Please fill in Name, Admission No and Form.");
    return;
  }
  const duplicate = students.find(s => s.adm === adm);
  if (duplicate) { alert('Admission number already exists.'); return; }

  try {
    await apiFetch('students.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ name, adm, form, stream, parent, phone }) });
    await loadAllData();
    ["studentName","admNo","studentFormLevel","studentStream","parentName","parentPhone"].forEach(id => { const el = document.getElementById(id); if (el) el.value = ""; });
    renderStudents(); renderFinance(); updateDashboard(); updateEnrollmentByForm();
    alert('Student registered successfully!');
  } catch (err) { alert('Failed to register student: ' + err.message); }
}

/* ---------- TEACHERS ---------- */
function renderTeachers() {
  const tbody = document.getElementById("teachersTableBody");
  if (!tbody) return;

  tbody.innerHTML = teachers.length === 0
    ? `<tr><td colspan="5" style="text-align:center;opacity:.6;padding:30px">No teachers registered yet.</td></tr>`
    : "";

  teachers.forEach((t, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${i + 1}</td>
        <td>${t.name}</td>
        <td>${t.subject}</td>
        <td>${t.phone}</td>
        <td><span class="status active-status">Active</span></td>
      </tr>`;
  });

  const tc = document.getElementById("teacherCount");
  const ta = document.getElementById("activeTeachers");
  if (tc) tc.textContent = teachers.length;
  if (ta) ta.textContent = teachers.length;
}

/* ---------- REGISTER TEACHER ---------- */
async function registerTeacher() {
  const val = id => document.getElementById(id)?.value.trim();
  const name    = val("tName");
  const subject = val("tSubject");
  const phone   = val("tPhone");
  const email   = val("tEmail");

  if (!name || !subject) {
    alert("Please fill in Name and Subject.");
    return;
  }
  try {
    await apiFetch('teachers.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ name, subject, phone, email }) });
    await loadAllData();
    ["tName","tSubject","tPhone","tEmail"].forEach(id => { const el = document.getElementById(id); if (el) el.value = ""; });
    renderTeachers(); updateDashboard();
    alert('Teacher registered successfully!');
  } catch (err) { alert('Failed to register teacher: ' + err.message); }
}

/* ---------- FINANCE ---------- */
function renderFinance() {
  const tbody = document.getElementById("financeTableBody");
  if (!tbody) return;

  const visible = getStudentVisibleRecords().finance;

  tbody.innerHTML = visible.length === 0
    ? `<tr><td colspan="4" style="text-align:center;opacity:.6;padding:30px">No fee records available for this account.</td></tr>`
    : "";

  visible.forEach((r, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${i + 1}</td>
        <td>${r.name}</td>
        <td>KES ${Number(r.balance || 0).toLocaleString()}</td>
        <td><span class="status ${r.balance > 0 ? "balance-status" : "paid-status"}">
          ${r.balance > 0 ? "Balance" : "Paid"}
        </span></td>
      </tr>`;
  });
}

/* ---------- ATTENDANCE ---------- */
function getAttendanceStatusCounts(list) {
  const counts = { Present: 0, Absent: 0, Late: 0 };
  list.forEach(item => {
    if (counts[item.status]) counts[item.status]++;
  });
  return counts;
}

function renderAttendanceTable() {
  const tbody = document.getElementById("attendanceTableBody");
  if (!tbody) return;

  const visible = getStudentVisibleRecords().attendance;

  tbody.innerHTML = visible.length === 0
    ? '<tr><td colspan="5" style="text-align:center;opacity:.6;padding:30px">No attendance records for your account.</td></tr>'
    : "";

  visible.forEach((item, index) => {
    tbody.innerHTML += `
      <tr>
        <td>${index + 1}</td>
        <td>${item.date || "-"}</td>
        <td>${item.status || "Present"}</td>
        <td>${item.name || "-"}</td>
        <td>${item.adm || "-"}</td>
      </tr>`;
  });

  const countEl = document.getElementById("attendanceCount");
  if (countEl) countEl.textContent = `${visible.length} students`;

  const counts = getAttendanceStatusCounts(visible.map(s => ({
    adm: s.adm,
    status: (attendanceRecords.find(r => r.adm === s.adm && r.date === getSelectedAttendanceDate()) || {}).status || "Present"
  })));

  const presentEl = document.getElementById("attendancePresent");
  const absentEl = document.getElementById("attendanceAbsent");
  const lateEl = document.getElementById("attendanceLate");
  const rateEl = document.getElementById("attendanceSummaryRate");

  if (presentEl) presentEl.textContent = counts.Present;
  if (absentEl) absentEl.textContent = counts.Absent;
  if (lateEl) lateEl.textContent = counts.Late;
  if (rateEl) {
    const total = visible.length || 1;
    const rate = Math.round((counts.Present / total) * 100);
    rateEl.textContent = `${rate}%`;
  }
}

function getSelectedAttendanceDate() {
  const input = document.getElementById("attendanceDate");
  if (!input || !input.value) return new Date().toISOString().slice(0, 10);
  return input.value;
}

function setupAttendanceControls() {
  const form = document.getElementById("attendanceFormSelect");
  const stream = document.getElementById("attendanceStreamSelect");
  const search = document.getElementById("attendanceSearch");
  const date = document.getElementById("attendanceDate");

  if (date) date.value = new Date().toISOString().slice(0, 10);

  [form, stream, search, date].forEach(el => {
    if (el) {
      el.oninput = renderAttendanceTable;
      el.onchange = renderAttendanceTable;
    }
  });

  document.addEventListener("change", (event) => {
    if (event.target && event.target.classList && event.target.classList.contains("attendance-status-select")) {
      const adm = event.target.dataset.adm;
      const name = event.target.dataset.name;
      const dateValue = getSelectedAttendanceDate();
      const status = event.target.value;

      if (!adm) return;

      const existingIndex = attendanceRecords.findIndex(r => r.adm === adm && r.date === dateValue);
      const record = { date: dateValue, adm, name, status };

      if (existingIndex >= 0) attendanceRecords[existingIndex] = record;
      else attendanceRecords.push(record);

      renderAttendanceTable();
    }
  });
}

function loadAttendanceStudents() {
  const form = document.getElementById("attendanceFormSelect");
  const stream = document.getElementById("attendanceStreamSelect");
  if (form) form.value = "Form 1";
  if (stream) stream.value = "East";
  if (document.getElementById("attendanceDate")) {
    document.getElementById("attendanceDate").value = new Date().toISOString().slice(0, 10);
  }
  renderAttendanceTable();
}

function saveAttendance() {
  const date = getSelectedAttendanceDate();
  const records = [];

  document.querySelectorAll(".attendance-status-select").forEach(select => {
    const adm = select.dataset.adm;
    const name = select.dataset.name;
    const status = select.value;
    if (adm && status) {
      records.push({ date, adm, name, status });
    }
  });

  if (!records.length) {
    alert('No attendance to save.');
    return;
  }

  apiFetch('attendance.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ records })
  })
    .then(() => loadAllData().then(() => {
      renderAttendanceTable();
      alert('Attendance saved successfully!');
    }))
    .catch(err => alert('Failed to save attendance: ' + err.message));
}

function updateAttendanceRate() {
  const dashboardRateEl = document.getElementById("attendanceRate");
  const pageRateEl = document.getElementById("attendanceSummaryRate");

  const total = attendanceRecords.length || 0;
  const present = attendanceRecords.filter(r => r.status === "Present").length;
  const percent = total ? Math.round((present / total) * 100) : 0;

  if (dashboardRateEl) dashboardRateEl.textContent = `${percent}%`;
  if (pageRateEl) pageRateEl.textContent = `${percent}%`;
}

/* ---------- CALENDAR ---------- */
let currentDate = new Date();

function renderCalendar() {
  const monthYear  = document.getElementById("monthYear");
  const calendarDays = document.getElementById("calendarDays");
  if (!monthYear || !calendarDays) return;

  const year  = currentDate.getFullYear();
  const month = currentDate.getMonth();

  const firstDay = new Date(year, month, 1).getDay();
  const lastDate = new Date(year, month + 1, 0).getDate();

  monthYear.textContent = currentDate.toLocaleString("default", { month: "long", year: "numeric" });
  calendarDays.innerHTML = "";

  for (let i = 0; i < firstDay; i++) calendarDays.innerHTML += "<div></div>";

  const today = new Date();
  for (let day = 1; day <= lastDate; day++) {
    const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
    calendarDays.innerHTML += `<div class="${isToday ? "today" : ""}">${day}</div>`;
  }
}

/* Calendar prev/next — attached after DOM is ready */
function attachCalendarButtons() {
  const prev = document.getElementById("prevBtn");
  const next = document.getElementById("nextBtn");
  if (prev) prev.onclick = () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); };
  if (next) next.onclick = () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); };
}

/* ---------- CHART ---------- */
let performanceChart;
let studentsChart;

function initCharts() {
  const chartCanvas = document.getElementById("chart");
  if (chartCanvas && !performanceChart) {
    performanceChart = new Chart(chartCanvas, {
      type: "line",
      data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun"],
        datasets: [{
          label: "Performance",
          data: [72, 76, 80, 84, 88, 92],
          borderColor: "#60a5fa",
          backgroundColor: "rgba(96,165,250,.2)",
          fill: true,
          tension: .4
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }

  const studentsCanvas = document.getElementById("studentsChart");
  if (studentsCanvas) {
    studentsChart = new Chart(studentsCanvas, {
      type: "bar",
      data: {
        labels: ["Form 1","Form 2","Form 3","Form 4"],
        datasets: [{
          label: "Students",
          data: [0, 0, 0, 0],
          backgroundColor: ["#3b82f6","#8b5cf6","#06b6d4","#22c55e"],
          borderRadius: 10,
          borderSkipped: false,
          barThickness: 28,
          maxBarThickness: 34
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: "#111827" } },
        scales: {
          x: { grid: { display: false }, ticks: { color: "#fff" } },
          y: { beginAtZero: true, grid: { color: "rgba(255,255,255,.08)" }, ticks: { color: "#fff" } }
        }
      }
    });
  }

  updateEnrollmentByForm();
}

/* Remove duplicate students (by admission number) and persist */
function dedupeStudents() {
  // remove duplicates from the in-memory `students` array
  const seen = new Set();
  const unique = [];
  students.forEach(s => {
    const key = String(s.adm || "").trim();
    if (!key) return;
    if (!seen.has(key)) { seen.add(key); unique.push(s); }
  });
  if (unique.length !== students.length) students = unique;
}

/* Compute enrolment counts from system and update chart & boxes */
function updateEnrollmentByForm() {
  // students array is kept current from server
  const forms = ["Form 1","Form 2","Form 3","Form 4"];
  const counts = forms.map(f => students.filter(s => s.form === f).length);

  // Update numeric boxes
  const ids = ["form1Count","form2Count","form3Count","form4Count"];
  ids.forEach((id, i) => { const el = document.getElementById(id); if (el) el.textContent = counts[i]; });

  // Update chart
  if (studentsChart && studentsChart.data && studentsChart.data.datasets[0]) {
    studentsChart.data.datasets[0].data = counts;
    studentsChart.update();
  }
}

function refreshPerformanceChart() {
  if (!performanceChart) return;
  performanceChart.data.datasets[0].data = Array.from({length: 6}, () => Math.floor(Math.random() * 25) + 70);
  performanceChart.update();
}

/* ---------- EXAMS ---------- */
function getCurrentStudentAdm() {
  if (currentRole !== 'Student' || !currentUser || !currentUser.email) return null;
  const profile = getCurrentStudentProfile();
  return profile ? String(profile.adm || '').trim() : null;
}

function loadExamStudents() {
  const form   = document.getElementById("examFormSelect")?.value;
  const stream = document.getElementById("examStreamSelect")?.value;
  const tbody  = document.getElementById("examStudentsTable");
  if (!tbody) return;

  const studentAdm = getCurrentStudentAdm();
  const profile = studentAdm ? getCurrentStudentProfile() : null;

  let filtered = [];
  if (studentAdm && profile) {
    filtered = students.filter(s => String(s.adm || '').trim() === String(studentAdm));
  } else {
    filtered = students.filter(s => s.form === form && s.stream === stream);
  }

  tbody.innerHTML = filtered.length === 0
    ? `<tr><td colspan="14" style="text-align:center;opacity:.6;padding:20px">No students found for this account.</td></tr>`
    : "";

  filtered.forEach(s => {
    const adm = String(s.adm || '');
    tbody.innerHTML += `
      <tr>
        <td>${s.name}</td><td>${adm}</td>
        <td><input type="number" min="0" max="100" id="math-${adm}"></td>
        <td><input type="number" min="0" max="100" id="eng-${adm}"></td>
        <td><input type="number" min="0" max="100" id="kisw-${adm}"></td>
        <td><input type="number" min="0" max="100" id="bio-${adm}"></td>
        <td><input type="number" min="0" max="100" id="chem-${adm}"></td>
        <td><input type="number" min="0" max="100" id="phy-${adm}"></td>
        <td><input type="number" min="0" max="100" id="hist-${adm}"></td>
        <td><input type="number" min="0" max="100" id="geo-${adm}"></td>
        <td><input type="number" min="0" max="100" id="cre-${adm}"></td>
        <td><input type="number" min="0" max="100" id="agri-${adm}"></td>
        <td><input type="number" min="0" max="100" id="bus-${adm}"></td>
        <td><input type="number" min="0" max="100" id="comp-${adm}"></td>
      </tr>`;
  });
}

function saveExamResults() {
  if (currentRole === 'Student') {
    alert('Students can view their results only.');
    return;
  }

  const form   = document.getElementById("examFormSelect")?.value;
  const term   = document.getElementById("examTermSelect")?.value;
  const stream = document.getElementById("examStreamSelect")?.value;

  const subjectIds = ["math","eng","kisw","bio","chem","phy","hist","geo","cre","agri","bus","comp"];
  const subjectNames = ["math","english","kiswahili","biology","chemistry","physics","history","geography","cre","agriculture","business","computer"];

  const classStudents = students.filter(s => s.form === form && s.stream === stream);

  const payload = [];
  classStudents.forEach(s => {
    const subjects = {};
    subjectIds.forEach((id, i) => { subjects[subjectNames[i]] = Number(document.getElementById(`${id}-${s.adm}`)?.value) || 0; });
    const record = { name: s.name, adm: s.adm, form, stream, term, subjects };
    calculateResults(record);
    payload.push(record);
  });
  apiFetch('exams.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify(payload) })
    .then(() => { loadAllData().then(() => alert('Exam results saved successfully!')); })
    .catch(err => alert('Failed to save exam results: ' + err.message));
}

function generateClassRanking(form, term) {
  const formName = String(form || '').trim();
  const termName = String(term || '').trim();

  return examRecords
    .filter(r => String(r.form || '').trim() === formName && String(r.term || '').trim() === termName)
    .map(r => ({
      ...r,
      total: Number(r.total || 0),
      average: Number(r.average || 0),
      grade: r.grade || getGrade(Number(r.average || 0))
    }))
    .sort((a, b) => (Number(b.average) || 0) - (Number(a.average) || 0) || (Number(b.total) || 0) - (Number(a.total) || 0))
    .map((r, index) => ({ ...r, position: index + 1 }));
}

function generateClassReport() {
  if (currentRole === 'Student') {
    const profile = getCurrentStudentProfile();
    if (!profile) {
      alert('No student profile found for this account.');
      return;
    }
    const adm = String(profile.adm || '').trim();
    const form = String(profile.form || '').trim();
    const stream = String(profile.stream || '').trim();
    const term = document.getElementById("examTermSelect")?.value || 'Term 1';
    generateStudentReport(adm, form, stream, term);
    return;
  }

  const form    = document.getElementById("examFormSelect")?.value;
  const term    = document.getElementById("examTermSelect")?.value;
  const ranking = generateClassRanking(form, term);
  const tbody   = document.getElementById("rankingTable");
  if (!tbody) return;

  tbody.innerHTML = ranking.length === 0
    ? `<tr><td colspan="7" style="text-align:center;opacity:.6;padding:20px">No results found. Save exam results first.</td></tr>`
    : "";

  ranking.forEach(s => {
    tbody.innerHTML += `
      <tr>
        <td>${s.position}</td>
        <td>${s.name}</td>
        <td>${s.adm}</td>
        <td>${s.total}</td>
        <td>${s.average.toFixed(1)}</td>
        <td>${s.grade}</td>
        <td><button class="table-btn" onclick="generateStudentReport('${s.adm}','${form}','${document.getElementById("examStreamSelect")?.value}','${term}')">Report</button></td>
      </tr>`;
  });

  if (ranking.length) {
    const setEl = (id, val) => {
      const e = document.getElementById(id);
      if (e) e.textContent = val;
    };
    setEl("examCandidates", ranking.length);
    setEl("examAverage", (ranking.reduce((s,r) => s + r.average, 0) / ranking.length).toFixed(1) + "%");
    setEl("topStudent", ranking[0].name);
    setEl("bestGrade", ranking[0].grade);
  }
}

function getGrade(score) {
  if (score >= 80) return "A";
  if (score >= 70) return "B";
  if (score >= 60) return "C";
  if (score >= 50) return "D";
  return "E";
}

function calculateResults(record) {
  let total = 0, count = 0;
  for (let key in record.subjects) { total += Number(record.subjects[key]); count++; }
  record.total   = total;
  record.average = count ? total / count : 0;
  record.grade   = getGrade(record.average);
  return record;
}

function generateStudentReport(adm, form, stream, term) {
  /* Support call from ranking table button where params are passed directly */
  form   = form   || document.getElementById("examFormSelect")?.value;
  stream = stream || document.getElementById("examStreamSelect")?.value;
  term   = term   || document.getElementById("examTermSelect")?.value;

  const record = examRecords.find(r =>
    String(r.adm).trim() === String(adm).trim() &&
    r.form === form && r.stream === stream && r.term === term
  );

  const el = document.getElementById("reportResult");
  if (!el) return;

  if (!record) {
    el.innerHTML = `<div class="glass" style="text-align:center;padding:24px;color:#f87171">No report card found. Please save exam results first.</div>`;
    return;
  }

  el.innerHTML = `
    <div class="glass">
      <div style="text-align:center;margin-bottom:20px">
        <img src="images/logo.jpeg" style="width:80px;height:80px;margin:auto;border-radius:50%;object-fit:cover">
        <h2 style="margin-top:10px">USHINDI BOYS SCHOOL</h2>
        <p style="opacity:.7">Academic Report Card</p>
      </div>
      <hr style="border-color:rgba(255,255,255,.15);margin-bottom:16px">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:16px">
        <div><strong>Name:</strong> ${record.name}</div>
        <div><strong>Adm No:</strong> ${record.adm}</div>
        <div><strong>Form:</strong> ${record.form}</div>
        <div><strong>Stream:</strong> ${record.stream}</div>
        <div><strong>Term:</strong> ${record.term}</div>
        <div><strong>Position:</strong> ${record.position || "-"}</div>
        <div><strong>Grade:</strong> ${record.grade}</div>
      </div>
      <table>
        <thead><tr><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead>
        <tbody>
          ${Object.entries(record.subjects).map(([sub, mark]) => `
            <tr><td>${sub.charAt(0).toUpperCase()+sub.slice(1)}</td><td>${mark}</td><td>${getGrade(Number(mark))}</td></tr>
          `).join("")}
        </tbody>
      </table>
      <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px">
        <div><strong>Total: ${record.total}</strong></div>
        <div><strong>Average: ${record.average.toFixed(1)}%</strong></div>
        <div><strong>Overall Grade: ${record.grade}</strong></div>
      </div>
      <div style="display:flex;justify-content:space-between;margin-top:40px;padding-top:16px;border-top:1px solid rgba(255,255,255,.15)">
        <div>____________________<br><small>Class Teacher</small></div>
        <div>____________________<br><small>Principal</small></div>
      </div>
    </div>`;
}

/* ---------- ADMISSIONS ---------- */
function openAdmissions() {
  openPage("admissions");
  document.querySelectorAll(".menu li[data-page]").forEach(li => {
    li.classList.remove("active");
    if (li.dataset.page === "admissions") li.classList.add("active");
  });
}

/* ---------- COMMUNICATION ---------- */
function searchCommunication() {
  renderCommunication();
}

function sendQuickNotice(type) {
  const target = document.getElementById("messageTarget")?.value || "All Students";
  const message = `${type}: ${new Date().toLocaleDateString()}`;
  sendMessageOverride(target, message, type);
}

function sendMessageOverride(target, message, type = document.getElementById("messageType")?.value || "SMS") {
  const payload = { date: new Date().toLocaleString(), type, target, message };

  apiFetch('communication.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(() => {
    loadAllData().then(() => {
      renderCommunication();
      const input = document.getElementById("messageContent");
      if (input) input.value = "";
    });
  }).catch(err => alert(err.message || 'Failed to send message'));
}

function renderCommunication() {
  const tbody = document.getElementById("communicationTableBody");
  if (!tbody) return;

  const visible = getStudentVisibleRecords().communication;

  tbody.innerHTML = visible.length === 0
    ? '<tr><td colspan="4" style="text-align:center;opacity:.6;padding:30px">No messages for your account.</td></tr>'
    : "";

  visible.forEach(msg => {
    tbody.innerHTML += `
      <tr>
        <td>${msg.date || ""}</td>
        <td>${msg.type || ""}</td>
        <td>${msg.target || ""}</td>
        <td>${msg.message || ""}</td>
      </tr>`;
  });

  const counts = {
    SMS: 0,
    Email: 0,
    "Parent Alert": 0,
    Announcement: 0
  };

  communicationRecords.forEach(msg => {
    if (counts[msg.type] !== undefined) counts[msg.type]++;
  });

  const setCount = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  };

  setCount("smsCount", counts.SMS);
  setCount("emailCount", counts.Email);
  setCount("parentAlertCount", counts["Parent Alert"]);
  setCount("announcementCount", counts.Announcement);
}

function sendMessage() {
  const type    = document.getElementById("messageType")?.value;
  const target  = document.getElementById("messageTarget")?.value;
  const message = document.getElementById("messageContent")?.value.trim();

  if (!message) { alert("Please enter a message."); return; }
  sendMessageOverride(target, message, type);
}

function clearMessages() {
  if (!confirm("Delete all communication records?")) return;
  apiFetch('communication.php', { method: 'DELETE' })
    .then(() => loadAllData().then(renderCommunication))
    .catch(err => alert(err.message || 'Failed to clear messages'));
}

/* ---------- SYSTEM SETTINGS ---------- */
async function loadSystemSettings() {
  try {
    const settings = await apiFetch('settings.php');
    if (document.getElementById('settingSchoolName')) document.getElementById('settingSchoolName').value = settings.school_name || "";
    if (document.getElementById('settingAcademicYear')) document.getElementById('settingAcademicYear').value = settings.academic_year || "";
    if (document.getElementById('settingTerm')) document.getElementById('settingTerm').value = settings.current_term || 'Term 1';
    if (document.getElementById('settingCurrency')) document.getElementById('settingCurrency').value = settings.currency || 'KES';
    applyTheme(settings.theme || 'light');
  } catch (err) {
    console.error('Failed to load settings:', err);
  }
}

async function saveSystemSettings() {
  const settings = {
    school_name: document.getElementById('settingSchoolName').value.trim(),
    academic_year: document.getElementById('settingAcademicYear').value.trim(),
    current_term: document.getElementById('settingTerm').value,
    currency: document.getElementById('settingCurrency').value,
    theme: document.body.classList.contains('dark-theme') ? 'dark' : 'light'
  };
  try {
    await apiFetch('settings.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(settings) });
    await addAuditLog('Updated system settings', 'School profile and preferences');
    alert('Settings saved successfully.');
  } catch (err) {
    alert(err.message || 'Failed to save settings');
  }
}

function applyTheme(theme) {
  document.body.classList.toggle('dark-theme', theme === 'dark');
  try { localStorage.setItem('erpTheme', theme); } catch (_) {}
}

function toggleTheme() {
  const theme = document.body.classList.contains('dark-theme') ? 'light' : 'dark';
  applyTheme(theme);
}

async function addAuditLog(action, details) {
  try {
    await apiFetch('audit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_email: currentUser?.email || 'Unknown', action, details })
    });
  } catch (_) {}
}

async function loadAuditLog() {
  const tbody = document.getElementById('auditTableBody');
  if (!tbody) return;
  try {
    const rows = await apiFetch('audit.php');
    tbody.innerHTML = rows.length ? rows.map(row => `<tr><td>${escapeHtml(row.created_at)}</td><td>${escapeHtml(row.user_email)}</td><td>${escapeHtml(row.action)}</td><td>${escapeHtml(row.details || '')}</td></tr>`).join('') : '<tr><td colspan="4">No activity recorded.</td></tr>';
  } catch (err) {
    tbody.innerHTML = '<tr><td colspan="4">Unable to load activity.</td></tr>';
  }
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
}

function downloadDatabaseBackup() {
  const link = document.createElement('a');
  link.href = 'api/backup.php';
  link.download = '';
  document.body.appendChild(link);
  link.click();
  link.remove();
  addAuditLog('Downloaded database backup', 'SQLite database backup');
}

/* ---------- TIMETABLE ---------- */
async function loadTimetable() {
  const tbody = document.getElementById('timetableTableBody');
  if (!tbody) return;
  const form = document.getElementById('ttFilterForm')?.value || '';
  const stream = document.getElementById('ttFilterStream')?.value || '';
  const params = new URLSearchParams();
  if (form) params.set('form', form);
  if (stream) params.set('stream', stream);
  try {
    const rows = await apiFetch('timetable.php' + (params.toString() ? '?' + params : ''));
    tbody.innerHTML = rows.length ? rows.map(row => `<tr><td>${escapeHtml(row.day)}</td><td>${escapeHtml(row.period)}</td><td>${escapeHtml(row.form)}</td><td>${escapeHtml(row.stream)}</td><td>${escapeHtml(row.subject)}</td><td>${escapeHtml(row.teacher || '-')}</td><td>${escapeHtml(row.room || '-')}</td><td><button class="table-btn" type="button" onclick="deleteTimetableEntry(${Number(row.id)})">Delete</button></td></tr>`).join('') : '<tr><td colspan="8">No timetable entries found.</td></tr>';
  } catch (_) {
    tbody.innerHTML = '<tr><td colspan="8">Unable to load timetable.</td></tr>';
  }
}

async function addTimetableEntry() {
  const payload = { day: document.getElementById('ttDay').value, period: document.getElementById('ttPeriod').value, form: document.getElementById('ttForm').value, stream: document.getElementById('ttStream').value, subject: document.getElementById('ttSubject').value.trim(), teacher: document.getElementById('ttTeacher').value.trim(), room: document.getElementById('ttRoom').value.trim() };
  if (!payload.subject) { alert('Enter a subject.'); return; }
  try {
    await apiFetch('timetable.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    document.getElementById('ttSubject').value = ''; document.getElementById('ttTeacher').value = ''; document.getElementById('ttRoom').value = '';
    await addAuditLog('Added timetable entry', `${payload.day} ${payload.period}: ${payload.subject} (${payload.form} ${payload.stream})`);
    loadTimetable();
  } catch (err) { alert(err.message || 'Failed to add timetable entry'); }
}

async function deleteTimetableEntry(id) {
  if (!confirm('Delete this timetable entry?')) return;
  try {
    await apiFetch(`timetable.php?id=${id}`, { method: 'DELETE' });
    await addAuditLog('Deleted timetable entry', `Timetable ID ${id}`);
    loadTimetable();
  } catch (err) { alert(err.message || 'Failed to delete timetable entry'); }
}

/* ---------- PASSWORD SECURITY ---------- */
async function changePassword() {
  const oldPassword = document.getElementById('oldPassword').value;
  const newPassword = document.getElementById('newPassword').value;
  if (!oldPassword || !newPassword) { alert('Complete both password fields.'); return; }
  if (newPassword.length < 6) { alert('New password must be at least 6 characters.'); return; }
  try {
    await apiFetch('auth.php?action=change_password', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ old_password: oldPassword, new_password: newPassword }) });
    document.getElementById('oldPassword').value = ''; document.getElementById('newPassword').value = '';
    await addAuditLog('Changed password', 'Account password updated');
    alert('Password changed successfully.');
  } catch (err) { alert(err.message || 'Failed to change password'); }
}

/* ---------- INIT ---------- */
document.addEventListener("DOMContentLoaded", () => {
  setupMenuNavigation();
  renderCalendar();
  attachCalendarButtons();
  setupAttendanceControls();
  dedupeStudents();
  const themeButton = document.getElementById('themeToggle');
  if (themeButton) themeButton.onclick = toggleTheme;
  const savedTheme = localStorage.getItem('erpTheme');
  if (savedTheme) applyTheme(savedTheme);
  loadSystemSettings();
  loadAllData().then(() => {
    renderStudents(); renderTeachers(); renderFinance(); updateDashboard(); updateAttendanceRate(); renderCommunication(); initCharts(); updateEnrollmentByForm();
  });
});

