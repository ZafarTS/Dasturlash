<?php
require_once __DIR__ . '/db.php';

function get_student_assignments(int $groupId): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT a.id, a.title, a.due_date, a.sequence, s.name as subject_name
        FROM assignments a
        INNER JOIN subjects s ON s.id = a.subject_id
        INNER JOIN group_subjects gs ON gs.subject_id = s.id
        WHERE gs.group_id = :group_id
        ORDER BY s.name, a.sequence');
    $stmt->execute(['group_id' => $groupId]);
    return $stmt->fetchAll();
}

function get_student_progress(int $studentId): array
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assignments a
        INNER JOIN group_subjects gs ON gs.subject_id = a.subject_id
        INNER JOIN users u ON u.group_id = gs.group_id
        WHERE u.id = :student_id');
    $stmt->execute(['student_id' => $studentId]);
    $totalAssignments = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE student_id = :student_id');
    $stmt->execute(['student_id' => $studentId]);
    $submitted = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE student_id = :student_id AND grade IS NOT NULL');
    $stmt->execute(['student_id' => $studentId]);
    $graded = (int) $stmt->fetchColumn();

    return [
        'submitted' => $submitted,
        'graded' => $graded,
        'total' => $totalAssignments,
        'completion_percent' => $totalAssignments ? round(($submitted / $totalAssignments) * 100) : 0,
        'graded_percent' => $totalAssignments ? round(($graded / $totalAssignments) * 100) : 0,
    ];
}

function get_group_overview(): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT g.id, g.name,
                   COUNT(DISTINCT u.id) AS student_count,
                   COUNT(DISTINCT gs.subject_id) AS subject_count,
                   COUNT(DISTINCT a.id) AS assignment_count,
                   COUNT(DISTINCT CASE WHEN su.group_id = g.id THEN s.id END) AS submission_count,
                   COUNT(DISTINCT CASE WHEN su.group_id = g.id AND s.grade IS NOT NULL THEN s.id END) AS graded_count
            FROM groups g
            LEFT JOIN users u ON u.group_id = g.id AND u.role = "student"
            LEFT JOIN group_subjects gs ON gs.group_id = g.id
            LEFT JOIN assignments a ON a.subject_id = gs.subject_id
            LEFT JOIN submissions s ON s.assignment_id = a.id
            LEFT JOIN users su ON su.id = s.student_id
            GROUP BY g.id, g.name
            ORDER BY g.name';
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function get_submission_filters(): array
{
    $pdo = get_db_connection();
    return [
        'groups' => $pdo->query('SELECT id, name FROM groups ORDER BY name')->fetchAll(),
        'subjects' => $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll(),
        'assignments' => $pdo->query('SELECT a.id, a.title, s.name AS subject_name FROM assignments a JOIN subjects s ON s.id = a.subject_id ORDER BY s.name, a.sequence')->fetchAll(),
    ];
}

function get_filtered_submissions(?int $groupId, ?int $subjectId, ?int $assignmentId): array
{
    $pdo = get_db_connection();
    $conditions = [];
    $params = [];

    if ($groupId) {
        $conditions[] = 'u.group_id = :group_id';
        $params['group_id'] = $groupId;
    }

    if ($subjectId) {
        $conditions[] = 'a.subject_id = :subject_id';
        $params['subject_id'] = $subjectId;
    }

    if ($assignmentId) {
        $conditions[] = 's.assignment_id = :assignment_id';
        $params['assignment_id'] = $assignmentId;
    }

    $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    $sql = "SELECT s.id, s.file_name, s.file_path, s.submitted_at, s.grade, s.comment,
                   u.name, u.surname, u.user_id, g.name AS group_name,
                   a.title AS assignment_title, subj.name AS subject_name
            FROM submissions s
            JOIN users u ON u.id = s.student_id
            JOIN groups g ON g.id = u.group_id
            JOIN assignments a ON a.id = s.assignment_id
            JOIN subjects subj ON subj.id = a.subject_id
            $where
            ORDER BY s.submitted_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
