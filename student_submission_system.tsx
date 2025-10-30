import React, { useState, useEffect } from 'react';
import { Upload, LogOut, User, FileText, CheckCircle, Clock, UserPlus, Download, BarChart } from 'lucide-react';

const StudentSubmissionSystem = () => {
  const [currentUser, setCurrentUser] = useState(null);
  const [loginForm, setLoginForm] = useState({ userId: '', password: '' });
  const [students, setStudents] = useState({});
  const [submissions, setSubmissions] = useState({});
  const [adminPassword] = useState('admin123');
  const [showAddStudent, setShowAddStudent] = useState(false);

  const groups = ['IT-21', 'IT-22', 'IT-23', 'IT-24', 'IT-25', 'IT-26', 'IT-27'];
  const practicalWorks = Array.from({ length: 24 }, (_, i) => `Amaliy ish ${i + 1}`);
const APPS_SCRIPT_URL = "https://script.google.com/macros/s/AKfycby4QdjzoeKOH-Y3WzUL3T9kfnS04a-pRvkD4Yi4hj4XganDhnf6m-icCS5XtNjm9SCN/exec"; 
  useEffect(() => {
    const savedStudents = {
      'S001': { userId: 'S001', password: 'pass1', name: 'Ali Valiyev', group: 'IT-21' },
      'S002': { userId: 'S002', password: 'pass2', name: 'Dilnoza Karimova', group: 'IT-21' },
      'S003': { userId: 'S003', password: 'pass3', name: 'Jasur Toshmatov', group: 'IT-22' },
      'S004': { userId: 'S004', password: 'pass4', name: 'Madina Rahimova', group: 'IT-22' },
      'S005': { userId: 'S005', password: 'pass5', name: 'Sardor Umarov', group: 'IT-23' }
    };
    setStudents(savedStudents);
  }, []);

  const handleLogin = () => {
    if (loginForm.userId === 'admin' && loginForm.password === adminPassword) {
      setCurrentUser({ type: 'admin', userId: 'admin' });
    } else if (students[loginForm.userId] && students[loginForm.userId].password === loginForm.password) {
      setCurrentUser({ type: 'student', userId: loginForm.userId });
    } else {
      alert('Noto\'g\'ri login yoki parol!');
    }
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setLoginForm({ userId: '', password: '' });
  };

  const handleAddStudent = (studentData) => {
    setStudents(prev => ({
      ...prev,
      [studentData.userId]: studentData
    }));
    setShowAddStudent(false);
    alert('Talaba muvaffaqiyatli qo\'shildi!');
  };

const handleFileSubmit = async (practicalWork, file) => {
  if (!file) {
    alert('Iltimos, fayl tanlang!');
    return;
  }

  // Fayl turi tekshiruvi (hozirgi qoidalarga mos)
  const allowedTypes = ['.ppt', '.pptx', '.doc', '.docx', '.zip', '.py', '.pdf'];
  const fileExt = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
  if (!allowedTypes.includes(fileExt)) {
    alert('Faqat PPT/PPTX, DOC/DOCX, ZIP, PY yoki PDF fayllarni yuklash mumkin!');
    return;
  }

  // 10 MB limit
  const MAX_BYTES = 10 * 1024 * 1024;
  if (file.size > MAX_BYTES) {
    alert('Fayl hajmi 10 MB dan oshmasin!');
    return;
  }

  if (!practicalWork) {
    alert('Iltimos, amaliy ishni tanlang!');
    return;
  }

  try {
    // Faylni base64 ga o'girish
    const base64 = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => {
        const res = String(reader.result || "");
        resolve(res.split(',')[1]); // data:*/*;base64,XXXXX → faqat 'XXXXX'
      };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });

    // Drive Apps Script'ga yuboriladigan ma'lumot
    const res = await fetch(APPS_SCRIPT_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        fileBase64: base64,
        fileName: file.name,
        mimeType: file.type || "application/octet-stream",
        // Qo'shimcha metadata — nomlash va qaydlar uchun
        practicalWork,
        studentId: currentUser.userId,
        studentName: students[currentUser.userId].name,
        group: students[currentUser.userId].group,
      }),
    });

    const json = await res.json();
    if (json.status !== "ok") {
      console.error(json);
      alert("Yuklashda xatolik. Keyinroq urinib ko'ring.");
      return;
    }

    // Tizimdagi submission yozuvi (Drive linki bilan)
    const submissionId = `${currentUser.userId}_${practicalWork.replace(/\s/g, '_')}`;
    const newSubmission = {
      studentId: currentUser.userId,
      studentName: students[currentUser.userId].name,
      group: students[currentUser.userId].group,
      practicalWork: practicalWork,
      fileName: file.name,
      fileSize: (file.size / 1024).toFixed(2) + ' KB',
      submitDate: new Date().toLocaleString('uz-UZ'),
      status: 'kutilmoqda',
      grade: null,
      comment: '',
      driveLink: json.webViewLink,   // <<— NEW: ko'rish linki
      driveFileId: json.fileId       // <<— NEW: file ID
    };

    setSubmissions(prev => ({
      ...prev,
      [submissionId]: newSubmission
    }));

    alert('Fayl yuborildi');

  } catch (err) {
    console.error(err);
    alert("Yuklashda xatolik yuz berdi.");
  }
};

    const allowedTypes = ['.ppt', '.pptx', '.doc', '.docx', '.zip', '.py'];
    const fileExt = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
    
    if (!allowedTypes.some(type => fileExt === type)) {
      alert('Faqat PPT, DOCX, ZIP, yoki PY fayllarni yuklash mumkin!');
      return;
    }

    const submissionId = `${currentUser.userId}_${practicalWork.replace(/\s/g, '_')}`;
    const newSubmission = {
      studentId: currentUser.userId,
      studentName: students[currentUser.userId].name,
      group: students[currentUser.userId].group,
      practicalWork: practicalWork,
      fileName: file.name,
      fileSize: (file.size / 1024).toFixed(2) + ' KB',
      submitDate: new Date().toLocaleString('uz-UZ'),
      status: 'kutilmoqda',
      grade: null,
      comment: ''
    };

    setSubmissions(prev => ({
      ...prev,
      [submissionId]: newSubmission
    }));

    alert('Fayl muvaffaqiyatli yuklandi!');
  };

  const handleGrading = (submissionId, grade, comment) => {
    setSubmissions(prev => ({
      ...prev,
      [submissionId]: {
        ...prev[submissionId],
        status: 'baholandi',
        grade: grade,
        comment: comment
      }
    }));
  };

  const exportToCSV = (selectedGroup, selectedWork) => {
    let data = [];
    let headers = ['Talaba', 'Guruh'];
    
    if (selectedWork) {
      headers.push(selectedWork, 'Baho', 'Sana');
    } else {
      practicalWorks.forEach(work => {
        headers.push(work);
      });
    }

    const filteredStudents = Object.values(students).filter(s => 
      !selectedGroup || s.group === selectedGroup
    );

    filteredStudents.forEach(student => {
      const row = [student.name, student.group];
      
      if (selectedWork) {
        const submissionId = `${student.userId}_${selectedWork.replace(/\s/g, '_')}`;
        const submission = submissions[submissionId];
        row.push(
          submission ? submission.fileName : 'Topshirilmagan',
          submission?.grade || '-',
          submission?.submitDate || '-'
        );
      } else {
        practicalWorks.forEach(work => {
          const submissionId = `${student.userId}_${work.replace(/\s/g, '_')}`;
          const submission = submissions[submissionId];
          row.push(submission?.grade || '-');
        });
      }
      
      data.push(row);
    });

    const csvContent = [
      headers.join(','),
      ...data.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n');

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `natijalar_${selectedGroup || 'hammasi'}_${Date.now()}.csv`;
    link.click();
  };

  if (!currentUser) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
          <div className="text-center mb-8">
            <div className="inline-block p-3 bg-indigo-100 rounded-full mb-4">
              <FileText className="w-12 h-12 text-indigo-600" />
            </div>
            <h1 className="text-3xl font-bold text-gray-800">Dasturlash</h1>
            <p className="text-gray-600 mt-2">Amaliy ishlar tizimi</p>
          </div>
          
          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                User ID
              </label>
              <input
                type="text"
                value={loginForm.userId}
                onChange={(e) => setLoginForm({...loginForm, userId: e.target.value})}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                placeholder="S001 yoki admin"
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Parol
              </label>
              <input
                type="password"
                value={loginForm.password}
                onChange={(e) => setLoginForm({...loginForm, password: e.target.value})}
                onKeyPress={(e) => e.key === 'Enter' && handleLogin()}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                placeholder="••••••••"
              />
            </div>
            
            <button
              onClick={handleLogin}
              className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200"
            >
              Kirish
            </button>
          </div>
          
          <div className="mt-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-600">
            <p className="font-semibold mb-2">Demo hisoblar:</p>
            <p>Admin: admin / admin123</p>
            <p>Talaba: S001-S005 / pass1-pass5</p>
          </div>
        </div>
      </div>
    );
  }

  if (currentUser.type === 'admin') {
    return (
      <AdminPanel 
        students={students}
        submissions={submissions}
        groups={groups}
        practicalWorks={practicalWorks}
        onLogout={handleLogout}
        onGrade={handleGrading}
        onAddStudent={handleAddStudent}
        showAddStudent={showAddStudent}
        setShowAddStudent={setShowAddStudent}
        onExport={exportToCSV}
      />
    );
  }

  return (
    <StudentPanel
      currentUser={currentUser}
      student={students[currentUser.userId]}
      submissions={submissions}
      practicalWorks={practicalWorks}
      onLogout={handleLogout}
      onSubmit={handleFileSubmit}
    />
  );
};

const AdminPanel = ({ students, submissions, groups, practicalWorks, onLogout, onGrade, onAddStudent, showAddStudent, setShowAddStudent, onExport }) => {
  const [selectedGroup, setSelectedGroup] = useState('');
  const [selectedWork, setSelectedWork] = useState('');
  const [activeTab, setActiveTab] = useState('submissions');

  const filteredSubmissions = Object.entries(submissions).filter(([_, sub]) => {
    const groupMatch = !selectedGroup || students[sub.studentId]?.group === selectedGroup;
    const workMatch = !selectedWork || sub.practicalWork === selectedWork;
    return groupMatch && workMatch;
  });

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-indigo-600 text-white p-4 shadow-lg">
        <div className="container mx-auto flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">Admin Paneli - Dasturlash</h1>
            <p className="text-sm text-indigo-200">24 ta amaliy ish | {Object.keys(students).length} ta talaba</p>
          </div>
          <button
            onClick={onLogout}
            className="flex items-center gap-2 bg-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-800 transition"
          >
            <LogOut className="w-4 h-4" />
            Chiqish
          </button>
        </div>
      </nav>

      <div className="container mx-auto p-6">
        <div className="flex gap-4 mb-6">
          <button
            onClick={() => setActiveTab('submissions')}
            className={`px-6 py-3 rounded-lg font-semibold transition ${activeTab === 'submissions' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'}`}
          >
            Topshiriqlar
          </button>
          <button
            onClick={() => setActiveTab('students')}
            className={`px-6 py-3 rounded-lg font-semibold transition ${activeTab === 'students' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'}`}
          >
            Talabalar
          </button>
          <button
            onClick={() => setActiveTab('analytics')}
            className={`px-6 py-3 rounded-lg font-semibold transition ${activeTab === 'analytics' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'}`}
          >
            Statistika
          </button>
        </div>

        {activeTab === 'submissions' && (
          <>
            <div className="bg-white rounded-lg shadow-md p-6 mb-6">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Guruh</label>
                  <select
                    value={selectedGroup}
                    onChange={(e) => setSelectedGroup(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                  >
                    <option value="">Barcha guruhlar</option>
                    {groups.map(g => <option key={g} value={g}>{g}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Amaliy ish</label>
                  <select
                    value={selectedWork}
                    onChange={(e) => setSelectedWork(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                  >
                    <option value="">Barcha ishlar</option>
                    {practicalWorks.map(w => <option key={w} value={w}>{w}</option>)}
                  </select>
                </div>
                <div className="flex items-end">
                  <button
                    onClick={() => onExport(selectedGroup, selectedWork)}
                    className="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
                  >
                    <Download className="w-4 h-4" />
                    CSV Eksport
                  </button>
                </div>
              </div>
            </div>

            {filteredSubmissions.length === 0 ? (
              <div className="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <FileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <p>Topshiriqlar topilmadi</p>
              </div>
            ) : (
              <div className="grid gap-4">
                {filteredSubmissions.map(([id, submission]) => (
                  <AdminSubmissionCard
                    key={id}
                    submissionId={id}
                    submission={submission}
                    onGrade={onGrade}
                  />
                ))}
              </div>
            )}
          </>
        )}

        {activeTab === 'students' && (
          <StudentsManagement
            students={students}
            groups={groups}
            showAddStudent={showAddStudent}
            setShowAddStudent={setShowAddStudent}
            onAddStudent={onAddStudent}
            submissions={submissions}
            practicalWorks={practicalWorks}
          />
        )}

        {activeTab === 'analytics' && (
          <AnalyticsView
            students={students}
            submissions={submissions}
            groups={groups}
            practicalWorks={practicalWorks}
          />
        )}
      </div>
    </div>
  );
};

const StudentsManagement = ({ students, groups, showAddStudent, setShowAddStudent, onAddStudent, submissions, practicalWorks }) => {
  const [newStudent, setNewStudent] = useState({ userId: '', password: '', name: '', group: '' });

  const handleAdd = () => {
    if (!newStudent.userId || !newStudent.password || !newStudent.name || !newStudent.group) {
      alert('Barcha maydonlarni to\'ldiring!');
      return;
    }
    onAddStudent(newStudent);
    setNewStudent({ userId: '', password: '', name: '', group: '' });
  };

  const calculateProgress = (studentId) => {
    const completed = Object.values(submissions).filter(s => 
      s.studentId === studentId && s.status === 'baholandi'
    ).length;
    return ((completed / practicalWorks.length) * 100).toFixed(1);
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Talabalar ro'yxati</h2>
        <button
          onClick={() => setShowAddStudent(!showAddStudent)}
          className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
        >
          <UserPlus className="w-4 h-4" />
          Talaba qo'shish
        </button>
      </div>

      {showAddStudent && (
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <h3 className="font-bold text-lg mb-4">Yangi talaba</h3>
          <div className="grid grid-cols-2 gap-4">
            <input
              type="text"
              placeholder="User ID (S006)"
              value={newStudent.userId}
              onChange={(e) => setNewStudent({...newStudent, userId: e.target.value})}
              className="px-4 py-2 border border-gray-300 rounded-lg"
            />
            <input
              type="text"
              placeholder="Parol"
              value={newStudent.password}
              onChange={(e) => setNewStudent({...newStudent, password: e.target.value})}
              className="px-4 py-2 border border-gray-300 rounded-lg"
            />
            <input
              type="text"
              placeholder="Ism-familiya"
              value={newStudent.name}
              onChange={(e) => setNewStudent({...newStudent, name: e.target.value})}
              className="px-4 py-2 border border-gray-300 rounded-lg"
            />
            <select
              value={newStudent.group}
              onChange={(e) => setNewStudent({...newStudent, group: e.target.value})}
              className="px-4 py-2 border border-gray-300 rounded-lg"
            >
              <option value="">Guruh tanlang</option>
              {groups.map(g => <option key={g} value={g}>{g}</option>)}
            </select>
          </div>
          <button
            onClick={handleAdd}
            className="mt-4 bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
          >
            Saqlash
          </button>
        </div>
      )}

      <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User ID</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ism</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guruh</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {Object.values(students).map(student => (
              <tr key={student.userId} className="hover:bg-gray-50">
                <td className="px-6 py-4 text-sm font-medium text-gray-900">{student.userId}</td>
                <td className="px-6 py-4 text-sm text-gray-900">{student.name}</td>
                <td className="px-6 py-4 text-sm text-gray-600">{student.group}</td>
                <td className="px-6 py-4">
                  <div className="flex items-center gap-2">
                    <div className="w-32 bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-indigo-600 h-2 rounded-full"
                        style={{ width: `${calculateProgress(student.userId)}%` }}
                      />
                    </div>
                    <span className="text-sm font-semibold text-gray-700">
                      {calculateProgress(student.userId)}%
                    </span>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

const AnalyticsView = ({ students, submissions, groups, practicalWorks }) => {
  const groupStats = groups.map(group => {
    const groupStudents = Object.values(students).filter(s => s.group === group);
    const totalSubmissions = groupStudents.length * practicalWorks.length;
    const completedSubmissions = Object.values(submissions).filter(s => 
      s.status === 'baholandi' && students[s.studentId]?.group === group
    ).length;
    const progress = totalSubmissions > 0 ? ((completedSubmissions / totalSubmissions) * 100).toFixed(1) : 0;
    
    return { group, students: groupStudents.length, progress, completed: completedSubmissions, total: totalSubmissions };
  });

  const totalStudents = Object.keys(students).length;
  const totalPossible = totalStudents * practicalWorks.length;
  const totalCompleted = Object.values(submissions).filter(s => s.status === 'baholandi').length;
  const overallProgress = totalPossible > 0 ? ((totalCompleted / totalPossible) * 100).toFixed(1) : 0;

  return (
    <div>
      <h2 className="text-2xl font-bold text-gray-800 mb-6">Statistika</h2>
      
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-100 rounded-lg">
              <User className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Talabalar</p>
              <p className="text-2xl font-bold text-gray-800">{totalStudents}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-green-100 rounded-lg">
              <FileText className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Amaliy ishlar</p>
              <p className="text-2xl font-bold text-gray-800">{practicalWorks.length}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-purple-100 rounded-lg">
              <CheckCircle className="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Bajarilgan</p>
              <p className="text-2xl font-bold text-gray-800">{totalCompleted}/{totalPossible}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-indigo-100 rounded-lg">
              <BarChart className="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Umumiy progress</p>
              <p className="text-2xl font-bold text-gray-800">{overallProgress}%</p>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="font-bold text-lg mb-4">Guruhlar bo'yicha statistika</h3>
        <div className="space-y-4">
          {groupStats.map(stat => (
            <div key={stat.group} className="border-b border-gray-200 pb-4 last:border-0">
              <div className="flex justify-between items-center mb-2">
                <div>
                  <h4 className="font-semibold text-gray-800">{stat.group}</h4>
                  <p className="text-sm text-gray-600">{stat.students} ta talaba • {stat.completed}/{stat.total} bajarilgan</p>
                </div>
                <span className="text-lg font-bold text-indigo-600">{stat.progress}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-3">
                <div
                  className="bg-indigo-600 h-3 rounded-full transition-all duration-500"
                  style={{ width: `${stat.progress}%` }}
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

const StudentPanel = ({ currentUser, student, submissions, practicalWorks, onLogout, onSubmit }) => {
  const mySubmissions = Object.entries(submissions).filter(([_, sub]) => 
    sub.studentId === currentUser.userId
  );

  const completedCount = mySubmissions.filter(([_, sub]) => sub.status === 'baholandi').length;
  const progress = ((completedCount / practicalWorks.length) * 100).toFixed(1);

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-indigo-600 text-white p-4 shadow-lg">
        <div className="container mx-auto flex justify-between items-center">
          <div className="flex items-center gap-3">
            <User className="w-6 h-6" />
            <div>
              <h1 className="text-xl font-bold">{student.name}</h1>
              <p className="text-sm text-indigo-200">{student.userId} • {student.group}</p>
            </div>
          </div>
          <button
            onClick={onLogout}
            className="flex items-center gap-2 bg-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-800 transition"
          >
            <LogOut className="w-4 h-4" />
            Chiqish
          </button>
        </div>
      </nav>

      <div className="container mx-auto p-6 max-w-5xl">
        <div className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div className="flex justify-between items-center mb-4">
            <div>
              <h2 className="text-2xl font-bold">Umumiy progress</h2>
              <p className="text-indigo-100">Bajarilgan: {completedCount} / {practicalWorks.length}</p>
            </div>
            <div className="text-right">
              <div className="text-5xl font-bold">{progress}%</div>
            </div>
          </div>
          <div className="w-full bg-white bg-opacity-30 rounded-full h-4">
            <div
              className="bg-white h-4 rounded-full transition-all duration-500"
              style={{ width: `${progress}%` }}
            />
          </div>
        </div>

        <SubmissionForm practicalWorks={practicalWorks} onSubmit={onSubmit} />
        <MySubmissions submissions={mySubmissions} practicalWorks={practicalWorks} />
      </div>
    </div>
  );
};

const SubmissionForm = ({ practicalWorks, onSubmit }) => {
  const [practicalWork, setPracticalWork] = useState('');
  const [file, setFile] = useState(null);

  const handleSubmit = () => {
    if (!practicalWork) {
      alert('Iltimos, amaliy ishni tanlang!');
      return;
    }
    onSubmit(practicalWork, file);
    setPracticalWork('');
    setFile(null);
  };

  return (
    <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <Upload className="w-6 h-6 text-indigo-600" />
        Amaliy ish topshirish
      </h2>
      
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Amaliy ish raqami
          </label>
          <select
            value={practicalWork}
            onChange={(e) => setPracticalWork(e.target.value)}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Amaliy ishni tanlang...</option>
            {practicalWorks.map(work => (
              <option key={work} value={work}>{work}</option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Fayl yuklang (PPT, DOCX, ZIP, PY)
          </label>
          <input
            type="file"
            onChange={(e) => setFile(e.target.files[0])}
            accept=".ppt,.pptx,.doc,.docx,.zip,.py"
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
          {file && (
            <p className="mt-2 text-sm text-gray-600">
              Tanlangan: {file.name} ({(file.size / 1024).toFixed(2)} KB)
            </p>
          )}
        </div>

        <button
          onClick={handleSubmit}
          className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200 flex items-center justify-center gap-2"
        >
          <Upload className="w-5 h-5" />
          Yuborish
        </button>
      </div>
    </div>
  );
};

const MySubmissions = ({ submissions, practicalWorks }) => {
  return (
    <div className="bg-white rounded-xl shadow-lg p-6">
      <h2 className="text-2xl font-bold text-gray-800 mb-6">Mening topshiriqlarim</h2>
      
      {submissions.length === 0 ? (
        <div className="text-center py-8 text-gray-500">
          <FileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
          <p>Siz hali amaliy ish topshirmadingiz</p>
        </div>
      ) : (
        <div className="space-y-4">
          {submissions.map(([id, submission]) => (
            <div key={id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
              <div className="flex justify-between items-start mb-3">
                <div>
                  <h3 className="font-bold text-lg text-gray-800">{submission.practicalWork}</h3>
                  <p className="text-sm text-gray-600">{submission.fileName} ({submission.fileSize})</p>
                  <p className="text-xs text-gray-500 mt-1">{submission.submitDate}</p>
                </div>
                <StatusBadge status={submission.status} />
              </div>
              
              {submission.status === 'baholandi' && (
                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="font-semibold text-gray-700">Baho:</span>
                    <span className="text-2xl font-bold text-indigo-600">{submission.grade}</span>
                  </div>
                  {submission.comment && (
                    <div>
                      <span className="font-semibold text-gray-700">Izoh:</span>
                      <p className="text-gray-600 mt-1">{submission.comment}</p>
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

const AdminSubmissionCard = ({ submissionId, submission, onGrade }) => {
  const [grade, setGrade] = useState('');
  const [comment, setComment] = useState('');
  const [isGrading, setIsGrading] = useState(false);

  const handleGrade = () => {
    if (!grade) {
      alert('Iltimos, baho kiriting!');
      return;
    }
    onGrade(submissionId, grade, comment);
    setIsGrading(false);
    setGrade('');
    setComment('');
  };

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex justify-between items-start mb-4">
        <div>
          <h3 className="font-bold text-xl text-gray-800">{submission.studentName}</h3>
          <p className="text-sm text-gray-600">ID: {submission.studentId} • Guruh: {submission.group}</p>
          <p className="text-sm text-indigo-600 font-semibold mt-1">{submission.practicalWork}</p>
        </div>
        <StatusBadge status={submission.status} />
      </div>

      <div className="space-y-2 text-sm text-gray-600 mb-4">
        <p><span className="font-semibold">Fayl:</span> {submission.fileName}</p>
        <p><span className="font-semibold">O'lcham:</span> {submission.fileSize}</p>
        <p><span className="font-semibold">Yuborilgan:</span> {submission.submitDate}</p>
      </div>

      {submission.status === 'baholandi' ? (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
          <p className="font-bold text-green-800 text-lg">Baho: {submission.grade}</p>
          {submission.comment && <p className="text-gray-700 mt-2">{submission.comment}</p>}
        </div>
      ) : isGrading ? (
        <div className="space-y-3">
          <input
            type="text"
            placeholder="Baho (masalan: 85, 5, A, Excellent)"
            value={grade}
            onChange={(e) => setGrade(e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
          <textarea
            placeholder="Izoh (ixtiyoriy)"
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            rows="3"
          />
          <div className="flex gap-2">
            <button
              onClick={handleGrade}
              className="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition"
            >
              Saqlash
            </button>
            <button
              onClick={() => setIsGrading(false)}
              className="flex-1 bg-gray-400 text-white py-2 rounded-lg hover:bg-gray-500 transition"
            >
              Bekor qilish
            </button>
          </div>
        </div>
      ) : (
        <button
          onClick={() => setIsGrading(true)}
          className="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition font-semibold"
        >
          Baholash
        </button>
      )}
    </div>
  );
};

const StatusBadge = ({ status }) => {
  const configs = {
    'kutilmoqda': {
      bg: 'bg-yellow-100',
      text: 'text-yellow-800',
      icon: Clock,
      label: 'Kutilmoqda'
    },
    'baholandi': {
      bg: 'bg-green-100',
      text: 'text-green-800',
      icon: CheckCircle,
      label: 'Baholandi'
    }
  };

  const config = configs[status];
  const Icon = config.icon;

  return (
    <span className={`${config.bg} ${config.text} px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-1`}>
      <Icon className="w-4 h-4" />
      {config.label}
    </span>
  );
};

export default StudentSubmissionSystem;