<?php
/**
 * Root redirect
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: public/dashboard.php');
} else {
    header('Location: public/login.php');
}
exit;
