<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

require_login();
require_role('student');

$config = require __DIR__ . '/../config/config.php';
$pdo = get_db_connection();
$user = current_user();
$groupId = $user['group_id'] ? (int) $user['group_id'] : null;
$groupName = 'Biriktirilmagan';
if ($groupId) {
    $stmt = $pdo->prepare('SELECT name FROM groups WHERE id = :id');
    $stmt->execute(['id' => $groupId]);
    $groupName = $stmt->fetchColumn() ?: $groupName;
}
$messages = ['success' => [], 'error' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update-profile') {
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '' || $surname === '') {
            $messages['error'][] = 'Ism va familiya kiritilishi shart.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = :name, surname = :surname, phone = :phone WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'surname' => $surname,
                'phone' => $phone !== '' ? $phone : null,
                'id' => $user['id'],
            ]);
            $messages['success'][] = 'Profil ma\'lumotlari yangilandi.';
            $user = current_user();
        }
    }

    if ($action === 'change-password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            $messages['error'][] = 'Yangi parol kiritilishi shart.';
        } elseif ($newPassword !== $confirmPassword) {
            $messages['error'][] = 'Yangi parollar mos emas.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $messages['error'][] = 'Joriy parol noto\'g\'ri.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :password WHERE id = :id');
            $stmt->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
            $messages['success'][] = 'Parol muvaffaqiyatli o\'zgartirildi.';
        }
    }

    if ($action === 'update-avatar' && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $messages['error'][] = 'Avatar yuklashda xatolik yuz berdi.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $messages['error'][] = 'Faqat JPG yoki PNG formatdagi rasmlarni yuklash mumkin.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $messages['error'][] = 'Avatar hajmi 2 MB dan oshmasligi kerak.';
            } else {
                $uploadDir = rtrim($config['upload_dir'], '/') . '/avatars';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $fileName = $user['user_id'] . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . '/' . $fileName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $relative = 'uploads/avatars/' . $fileName;
                    $stmt = $pdo->prepare('UPDATE users SET avatar_path = :avatar WHERE id = :id');
                    $stmt->execute(['avatar' => $relative, 'id' => $user['id']]);
                    $messages['success'][] = 'Avatar yangilandi.';
                    $user = current_user();
                } else {
                    $messages['error'][] = 'Faylni saqlab bo\'lmadi.';
                }
            }
        }
    }

    if ($action === 'submit-assignment' && isset($_FILES['file'])) {
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $file = $_FILES['file'];

        if ($assignmentId <= 0) {
            $messages['error'][] = 'Amaliy ish tanlanishi shart.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $messages['error'][] = 'Fayl yuklashda xatolik yuz berdi.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $config['allowed_extensions'], true)) {
                $messages['error'][] = 'Ruxsat etilgan fayl turlari: PPT, PPTX, DOC, DOCX, ZIP, PY, PDF.';
            } elseif ($file['size'] > $config['max_file_size']) {
                $messages['error'][] = 'Fayl hajmi 10 MB dan oshmasligi kerak.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM assignments a
                    INNER JOIN subjects s ON s.id = a.subject_id
                    INNER JOIN group_subjects gs ON gs.subject_id = s.id
                    WHERE a.id = :assignment_id AND gs.group_id = :group_id');
                $stmt->execute([
                    'assignment_id' => $assignmentId,
                    'group_id' => $user['group_id'],
                ]);

                if ((int) $stmt->fetchColumn() === 0) {
                    $messages['error'][] = 'Bu amaliy ish sizning guruhingiz uchun mavjud emas.';
                } else {
                    $uploadDir = rtrim($config['upload_dir'], '/') . '/submissions/' . $user['user_id'];
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $fileName = $assignmentId . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                    $targetPath = $uploadDir . '/' . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $relativePath = 'uploads/submissions/' . $user['user_id'] . '/' . $fileName;

                        $stmt = $pdo->prepare('INSERT INTO submissions (student_id, assignment_id, file_name, file_path, submitted_at)
                            VALUES (:student_id, :assignment_id, :file_name, :file_path, NOW())
                            ON DUPLICATE KEY UPDATE file_name = VALUES(file_name), file_path = VALUES(file_path), submitted_at = NOW(), grade = NULL, comment = NULL');
                        $stmt->execute([
                            'student_id' => $user['id'],
                            'assignment_id' => $assignmentId,
                            'file_name' => $file['name'],
                            'file_path' => $relativePath,
                        ]);

                        $messages['success'][] = 'Fayl muvaffaqiyatli yuborildi.';
                    } else {
                        $messages['error'][] = 'Faylni saqlab bo\'lmadi.';
                    }
                }
            }
        }
    }
}

$assignments = $groupId ? get_student_assignments($groupId) : [];
$progress = get_student_progress((int)$user['id']);

$stmt = $pdo->prepare('SELECT s.id, a.title, subj.name AS subject_name, s.file_name, s.submitted_at, s.grade, s.comment
    FROM submissions s
    JOIN assignments a ON a.id = s.assignment_id
    JOIN subjects subj ON subj.id = a.subject_id
    WHERE s.student_id = :student_id
    ORDER BY s.submitted_at DESC');
$stmt->execute(['student_id' => $user['id']]);
$submissionHistory = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talaba paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="#">Talaba paneli</a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-white small text-end">
                <div class="fw-semibold"><?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?></div>
                <div class="text-white-50">ID: <?= htmlspecialchars($user['user_id']) ?></div>
            </div>
            <a href="/logout.php" class="btn btn-outline-light btn-sm">Chiqish</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="<?= $user['avatar_path'] ? '/' . htmlspecialchars($user['avatar_path']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] . '+' . $user['surname']) ?>" alt="Avatar" class="avatar-preview">
                        <div>
                            <h2 class="h5 mb-1">Profil</h2>
                            <p class="text-muted small mb-0">Guruh: <?= htmlspecialchars($groupName) ?></p>
                        </div>
                    </div>
                    <?php foreach ($messages['success'] as $msg): ?>
                        <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                    <?php foreach ($messages['error'] as $msg): ?>
                        <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>

                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="update-profile">
                        <div class="mb-2">
                            <label class="form-label small text-muted">Ism</label>
                            <input type="text" class="form-control form-control-sm" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Familiya</label>
                            <input type="text" class="form-control form-control-sm" name="surname" value="<?= htmlspecialchars($user['surname']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Telefon (ixtiyoriy)</label>
                            <input type="tel" class="form-control form-control-sm" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="998901234567">
                        </div>
                        <button class="btn btn-primary btn-sm w-100">Saqlash</button>
                    </form>

                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="change-password">
                        <div class="mb-2">
                            <label class="form-label small text-muted">Joriy parol</label>
                            <input type="password" class="form-control form-control-sm" name="current_password" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Yangi parol</label>
                            <input type="password" class="form-control form-control-sm" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Yangi parolni tasdiqlang</label>
                            <input type="password" class="form-control form-control-sm" name="confirm_password" required>
                        </div>
                        <button class="btn btn-outline-primary btn-sm w-100">Parolni o'zgartirish</button>
                    </form>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update-avatar">
                        <label class="form-label small text-muted">Avatarni yangilash</label>
                        <input type="file" class="form-control form-control-sm mb-2" name="avatar" accept="image/png,image/jpeg">
                        <button class="btn btn-outline-secondary btn-sm w-100">Yuklash</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <p class="text-muted text-uppercase small mb-1">Umumiy</p>
                            <h3 class="h4 mb-0"><?= $progress['total'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <p class="text-muted text-uppercase small mb-1">Yuborilgan</p>
                            <h3 class="h4 mb-0"><?= $progress['submitted'] ?></h3>
                            <span class="badge badge-soft badge-soft-warning small"><?= $progress['completion_percent'] ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <p class="text-muted text-uppercase small mb-1">Baholangan</p>
                            <h3 class="h4 mb-0"><?= $progress['graded'] ?></h3>
                            <span class="badge badge-soft badge-soft-success small"><?= $progress['graded_percent'] ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <p class="text-muted text-uppercase small mb-1">Qolgan ishlar</p>
                            <h3 class="h4 mb-0"><?= max($progress['total'] - $progress['submitted'], 0) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Amaliy ishni yuborish</h2>
                        <span class="badge badge-soft badge-soft-secondary">Fayl hajmi 10 MB gacha</span>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="row g-2">
                        <input type="hidden" name="action" value="submit-assignment">
                        <div class="col-12">
                            <label class="form-label small text-muted">Amaliy ish</label>
                            <select name="assignment_id" class="form-select" required>
                                <option value="">Amaliy ishni tanlang</option>
                                <?php foreach ($assignments as $assignment): ?>
                                    <option value="<?= $assignment['id'] ?>">
                                        <?= htmlspecialchars($assignment['subject_name'] . ' — ' . $assignment['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Fayl</label>
                            <input type="file" name="file" class="form-control" required accept=".ppt,.pptx,.doc,.docx,.zip,.py,.pdf">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">Yuborish</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Topshiriqlar tarixi</h2>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="text-muted small">
                                <tr>
                                    <th>Fan</th>
                                    <th>Amaliy ish</th>
                                    <th>Fayl</th>
                                    <th>Yuborilgan</th>
                                    <th>Holat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($submissionHistory)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Hozircha topshiriq yuborilmagan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($submissionHistory as $submission): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($submission['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($submission['title']) ?></td>
                                            <td>
                                                <a href="/<?= htmlspecialchars($submission['file_path']) ?>" class="small" target="_blank" rel="noopener">
                                                    <?= htmlspecialchars($submission['file_name']) ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                            <td>
                                                <?php if ($submission['grade'] !== null): ?>
                                                    <span class="badge badge-soft badge-soft-success"><?= htmlspecialchars($submission['grade']) ?></span>
                                                    <?php if ($submission['comment']): ?>
                                                        <div class="small text-muted mt-1"><?= htmlspecialchars($submission['comment']) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-soft badge-soft-warning">Ko'rib chiqilmoqda</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</html>
