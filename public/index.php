<?php
require_once __DIR__ . '/../lib/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($userId === '' || $password === '') {
        $error = 'Login va parol kiritilishi shart.';
    } elseif (!authenticate($userId, $password)) {
        $error = 'Noto\'g\'ri login yoki parol.';
    } else {
        $user = current_user();
        if ($user['role'] === 'admin') {
            header('Location: /admin.php');
        } else {
            header('Location: /student.php');
        }
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasturlash amaliy ishlari - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="icon-circle mb-3">
                                <span class="icon">📁</span>
                            </div>
                            <h1 class="h4 fw-bold">Dasturlash amaliy ishlari</h1>
                            <p class="text-muted small">Tizimga kirish uchun login va parolni kiriting</p>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="user_id" class="form-label small text-uppercase text-muted">Login (ID)</label>
                                <input type="text" class="form-control" id="user_id" name="user_id" required placeholder="1234567890" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label small text-uppercase text-muted">Parol</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">Kirish</button>
                        </form>
                        <div class="mt-4 text-center text-muted small">
                            <p>Ro'yxatdan o'tkazish faqat admin tomonidan amalga oshiriladi.</p>
                            <p>Login sifatida 10 xonali talaba IDsi ishlatiladi.</p>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3">© <?= date('Y') ?> Dasturlash kafedrasi</p>
            </div>
        </div>
    </div>
</body>
</html>
