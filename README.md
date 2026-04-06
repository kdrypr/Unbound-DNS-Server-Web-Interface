# Unbound DNS Server Web Interface

A secure web-based management interface for [Unbound DNS Server](https://nlnetlabs.nl/projects/unbound/about/), built with PHP 8.x and Bootstrap.

![License](https://img.shields.io/badge/license-MIT-green)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)

## Features

- **DNS Record Management** — Add, edit, delete DNS records (A, AAAA, MX, CNAME, TXT) with server-side pagination and instant search
- **DNS Query** — Query the DNS server directly from the interface via `dig`
- **Apply Rules** — Reload Unbound service with one click; change detection indicator shows unapplied edits
- **Multi-User Support** — Admin and User roles with full user management (create, edit, disable, delete)
- **Audit Logging** — Every action is logged with username, IP, timestamp, and details
- **SIEM Integration** — CEF-formatted syslog output (facility `local6`) with configurable forwarding to QRadar, Splunk, ELK, etc.
- **SIEM Config UI** — Edit rsyslog forwarding rules and send test events directly from the web interface
- **Responsive UI** — Built on Sneat Bootstrap template with corporate color theming

## Security

- CSRF token protection on all POST requests
- Bcrypt password hashing (cost 12)
- Session fingerprinting (User-Agent + IP) and 30-minute timeout
- Input validation on all DNS fields (FQDN, IP, record type)
- No `shell_exec` for file operations — uses PHP native `str_replace` / `file_put_contents`
- Login rate limiting (10 attempts per 15 minutes per IP)
- PDO prepared statements for all database queries
- Session regeneration every 5 minutes

## Requirements

- PHP 8.1+
- MySQL / MariaDB
- Unbound DNS Server
- Apache with mod_php (or nginx + php-fpm)
- rsyslog (for SIEM integration)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/kdrypr/Unbound-DNS-Server-Web-Interface.git /var/www/unbound
```

### 2. Create the database

```sql
CREATE DATABASE unbound CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'dns_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON unbound.* TO 'dns_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Import the schema

```sql
USE unbound;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    email VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
);

-- Create initial admin user (password: changeme)
INSERT INTO users (username, password, role) VALUES (
    'admin',
    '$2y$12$LJ3m4yPnMDAVE8QKR1ZBXOYzVqCvU7jmJQYYhKQFx0B0YcOKTNdLa',
    'admin'
);
```

### 4. Configure the database connection

```bash
cp config/db.php.example config/db.php
# Edit config/db.php with your database credentials
```

### 5. Set file permissions

```bash
# Unbound config must be writable by the web server user
sudo chown root:www-data /etc/unbound/host_entries.conf
sudo chmod 664 /etc/unbound/host_entries.conf

# Hash file
touch filehash.txt
chmod 664 filehash.txt
```

### 6. Configure sudoers

```bash
# Allow web server to reload unbound and manage rsyslog
echo 'www-data ALL=(ALL) NOPASSWD: /usr/sbin/service unbound reload' | sudo tee /etc/sudoers.d/unbound-web
echo 'www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart rsyslog' | sudo tee -a /etc/sudoers.d/unbound-web
sudo chmod 440 /etc/sudoers.d/unbound-web
```

> **Note:** Replace `www-data` with your Apache user if different (check with `ps aux | grep apache`).

### 7. Apache Virtual Host

```apache
<VirtualHost *:443>
    ServerName dns.example.com
    DocumentRoot /var/www/unbound
    SSLEngine on
    SSLCertificateFile /path/to/cert.crt
    SSLCertificateKeyFile /path/to/cert.key
</VirtualHost>
```

### 8. SIEM Setup (Optional)

The rsyslog configuration is created automatically at `/etc/rsyslog.d/60-unbound-dns-panel.conf`. You can configure forwarding rules directly from the SIEM Config page in the web interface, or edit the file manually:

```bash
# Example: Forward to SIEM via TCP
local6.*    @@siem-server.example.com:514
```

## CEF Log Format

All events are logged in CEF (Common Event Format):

```
CEF:0|InfinitumIT|UnboundDNSPanel|1.0|dns_add|DNS Record Added|3|src=10.0.0.1 suser=admin suid=1 msg=Added A record: test.example.com -> 192.168.1.1 dhost=dns
```

## File Structure

```
├── index.php              # Login page
├── dns.php                # DNS records (main dashboard)
├── users.php              # User management (admin only)
├── logs.php               # Audit log viewer
├── siem.php               # SIEM configuration (admin only)
├── profile.php            # Password change
├── controller.php         # API endpoint (all AJAX requests)
├── login.php              # Authentication handler
├── logout.php             # Session termination
├── config/
│   ├── db.php             # Database credentials (not in repo)
│   ├── db.php.example     # Database config template
│   └── security.php       # Session, CSRF, role helpers
├── core/
│   ├── functions.php      # Business logic
│   ├── header.php         # HTML head
│   ├── sidebar.php        # Navigation menu
│   └── footer.php         # Footer and shared JS
├── js/
│   └── unbound.js         # DNS page JavaScript
└── assets/                # Bootstrap theme, CSS, vendor libs
```

## License

MIT

## Author

[InfinitumIT](https://infinitumit.com.tr)
