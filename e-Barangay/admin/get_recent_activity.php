<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$activities = [];

// Recent user registrations
$recentUsers = $con->query("
    SELECT fullname, date_registered, role 
    FROM users 
    ORDER BY date_registered DESC 
    LIMIT 3
");

if ($recentUsers) {
    while ($row = $recentUsers->fetch_assoc()) {
        $icon = $row['role'] === 'resident' ? '👤' : ($row['role'] === 'secretary' ? '📝' : '👑');
        $activities[] = [
            'icon' => $icon,
            'user' => $row['fullname'],
            'action' => 'registered as ' . $row['role'],
            'time' => timeAgo($row['date_registered'])
        ];
    }
}

// Recent clearance requests
$recentClearances = $con->query("
    SELECT u.fullname, cr.request_date 
    FROM clearance_requests cr
    JOIN users u ON cr.resident_id = u.user_id
    ORDER BY cr.request_date DESC 
    LIMIT 3
");

if ($recentClearances) {
    while ($row = $recentClearances->fetch_assoc()) {
        $activities[] = [
            'icon' => '📄',
            'user' => $row['fullname'],
            'action' => 'requested a clearance',
            'time' => timeAgo($row['request_date'])
        ];
    }
}

// Recent cases
$recentCases = $con->query("
    SELECT u.fullname, c.case_date, c.case_type
    FROM cases c
    JOIN users u ON c.resident_id = u.user_id
    ORDER BY c.case_date DESC 
    LIMIT 2
");

if ($recentCases) {
    while ($row = $recentCases->fetch_assoc()) {
        $activities[] = [
            'icon' => '📋',
            'user' => $row['fullname'],
            'action' => 'reported a ' . $row['case_type'] . ' case',
            'time' => timeAgo($row['case_date'])
        ];
    }
}

// Sort by time (most recent first)
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Limit to 8 most recent
$activities = array_slice($activities, 0, 8);

echo json_encode($activities);

// Helper function to convert timestamp to relative time
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>