<?php
session_start();

// Simple authentication class
class SimpleAuth {
    // Predefined admin credentials
    private static $admin_users = [
        'admin' => 'AutoOutreach2024!',
        'super_admin' => 'SecureAdmin@2024'
    ];
    
    public static function login($username, $password) {
        if (isset(self::$admin_users[$username]) && 
            password_verify($password, password_hash(self::$admin_users[$username], PASSWORD_DEFAULT)) ||
            (isset(self::$admin_users[$username]) && self::$admin_users[$username] === $password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }
    
    public static function logout() {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['login_time']);
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public static function getUsername() {
        return $_SESSION['admin_username'] ?? null;
    }
}
?>