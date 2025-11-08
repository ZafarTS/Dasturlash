<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

require_login();
require_role('admin');

$config = require __DIR__ . '/../config/config.php';
$pdo = get_db_connection();
$messages = ['success' => [], 'error' => []];

function validate_id(string $value): bool
{
    return (bool) preg_match('/^[0-9]{6,12}$/', $value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add-student') {
        $userId = trim($_POST['user_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $groupId = (int) ($_POST['group_id'] ?? 0);

        if (!validate_id($userId)) {
            $messages['error'][] = 'Login sifatida 6-12 xonali raqam kiriting.';
        } elseif ($name === '' || $surname === '' || $password === '') {
            $messages['error'][] = 'Ism, familiya va parol kiritilishi shart.';
        } elseif ($groupId <= 0) {
            $messages['error'][] = 'Guruhni tanlang.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (user_id, name, surname, password_hash, role, group_id)
                    VALUES (:user_id, :name, :surname, :password_hash, "student", :group_id)');
                $stmt->execute([
                    'user_id' => $userId,
                    'name' => $name,
                    'surname' => $surname,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'group_id' => $groupId,
                ]);
                $messages['success'][] = 'Talaba muvaffaqiyatli qo\'shildi.';
            } catch (PDOException $e) {
                $messages['error'][] = 'Talabani qo\'shib bo\'lmadi: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'add-group') {
        $groupName = trim($_POST['group_name'] ?? '');
        if ($groupName === '') {
            $messages['error'][] = 'Guruh nomini kiriting.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO groups (name) VALUES (:name)');
                $stmt->execute(['name' => $groupName]);
                $messages['success'][] = 'Guruh yaratildi.';
            } catch (PDOException $e) {
                $messages['error'][] = 'Guruhni yaratib bo\'lmadi: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'add-subject') {
        $subjectName = trim($_POST['subject_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $practicalCount = max(1, min(24, (int) ($_POST['practical_count'] ?? 24)));

        if ($subjectName === '') {
            $messages['error'][] = 'Fan nomini kiriting.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO subjects (name, description, practical_count) VALUES (:name, :description, :count)');
                $stmt->execute([
                    'name' => $subjectName,
                    'description' => $description,
                    'count' => $practicalCount,
                ]);
                $subjectId = (int) $pdo->lastInsertId();

                $assignmentStmt = $pdo->prepare('INSERT INTO assignments (subject_id, title, sequence) VALUES (:subject_id, :title, :sequence)');
                for ($i = 1; $i <= $practicalCount; $i++) {
                    $assignmentStmt->execute([
                        'subject_id' => $subjectId,
                        'title' => 'Amaliy ish ' . $i,
                        'sequence' => $i,
                    ]);
                }
                $pdo->commit();
                $messages['success'][] = 'Fan va amaliy ishlar yaratildi.';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $messages['error'][] = 'Fan yaratishda xatolik: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'assign-subject') {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);

        if ($groupId <= 0 || $subjectId <= 0) {
            $messages['error'][] = 'Guruh va fanni tanlang.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT IGNORE INTO group_subjects (group_id, subject_id) VALUES (:group_id, :subject_id)');
                $stmt->execute(['group_id' => $groupId, 'subject_id' => $subjectId]);
                $messages['success'][] = 'Fan guruhga biriktirildi.';
            } catch (PDOException $e) {
                $messages['error'][] = 'Biriktirishda xatolik: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'grade-submission') {
        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        $grade = trim($_POST['grade'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if ($submissionId <= 0) {
            $messages['error'][] = 'Topshiriq tanlanmagan.';
        } else {
            $stmt = $pdo->prepare('UPDATE submissions SET grade = :grade, comment = :comment WHERE id = :id');
            $stmt->execute([
                'grade' => $grade !== '' ? $grade : null,
                'comment' => $comment !== '' ? $comment : null,
                'id' => $submissionId,
            ]);
            $messages['success'][] = 'Baho saqlandi.';
        }
    }
}

$filters = get_submission_filters();
$selectedGroup = isset($_GET['group']) ? (int) $_GET['group'] : null;
$selectedSubject = isset($_GET['subject']) ? (int) $_GET['subject'] : null;
$selectedAssignment = isset($_GET['assignment']) ? (int) $_GET['assignment'] : null;
$submissions = get_filtered_submissions($selectedGroup ?: null, $selectedSubject ?: null, $selectedAssignment ?: null);

$groups = $filters['groups'];
$subjects = $filters['subjects'];

$studentsStmt = $pdo->query('SELECT u.id, u.user_id, u.name, u.surname, g.name AS group_name
    FROM users u
    LEFT JOIN groups g ON g.id = u.group_id
    WHERE u.role = "student"
    ORDER BY g.name, u.surname');
$students = $studentsStmt->fetchAll();

$groupStats = get_group_overview();

$overallStmt = $pdo->query('SELECT
    (SELECT COUNT(*) FROM users WHERE role = "student") AS student_count,
    (SELECT COUNT(*) FROM subjects) AS subject_count,
    (SELECT COUNT(*) FROM assignments) AS assignment_count,
    (SELECT COUNT(*) FROM submissions) AS submission_count,
    (SELECT COUNT(*) FROM submissions WHERE grade IS NOT NULL) AS graded_count');
$overview = $overallStmt->fetch();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="#">Admin paneli</a>
        <div class="d-flex gap-2">
            <a href="/logout.php" class="btn btn-light btn-sm">Chiqish</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php foreach ($messages['success'] as $msg): ?>
        <div class="alert alert-success small"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>
    <?php foreach ($messages['error'] as $msg): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Talabalar</p>
                    <h3 class="h4 mb-0"><?= (int) $overview['student_count'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Fanlar</p>
                    <h3 class="h4 mb-0"><?= (int) $overview['subject_count'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Amaliy ishlar</p>
                    <h3 class="h4 mb-0"><?= (int) $overview['assignment_count'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Topshiriqlar</p>
                    <h3 class="h4 mb-0"><?= (int) $overview['submission_count'] ?></h3>
                    <span class="badge badge-soft badge-soft-success small"><?= (int) $overview['graded_count'] ?> baholangan</span>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills mb-4" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="submissions-tab" data-bs-toggle="pill" data-bs-target="#submissions" type="button" role="tab">Topshiriqlar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="students-tab" data-bs-toggle="pill" data-bs-target="#students" type="button" role="tab">Talabalar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="setup-tab" data-bs-toggle="pill" data-bs-target="#setup" type="button" role="tab">Sozlamalar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">Statistika</button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabContent">
        <div class="tab-pane fade show active" id="submissions" role="tabpanel" aria-labelledby="submissions-tab">
            <div class="card dashboard-card mb-3">
                <div class="card-body">
                    <form class="row g-3" method="get">
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted">Guruh</label>
                            <select name="group" class="form-select">
                                <option value="">Barchasi</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= $group['id'] ?>" <?= $selectedGroup === (int)$group['id'] ? 'selected' : '' ?>><?= htmlspecialchars($group['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted">Fan</label>
                            <select name="subject" class="form-select">
                                <option value="">Barchasi</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= $selectedSubject === (int)$subject['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subject['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted">Amaliy ish</label>
                            <select name="assignment" class="form-select">
                                <option value="">Barchasi</option>
                                <?php foreach ($filters['assignments'] as $assignment): ?>
                                    <option value="<?= $assignment['id'] ?>" <?= $selectedAssignment === (int)$assignment['id'] ? 'selected' : '' ?>><?= htmlspecialchars($assignment['subject_name'] . ' — ' . $assignment['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Filtrlash</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <p class="text-muted text-center mb-0">Tanlangan filtrlar bo'yicha topshiriqlar topilmadi.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th>Talaba</th>
                                        <th>Guruh</th>
                                        <th>Fan / ish</th>
                                        <th>Fayl</th>
                                        <th>Sana</th>
                                        <th>Baho</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold small"><?= htmlspecialchars($submission['surname'] . ' ' . $submission['name']) ?></div>
                                                <div class="text-muted small">ID: <?= htmlspecialchars($submission['user_id']) ?></div>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($submission['group_name']) ?></td>
                                            <td>
                                                <div class="small fw-semibold"><?= htmlspecialchars($submission['subject_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($submission['assignment_title']) ?></div>
                                            </td>
                                            <td><a href="/<?= htmlspecialchars($submission['file_path']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($submission['file_name']) ?></a></td>
                                            <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                            <td>
                                                <?php if ($submission['grade'] !== null): ?>
                                                    <span class="badge badge-soft badge-soft-success"><?= htmlspecialchars($submission['grade']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-soft badge-soft-warning">Kutilmoqda</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#gradeModal<?= $submission['id'] ?>">Baholash</button>

                                                <div class="modal fade" id="gradeModal<?= $submission['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="post">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Baholash</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="grade-submission">
                                                                    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Baho</label>
                                                                        <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($submission['grade'] ?? '') ?>" placeholder="A, 85 yoki boshqa format">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Izoh</label>
                                                                        <textarea name="comment" class="form-control" rows="3" placeholder="Talabaga qayd"><?= htmlspecialchars($submission['comment'] ?? '') ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                                                                    <button type="submit" class="btn btn-primary">Saqlash</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Talaba qo'shish</h2>
                            <form method="post">
                                <input type="hidden" name="action" value="add-student">
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Talaba ID</label>
                                    <input type="text" name="user_id" class="form-control form-control-sm" required placeholder="1234567890">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Ism</label>
                                    <input type="text" name="name" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Familiya</label>
                                    <input type="text" name="surname" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Parol</label>
                                    <input type="text" name="password" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Guruh</label>
                                    <select name="group_id" class="form-select form-select-sm" required>
                                        <option value="">Tanlang</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="btn btn-primary btn-sm w-100">Saqlash</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Talabalar ro'yxati</h2>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="text-muted small">
                                        <tr>
                                            <th>Talaba</th>
                                            <th>Guruh</th>
                                            <th>ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['surname'] . ' ' . $student['name']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($student['group_name'] ?? '—') ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($student['user_id']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="setup" role="tabpanel" aria-labelledby="setup-tab">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Guruh yaratish</h2>
                            <form method="post">
                                <input type="hidden" name="action" value="add-group">
                                <label class="form-label small text-muted">Guruh nomi</label>
                                <input type="text" name="group_name" class="form-control form-control-sm mb-3" placeholder="IT-21" required>
                                <button class="btn btn-outline-primary btn-sm w-100">Yaratish</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Fan yaratish</h2>
                            <form method="post">
                                <input type="hidden" name="action" value="add-subject">
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Fan nomi</label>
                                    <input type="text" name="subject_name" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Izoh</label>
                                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Fan haqida qisqa ma'lumot"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Amaliy ishlar soni</label>
                                    <input type="number" name="practical_count" class="form-control form-control-sm" value="24" min="1" max="24">
                                </div>
                                <button class="btn btn-outline-primary btn-sm w-100">Saqlash</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Fan biriktirish</h2>
                            <form method="post">
                                <input type="hidden" name="action" value="assign-subject">
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Guruh</label>
                                    <select name="group_id" class="form-select form-select-sm" required>
                                        <option value="">Tanlang</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Fan</label>
                                    <select name="subject_id" class="form-select form-select-sm" required>
                                        <option value="">Tanlang</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="btn btn-outline-primary btn-sm w-100">Biriktirish</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h2 class="h6 mb-3">Guruhlar bo'yicha statistika</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="text-muted small">
                                <tr>
                                    <th>Guruh</th>
                                    <th>Talabalar</th>
                                    <th>Fanlar</th>
                                    <th>Amaliy ishlar</th>
                                    <th>Yuborilgan</th>
                                    <th>Baholangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupStats as $stat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stat['name']) ?></td>
                                        <td><?= (int) $stat['student_count'] ?></td>
                                        <td><?= (int) $stat['subject_count'] ?></td>
                                        <td><?= (int) $stat['assignment_count'] ?></td>
                                        <td><?= (int) $stat['submission_count'] ?></td>
                                        <td><?= (int) $stat['graded_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
