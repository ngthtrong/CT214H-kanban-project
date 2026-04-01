<?php
/**
 * Logout Handler
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

// Log out user
logoutUser();

// Set flash message
flash('Bạn đã đăng xuất thành công.', 'info');

// Redirect to login page
redirect('login.php');
