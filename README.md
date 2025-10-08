# Digital Forensic Crime Tracking System (DFCTS)

A comprehensive web-based system for managing forensic investigations across Himachal Pradesh police stations and forensic laboratories.

## 🚀 Features

### 👨‍💼 Admin Dashboard
- **User Management**: Approve/reject police station registrations
- **Case Assignment**: Assign forensic cases to officers
- **Priority Management**: Set case priorities (low/medium/high/urgent)
- **System Monitoring**: Real-time dashboard with statistics
- **Audit Logging**: Complete activity tracking

### 👮‍♂️ Police Dashboard  
- **FIR Submission**: Submit cases requiring forensic analysis
- **Case Tracking**: Monitor forensic case progress
- **Lab Assignment**: Select appropriate forensic laboratories
- **Report Download**: Access completed forensic reports
- **Status Updates**: Real-time case status tracking

### 🔬 Forensic Dashboard (NEW!)
- **Case Management**: View assigned forensic cases
- **Status Updates**: Update case progress and findings
- **Report Generation**: Upload final forensic reports
- **Lab Monitoring**: Track lab conditions and equipment
- **Performance Metrics**: View completion rates and quality scores

### 🛡️ Security Features
- **Admin Approval System**: Police accounts require approval
- **CSRF Protection**: All forms protected against attacks
- **Role-based Access Control**: Admin, Police, Forensic roles
- **Password Hashing**: bcrypt hashing for all passwords
- **Session Management**: Secure session handling with timeouts
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## 🛠️ Technology Stack

- **Backend**: PHP 8.x with PDO
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS with Bootstrap components
- **Icons**: Font Awesome 6

## 📋 Prerequisites

- Apache/Nginx web server
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer (for PHPMailer if using email features)

## 💾 Installation

### 🚀 Method 1: PHP Setup Scripts (Recommended)

1. **Access Setup Interface**:
   ```
   http://localhost/dfcts/setup_index.php
   ```

2. **Run Setup Scripts** (3 clicks total):
   - Step 1: **Setup Database** → Creates complete database structure
   - Step 2: **Add Police Accounts** → Creates 19 police station accounts  
   - Step 3: **Auto Approve** → Approves 3 test accounts (optional)

3. **Start Testing**:
   - Admin: `admin@dfcts.gov.in` / `password123`
   - Police: `shimla.city@hppolice.gov.in` / `password123`
   - Forensic: `rajesh@sfsl-junga.gov.in` / `password123`

### 📜 Method 2: Manual Database Setup

1. Create a MySQL database named `dfcts`:
```sql
CREATE DATABASE dfcts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p dfcts < database.sql
mysql -u your_username -p dfcts < police_logins.sql  # Optional: Add police accounts
mysql -u your_username -p dfcts < approve_test_accounts.sql  # Optional: Approve test accounts
```

### 2. Configuration

1. Update database credentials in `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dfcts');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. File Permissions

Ensure the following directories are writable:
```bash
chmod 755 uploads/
chmod 755 assets/
```

### 4. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

## 👥 Default Users

The system comes with pre-configured accounts:

### Default Admin
- **Email**: `admin@dfcts.gov.in`
- **Password**: `password123`
- **Role**: System Administrator

### Forensic Officers (Pre-approved)
- **SFSL Junga**: `rajesh@sfsl-junga.gov.in`
- **RFSL Dharamshala**: `priya@rfsl-dharamshala.gov.in`  
- **RFSL Mandi**: `amit@rfsl-mandi.gov.in`
- **Password**: `password123` (for all)

### Police Stations (Pending Approval)
- **19 stations** across Himachal Pradesh districts
- **Status**: Pending admin approval (except 3 test accounts)
- **Password**: `password123` (for all)
- **Test Account**: `shimla.city@hppolice.gov.in`

⚠️ **Important**: All police accounts require admin approval before login!

📚 **Documentation**: See [LOGIN_CREDENTIALS.txt](LOGIN_CREDENTIALS.txt) for complete account details

## 🔧 Configuration Options

### Email Setup (Optional)

To enable email notifications, configure PHPMailer in `includes/functions.php`:

1. Install PHPMailer via Composer:
```bash
composer require phpmailer/phpmailer
```

2. Update email configuration in `sendEmail()` function:
```php
$mail->Host = 'your-smtp-server.com';
$mail->Username = 'your-email@domain.com';
$mail->Password = 'your-email-password';
```

### Security Configuration

1. **SSL Certificate**: Configure HTTPS for production
2. **Session Security**: Update session settings in `includes/auth.php`
3. **Database Security**: Use restricted database user with minimal privileges
4. **File Upload**: Configure upload limits in `includes/functions.php`

## 📱 Usage Guide

### For Police Stations

1. **Registration**: Use the registration form to request access
2. **Login**: Access the police dashboard after approval
3. **Submit FIR**: Use the FIR submission form
4. **Track Cases**: Monitor forensic case progress

### For Forensic Admin

1. **User Management**: Approve/reject police station registrations
2. **Case Assignment**: Assign cases to appropriate forensic officers
3. **Status Monitoring**: Track all forensic cases across labs
4. **Priority Management**: Set case priorities

### For Forensic Officers

1. **Case Review**: View assigned forensic cases
2. **Status Updates**: Update investigation progress
3. **Report Upload**: Upload completed forensic reports
4. **Case Notes**: Add investigation notes and comments

## 🔍 System Workflow

```
Police Station → Submit FIR → Admin Review → Lab Assignment → Forensic Analysis → Report Generation → Case Closure
```

## 📊 Database Schema

### Core Tables
- `users`: System users (police, admin, forensic officers)
- `firs`: FIR records
- `forensic_cases`: Forensic investigation cases
- `audit_logs`: System activity logs

### Relationships
- FIRs belong to police users
- Forensic cases link to FIRs and assigned officers
- Audit logs track all system activities

## 🚨 Security Features

- **Password Hashing**: Using PHP's `password_hash()`
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: PDO prepared statements
- **Session Management**: Secure session handling with timeout
- **Input Validation**: Server-side validation for all inputs
- **Role-Based Access**: Strict role-based access control

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/db.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied**
   - Check file/folder permissions
   - Ensure web server has read/write access

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions

4. **Email Not Working**
   - Check email configuration in `includes/functions.php`
   - Verify SMTP settings
   - Check firewall/network restrictions

### Debug Mode

To enable debug mode, add to `includes/db.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📈 Performance Optimization

1. **Database Indexing**: Indexes are created for frequently queried columns
2. **Query Optimization**: Use LIMIT for large datasets
3. **Caching**: Implement PHP OpCode caching
4. **Asset Optimization**: Minify CSS/JS files for production

## 🔄 Updates and Maintenance

### Regular Maintenance
- Review audit logs monthly
- Update user passwords regularly
- Monitor database performance
- Backup database weekly

### Version Updates
1. Backup database and files
2. Test updates on staging environment
3. Apply updates during maintenance window
4. Monitor system after updates

## 📞 Support

For technical support or feature requests:
- **Email**: support@dfcts.gov.in
- **Phone**: 1800-XXX-XXXX

## 📄 License

This software is developed for the Himachal Pradesh Police Department.
All rights reserved.

---

**Digital Forensic Crime Tracking System (DFCTS)**  
*Enhancing forensic investigation efficiency across Himachal Pradesh*