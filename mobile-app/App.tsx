import React, { useMemo, useState } from 'react';
import {
  Alert,
  FlatList,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as DocumentPicker from 'expo-document-picker';
import * as FileSystem from 'expo-file-system';

const APPS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycby4QdjzoeKOH-Y3WzUL3T9kfnS04a-pRvkD4Yi4hj4XganDhnf6m-icCS5XtNjm9SCN/exec';

const groups = ['IT-21', 'IT-22', 'IT-23', 'IT-24', 'IT-25', 'IT-26', 'IT-27'];
const practicalWorks = Array.from({ length: 24 }, (_, index) => `Amaliy ish ${index + 1}`);

const allowedExtensions = ['ppt', 'pptx', 'doc', 'docx', 'zip', 'py', 'pdf'];
const MAX_BYTES = 10 * 1024 * 1024;

type UserType = 'student' | 'admin';

type Student = {
  userId: string;
  password: string;
  name: string;
  group: string;
};

type Submission = {
  studentId: string;
  studentName: string;
  group: string;
  practicalWork: string;
  fileName: string;
  fileSize: string;
  submitDate: string;
  status: 'kutilmoqda' | 'baholandi';
  grade: string | null;
  comment: string;
  driveLink?: string;
  driveFileId?: string;
};

type PickedFile = {
  name: string;
  size: number;
  mimeType?: string | null;
  base64: string;
};

const defaultStudents: Record<string, Student> = {
  S001: { userId: 'S001', password: 'pass1', name: 'Ali Valiyev', group: 'IT-21' },
  S002: { userId: 'S002', password: 'pass2', name: 'Dilnoza Karimova', group: 'IT-21' },
  S003: { userId: 'S003', password: 'pass3', name: 'Jasur Toshmatov', group: 'IT-22' },
  S004: { userId: 'S004', password: 'pass4', name: 'Madina Rahimova', group: 'IT-22' },
  S005: { userId: 'S005', password: 'pass5', name: 'Sardor Umarov', group: 'IT-23' }
};

type LoginState = {
  userId: string;
  password: string;
};

type CurrentUser = {
  type: UserType;
  userId: string;
};

const LoginScreen: React.FC<{
  credentials: LoginState;
  onChange: (values: Partial<LoginState>) => void;
  onLogin: () => void;
}> = ({ credentials, onChange, onLogin }) => {
  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      <View style={styles.loginCard}>
        <Text style={styles.appTitle}>Zafarts</Text>
        <Text style={styles.subtitle}>Amaliy ishlar tizimi</Text>

        <View style={styles.inputGroup}>
          <Text style={styles.inputLabel}>User ID</Text>
          <TextInput
            value={credentials.userId}
            onChangeText={(value) => onChange({ userId: value })}
            placeholder="S001 yoki admin"
            style={styles.input}
            autoCapitalize="characters"
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.inputLabel}>Parol</Text>
          <TextInput
            value={credentials.password}
            onChangeText={(value) => onChange({ password: value })}
            placeholder="••••••••"
            style={styles.input}
            secureTextEntry
          />
        </View>

        <TouchableOpacity style={styles.primaryButton} onPress={onLogin}>
          <Text style={styles.primaryButtonText}>Kirish</Text>
        </TouchableOpacity>

        <View style={styles.demoBox}>
          <Text style={styles.demoTitle}>Demo hisoblar</Text>
          <Text style={styles.demoText}>Admin: admin / admin123</Text>
          <Text style={styles.demoText}>Talabalar: S001-S005 / pass1-pass5</Text>
        </View>
      </View>
    </SafeAreaView>
  );
};

type StudentDashboardProps = {
  student: Student;
  submissions: Record<string, Submission>;
  onSubmit: (practicalWork: string, file: PickedFile) => Promise<void>;
  onLogout: () => void;
};

const StudentDashboard: React.FC<StudentDashboardProps> = ({ student, submissions, onSubmit, onLogout }) => {
  const [uploading, setUploading] = useState<string | null>(null);

  const handleSelectAndUpload = async (practicalWork: string) => {
    try {
      setUploading(practicalWork);
      const result = await DocumentPicker.getDocumentAsync({ type: '*/*', copyToCacheDirectory: true });

      if (result.type !== 'success' || !result.assets?.length) {
        setUploading(null);
        return;
      }

      const asset = result.assets[0];
      const extension = asset.name?.split('.').pop()?.toLowerCase();

      if (!extension || !allowedExtensions.includes(extension)) {
        Alert.alert('Xatolik', "Faqat PPT/PPTX, DOC/DOCX, ZIP, PY yoki PDF fayllarni yuborish mumkin.");
        setUploading(null);
        return;
      }

      if (asset.size && asset.size > MAX_BYTES) {
        Alert.alert('Xatolik', 'Fayl hajmi 10 MB dan oshmasligi kerak.');
        setUploading(null);
        return;
      }

      const base64 = await FileSystem.readAsStringAsync(asset.uri, {
        encoding: FileSystem.EncodingType.Base64
      });

      await onSubmit(practicalWork, {
        name: asset.name ?? `file.${extension}`,
        size: asset.size ?? 0,
        mimeType: asset.mimeType,
        base64
      });

      Alert.alert('Muvaffaqiyatli', 'Fayl yuborildi.');
    } catch (error: any) {
      const message = error?.message ?? 'Yuklash vaqtida xatolik yuz berdi.';
      Alert.alert('Xatolik', message);
    } finally {
      setUploading(null);
    }
  };

  const getSubmissionKey = (work: string) => `${student.userId}_${work.replace(/\s/g, '_')}`;

  return (
    <SafeAreaView style={styles.dashboardContainer}>
      <StatusBar style="dark" />
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Salom, {student.name}</Text>
          <Text style={styles.headerSubtitle}>Guruh: {student.group}</Text>
        </View>
        <TouchableOpacity onPress={onLogout} style={styles.logoutButton}>
          <Text style={styles.logoutText}>Chiqish</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        <Text style={styles.sectionTitle}>Amaliy ishlar</Text>
        {practicalWorks.map((work) => {
          const submission = submissions[getSubmissionKey(work)];
          const status = submission?.status === 'baholandi' ? '✅ Baholandi' : submission ? '⏳ Kutilmoqda' : '⬜ Topshirilmagan';
          return (
            <View key={work} style={styles.card}>
              <View style={styles.cardHeader}>
                <Text style={styles.cardTitle}>{work}</Text>
                <Text style={styles.cardStatus}>{status}</Text>
              </View>
              {submission && (
                <View style={styles.cardBody}>
                  <Text style={styles.cardMeta}>Fayl: {submission.fileName}</Text>
                  <Text style={styles.cardMeta}>Yuborilgan vaqt: {submission.submitDate}</Text>
                  {submission.grade && <Text style={styles.cardMeta}>Baho: {submission.grade}</Text>}
                  {submission.comment ? <Text style={styles.cardMeta}>Izoh: {submission.comment}</Text> : null}
                </View>
              )}
              <TouchableOpacity
                onPress={() => handleSelectAndUpload(work)}
                style={[styles.primaryButton, uploading === work && styles.primaryButtonDisabled]}
                disabled={uploading === work}
              >
                <Text style={styles.primaryButtonText}>
                  {uploading === work ? 'Yuborilmoqda...' : 'Fayl yuborish'}
                </Text>
              </TouchableOpacity>
            </View>
          );
        })}
      </ScrollView>
    </SafeAreaView>
  );
};

type AdminDashboardProps = {
  students: Record<string, Student>;
  submissions: Record<string, Submission>;
  onGrade: (submissionId: string, grade: string, comment: string) => void;
  onAddStudent: (student: Student) => void;
  onLogout: () => void;
};

const AdminDashboard: React.FC<AdminDashboardProps> = ({ students, submissions, onGrade, onAddStudent, onLogout }) => {
  const [selectedSubmission, setSelectedSubmission] = useState<string | null>(null);
  const [grade, setGrade] = useState('');
  const [comment, setComment] = useState('');
  const [newStudent, setNewStudent] = useState<Student>({ userId: '', password: '', name: '', group: groups[0] });

  const submissionList = useMemo(() => Object.entries(submissions), [submissions]);
  const totalStudents = Object.keys(students).length;
  const totalPossible = totalStudents * practicalWorks.length;
  const totalGraded = submissionList.filter(([, value]) => value.status === 'baholandi').length;
  const overallProgress = totalPossible > 0 ? Math.round((totalGraded / totalPossible) * 100) : 0;

  const handleGrade = (submissionId: string) => {
    if (!grade) {
      Alert.alert('Xatolik', 'Iltimos, baho kiriting.');
      return;
    }
    onGrade(submissionId, grade, comment);
    setSelectedSubmission(null);
    setGrade('');
    setComment('');
    Alert.alert('Muvaffaqiyatli', 'Baho saqlandi.');
  };

  const handleAddStudent = () => {
    if (!newStudent.userId || !newStudent.name || !newStudent.password) {
      Alert.alert('Xatolik', "Barcha maydonlarni to'ldiring.");
      return;
    }

    if (students[newStudent.userId]) {
      Alert.alert('Xatolik', 'Bu ID bilan talaba mavjud.');
      return;
    }

    onAddStudent(newStudent);
    Alert.alert('Muvaffaqiyatli', "Talaba qo'shildi.");
    setNewStudent({ userId: '', password: '', name: '', group: groups[0] });
  };

  return (
    <SafeAreaView style={styles.dashboardContainer}>
      <StatusBar style="light" />
      <View style={[styles.header, styles.adminHeader]}>
        <View>
          <Text style={styles.headerTitleLight}>Admin paneli</Text>
          <Text style={styles.headerSubtitleLight}>
            {totalStudents} talaba • {totalGraded} / {totalPossible} baholangan ishlari
          </Text>
        </View>
        <TouchableOpacity onPress={onLogout} style={styles.logoutButtonLight}>
          <Text style={styles.logoutTextLight}>Chiqish</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.statRow}>
          <View style={styles.statCard}>
            <Text style={styles.statTitle}>Talabalar</Text>
            <Text style={styles.statValue}>{totalStudents}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statTitle}>Umumiy progress</Text>
            <Text style={styles.statValue}>{overallProgress}%</Text>
          </View>
        </View>

        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Topshiriqlar</Text>
          {submissionList.length === 0 ? (
            <Text style={styles.emptyText}>Hozircha topshiriqlar yo'q.</Text>
          ) : (
            submissionList.map(([id, submission]) => (
              <View key={id} style={styles.submissionItem}>
                <View style={styles.submissionMeta}>
                  <Text style={styles.submissionTitle}>{submission.studentName}</Text>
                  <Text style={styles.submissionSubtitle}>{submission.practicalWork}</Text>
                  <Text style={styles.submissionDetail}>Guruh: {submission.group}</Text>
                  <Text style={styles.submissionDetail}>Fayl: {submission.fileName}</Text>
                  <Text style={styles.submissionDetail}>Vaqt: {submission.submitDate}</Text>
                  {submission.grade && <Text style={styles.submissionDetail}>Baho: {submission.grade}</Text>}
                  {submission.comment ? <Text style={styles.submissionDetail}>Izoh: {submission.comment}</Text> : null}
                </View>

                <TouchableOpacity
                  onPress={() => {
                    setSelectedSubmission(id);
                    setGrade(submission.grade ?? '');
                    setComment(submission.comment ?? '');
                  }}
                  style={styles.secondaryButton}
                >
                  <Text style={styles.secondaryButtonText}>Baholash</Text>
                </TouchableOpacity>

                {selectedSubmission === id && (
                  <View style={styles.gradeBox}>
                    <TextInput
                      placeholder="Baho (masalan, 85)"
                      value={grade}
                      onChangeText={setGrade}
                      style={styles.input}
                      keyboardType="numeric"
                    />
                    <TextInput
                      placeholder="Izoh"
                      value={comment}
                      onChangeText={setComment}
                      style={[styles.input, styles.multilineInput]}
                      multiline
                      numberOfLines={3}
                    />
                    <TouchableOpacity style={styles.primaryButton} onPress={() => handleGrade(id)}>
                      <Text style={styles.primaryButtonText}>Saqlash</Text>
                    </TouchableOpacity>
                  </View>
                )}
              </View>
            ))
          )}
        </View>

        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Talaba qo'shish</Text>
          <View style={styles.inputGroup}>
            <Text style={styles.inputLabel}>User ID</Text>
            <TextInput
              value={newStudent.userId}
              onChangeText={(value) => setNewStudent((prev) => ({ ...prev, userId: value }))}
              style={styles.input}
              placeholder="S006"
              autoCapitalize="characters"
            />
          </View>
          <View style={styles.inputGroup}>
            <Text style={styles.inputLabel}>Ism familiya</Text>
            <TextInput
              value={newStudent.name}
              onChangeText={(value) => setNewStudent((prev) => ({ ...prev, name: value }))}
              style={styles.input}
              placeholder="Talaba ismi"
            />
          </View>
          <View style={styles.inputGroup}>
            <Text style={styles.inputLabel}>Parol</Text>
            <TextInput
              value={newStudent.password}
              onChangeText={(value) => setNewStudent((prev) => ({ ...prev, password: value }))}
              style={styles.input}
              placeholder="Yangi parol"
              secureTextEntry
            />
          </View>
          <View style={styles.inputGroup}>
            <Text style={styles.inputLabel}>Guruh</Text>
            <FlatList
              data={groups}
              keyExtractor={(item) => item}
              horizontal
              contentContainerStyle={styles.groupList}
              renderItem={({ item }) => (
                <TouchableOpacity
                  onPress={() => setNewStudent((prev) => ({ ...prev, group: item }))}
                  style={[
                    styles.groupChip,
                    newStudent.group === item && styles.groupChipSelected
                  ]}
                >
                  <Text
                    style={[
                      styles.groupChipText,
                      newStudent.group === item && styles.groupChipTextSelected
                    ]}
                  >
                    {item}
                  </Text>
                </TouchableOpacity>
              )}
            />
          </View>

          <TouchableOpacity style={styles.primaryButton} onPress={handleAddStudent}>
            <Text style={styles.primaryButtonText}>Talabani saqlash</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const App: React.FC = () => {
  const [login, setLogin] = useState<LoginState>({ userId: '', password: '' });
  const [currentUser, setCurrentUser] = useState<CurrentUser | null>(null);
  const [students, setStudents] = useState<Record<string, Student>>(defaultStudents);
  const [submissions, setSubmissions] = useState<Record<string, Submission>>({});

  const handleLogin = () => {
    if (login.userId.trim().toLowerCase() === 'admin' && login.password === 'admin123') {
      setCurrentUser({ type: 'admin', userId: 'admin' });
      return;
    }

    const student = students[login.userId.trim()];
    if (student && student.password === login.password) {
      setCurrentUser({ type: 'student', userId: student.userId });
      return;
    }

    Alert.alert('Xatolik', "Noto'g'ri login yoki parol.");
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setLogin({ userId: '', password: '' });
  };

  const handleStudentSubmission = async (practicalWork: string, file: PickedFile) => {
    if (!currentUser || currentUser.type !== 'student') {
      throw new Error('Foydalanuvchi topilmadi.');
    }

    const student = students[currentUser.userId];
    if (!student) {
      throw new Error("Talaba ma'lumotlari topilmadi.");
    }

    const payload = {
      fileBase64: file.base64,
      fileName: file.name,
      mimeType: file.mimeType ?? 'application/octet-stream',
      practicalWork,
      studentId: student.userId,
      studentName: student.name,
      group: student.group
    };

    const response = await fetch(APPS_SCRIPT_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    let json: any;
    try {
      json = await response.json();
    } catch (error) {
      throw new Error("Serverdan noto'g'ri javob qaytdi.");
    }

    if (!response.ok || json?.status !== 'ok') {
      const message = json?.message ?? "Faylni yuborib bo'lmadi. Keyinroq urinib ko'ring.";
      throw new Error(message);
    }

    const submissionId = `${student.userId}_${practicalWork.replace(/\s/g, '_')}`;
    const newSubmission: Submission = {
      studentId: student.userId,
      studentName: student.name,
      group: student.group,
      practicalWork,
      fileName: file.name,
      fileSize: `${(file.size / 1024).toFixed(2)} KB`,
      submitDate: new Date().toLocaleString('uz-UZ'),
      status: 'kutilmoqda',
      grade: null,
      comment: '',
      driveLink: json.webViewLink,
      driveFileId: json.fileId
    };

    setSubmissions((prev) => ({
      ...prev,
      [submissionId]: newSubmission
    }));
  };

  const handleGrade = (submissionId: string, grade: string, comment: string) => {
    setSubmissions((prev) => ({
      ...prev,
      [submissionId]: {
        ...prev[submissionId],
        status: 'baholandi',
        grade,
        comment
      }
    }));
  };

  const handleAddStudent = (student: Student) => {
    setStudents((prev) => ({
      ...prev,
      [student.userId]: student
    }));
  };

  if (!currentUser) {
    return (
      <LoginScreen
        credentials={login}
        onChange={(values) => setLogin((prev) => ({ ...prev, ...values }))}
        onLogin={handleLogin}
      />
    );
  }

  if (currentUser.type === 'admin') {
    return (
      <AdminDashboard
        students={students}
        submissions={submissions}
        onGrade={handleGrade}
        onAddStudent={handleAddStudent}
        onLogout={handleLogout}
      />
    );
  }

  const student = students[currentUser.userId];
  if (!student) {
    return (
      <SafeAreaView style={styles.container}>
        <Text style={styles.errorText}>Talaba ma'lumotlari topilmadi.</Text>
        <TouchableOpacity style={styles.primaryButton} onPress={handleLogout}>
          <Text style={styles.primaryButtonText}>Chiqish</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  return (
    <StudentDashboard
      student={student}
      submissions={submissions}
      onSubmit={handleStudentSubmission}
      onLogout={handleLogout}
    />
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#EEF2FF',
    justifyContent: 'center',
    paddingHorizontal: 24
  },
  loginCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 12 },
    shadowOpacity: 0.1,
    shadowRadius: 24,
    elevation: 8
  },
  appTitle: {
    fontSize: 32,
    fontWeight: '700',
    color: '#4338CA',
    textAlign: 'center'
  },
  subtitle: {
    textAlign: 'center',
    color: '#6B7280',
    marginBottom: 24
  },
  inputGroup: {
    marginBottom: 16
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#4B5563',
    marginBottom: 6
  },
  input: {
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: '#FFFFFF'
  },
  multilineInput: {
    minHeight: 80,
    textAlignVertical: 'top'
  },
  primaryButton: {
    backgroundColor: '#4F46E5',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 12
  },
  primaryButtonDisabled: {
    opacity: 0.6
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 16
  },
  secondaryButton: {
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#6366F1',
    alignSelf: 'flex-start',
    marginTop: 12
  },
  secondaryButtonText: {
    color: '#4338CA',
    fontWeight: '600'
  },
  demoBox: {
    backgroundColor: '#F3F4F6',
    borderRadius: 12,
    padding: 12,
    marginTop: 20
  },
  demoTitle: {
    fontWeight: '600',
    marginBottom: 4,
    color: '#374151'
  },
  demoText: {
    color: '#6B7280',
    fontSize: 12
  },
  dashboardContainer: {
    flex: 1,
    backgroundColor: '#F8FAFC'
  },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 24,
    paddingVertical: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB'
  },
  adminHeader: {
    backgroundColor: '#4338CA'
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1F2937'
  },
  headerSubtitle: {
    color: '#6B7280',
    marginTop: 4
  },
  headerTitleLight: {
    fontSize: 24,
    fontWeight: '700',
    color: '#FFFFFF'
  },
  headerSubtitleLight: {
    color: '#E0E7FF',
    marginTop: 4
  },
  logoutButton: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    backgroundColor: '#EEF2FF',
    borderRadius: 20
  },
  logoutText: {
    color: '#4338CA',
    fontWeight: '600'
  },
  logoutButtonLight: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    backgroundColor: '#6366F1',
    borderRadius: 20
  },
  logoutTextLight: {
    color: '#FFFFFF',
    fontWeight: '600'
  },
  scrollContent: {
    padding: 24,
    paddingBottom: 80
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 12,
    color: '#1F2937'
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 4
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827'
  },
  cardStatus: {
    color: '#6366F1',
    fontWeight: '600'
  },
  cardBody: {
    marginBottom: 12
  },
  cardMeta: {
    color: '#4B5563',
    marginBottom: 4
  },
  statRow: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 16
  },
  statCard: {
    flex: 1,
    backgroundColor: '#EEF2FF',
    borderRadius: 16,
    padding: 16
  },
  statTitle: {
    color: '#4B5563',
    fontWeight: '600'
  },
  statValue: {
    fontSize: 28,
    fontWeight: '700',
    color: '#1F2937',
    marginTop: 8
  },
  emptyText: {
    color: '#6B7280'
  },
  submissionItem: {
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingTop: 16,
    marginTop: 16
  },
  submissionMeta: {
    gap: 4
  },
  submissionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937'
  },
  submissionSubtitle: {
    color: '#4B5563',
    fontWeight: '500'
  },
  submissionDetail: {
    color: '#6B7280'
  },
  gradeBox: {
    marginTop: 12,
    gap: 10
  },
  groupList: {
    gap: 8
  },
  groupChip: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 16,
    paddingHorizontal: 12,
    paddingVertical: 6,
    marginRight: 8
  },
  groupChipSelected: {
    backgroundColor: '#4338CA',
    borderColor: '#4338CA'
  },
  groupChipText: {
    color: '#4B5563',
    fontWeight: '600'
  },
  groupChipTextSelected: {
    color: '#FFFFFF'
  },
  errorText: {
    textAlign: 'center',
    color: '#DC2626',
    fontWeight: '600'
  }
});

export default App;
