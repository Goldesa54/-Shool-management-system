const erpDB = {
  keys: {
    users: "erpUsers",
    students: "erpStudents",
    teachers: "erpTeachers",
    attendance: "erpAttendance",
    fees: "erpFees",
    exams: "erpExamRecords",
    communication: "erpCommunication"
  },

  get(key, defaultValue = []) {
    const raw = localStorage.getItem(key);
    if (!raw) return defaultValue;
    try {
      return JSON.parse(raw);
    } catch (error) {
      console.error("erpDB: failed to parse " + key, error);
      return defaultValue;
    }
  },

  set(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },

  getUsers()         { return this.get(this.keys.users); },
  setUsers(v)        { this.set(this.keys.users, v); },

  getStudents()      { return this.get(this.keys.students); },
  setStudents(v)     { this.set(this.keys.students, v); },

  getTeachers()      { return this.get(this.keys.teachers); },
  setTeachers(v)     { this.set(this.keys.teachers, v); },

  getAttendance()    { return this.get(this.keys.attendance); },
  setAttendance(v)   { this.set(this.keys.attendance, v); },

  getFees()          { return this.get(this.keys.fees); },
  setFees(v)         { this.set(this.keys.fees, v); },

  getExams()         { return this.get(this.keys.exams); },
  setExams(v)        { this.set(this.keys.exams, v); },

  getCommunication() { return this.get(this.keys.communication); },
  setCommunication(v){ this.set(this.keys.communication, v); },

  ensureDefaults() {
    /* ---------- DEFAULT DEMO ACCOUNTS (all 4 roles) ----------
       Admin   : admin@school.com   / admin123
       Teacher : teacher@school.com / teacher123
       Student : student@school.com / student123
       Parent  : parent@school.com  / parent123
    ---------------------------------------------------------- */
    if (!localStorage.getItem(this.keys.users)) {
      this.setUsers([
        { email: "admin@school.com",   password: "admin123",   role: "Admin"   },
        { email: "teacher@school.com", password: "teacher123", role: "Teacher" },
        { email: "student@school.com", password: "student123", role: "Student" },
        { email: "parent@school.com",  password: "parent123",  role: "Parent"  }
      ]);
    } else {
      /* Make sure existing installs get the new demo accounts added
         without wiping any real accounts the user already created.  */
      const existing = this.getUsers();
      const demoAccounts = [
        { email: "admin@school.com",   password: "admin123",   role: "Admin"   },
        { email: "teacher@school.com", password: "teacher123", role: "Teacher" },
        { email: "student@school.com", password: "student123", role: "Student" },
        { email: "parent@school.com",  password: "parent123",  role: "Parent"  }
      ];
      let changed = false;
      demoAccounts.forEach(demo => {
        const found = existing.find(u => u.email === demo.email);
        if (!found) {
          existing.push(demo);
          changed = true;
        }
      });
      if (changed) this.setUsers(existing);
    }

    if (!localStorage.getItem(this.keys.students))     this.setStudents([]);
    if (!localStorage.getItem(this.keys.teachers))     this.setTeachers([]);
    if (!localStorage.getItem(this.keys.attendance))   this.setAttendance([]);
    if (!localStorage.getItem(this.keys.fees))         this.setFees([]);
    if (!localStorage.getItem(this.keys.exams))        this.setExams([]);
    if (!localStorage.getItem(this.keys.communication))this.setCommunication([]);
  }
};

erpDB.ensureDefaults();
