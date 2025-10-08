# 👨‍💼 Admin Approval Process Guide - DFCTS

## 🚀 Quick Setup

### 1. Database Setup
```bash
# Run these commands in order:
mysql -u root -p dfcts < database.sql
mysql -u root -p dfcts < police_logins.sql
mysql -u root -p dfcts < approve_test_accounts.sql  # Optional: for immediate testing
```

### 2. Access Admin Dashboard
- URL: `http://localhost/dfcts/login.php`
- Email: `admin@dfcts.gov.in`
- Password: `password123`

## 📋 Admin Dashboard Features

### User Approval Workflow
1. **Login as Admin** → You'll see the admin dashboard
2. **Check Statistics** → "Pending Users" count shows how many need approval
3. **Review Pending Registrations** → Left panel shows all pending police accounts
4. **Approve/Reject** → Click ✅ (Approve) or ❌ (Reject) buttons

### What You'll See:
- **Pending Registrations Section** (Yellow border cards)
  - Officer Name and Police Station
  - District and Contact Information  
  - Registration Date
  - Action buttons (Approve/Reject)

## 🏛️ Police Account Management

### Default Status: **PENDING** ⏳
- All new police registrations start as "pending"
- They cannot login until admin approves them
- This ensures security and proper verification

### Pre-Approved Accounts ✅
For immediate testing, these 3 accounts are pre-approved:

| Officer | Email | District |
|---------|-------|----------|
| Inspector Rajesh Sharma | shimla.city@hppolice.gov.in | Shimla |
| Inspector Mohit Kumar | kangra.dharamshala@hppolice.gov.in | Kangra |
| Inspector Anil Chauhan | mandi.city@hppolice.gov.in | Mandi |

**Password for all accounts:** `password123`

## 🔧 Admin Functions

### 1. User Management
- **Approve Users:** Change status from 'pending' to 'approved'
- **Reject Users:** Change status to 'rejected'
- **View All Users:** See complete user database

### 2. Case Management  
- **Assign Forensic Officers:** Assign cases to specific forensic experts
- **Update Priority:** Change case priority (low/medium/high/urgent)
- **Monitor Progress:** Track all forensic cases

### 3. System Monitoring
- **Dashboard Statistics:** Real-time system metrics
- **Audit Logs:** Complete activity tracking
- **Recent Activities:** Latest system events

## 🎯 Testing Workflow

### Step 1: Admin Login & Approval
1. Login as admin
2. See 16 pending police registrations
3. Approve a few accounts (click ✅)
4. Check that "Pending Users" count decreases

### Step 2: Police Login Test  
1. Logout from admin
2. Try logging in with a **pending account** → Should be blocked
3. Try logging in with an **approved account** → Should work
4. Access police dashboard successfully

### Step 3: End-to-End Test
1. Police officer submits FIR
2. Admin assigns to forensic lab  
3. Forensic officer receives case
4. Complete forensic analysis workflow

## 🚨 Important Security Notes

- **Only approved users can login**
- **All activities are logged** in audit_logs table
- **CSRF protection** on all forms
- **Session timeout** after 30 minutes
- **Password hashing** with PHP password_hash()

## 📞 Quick Reference

### Admin Credentials
- **Email:** admin@dfcts.gov.in
- **Password:** password123
- **Role:** System Administrator

### Test Police Accounts (Pre-approved)
- **Email:** shimla.city@hppolice.gov.in
- **Email:** kangra.dharamshala@hppolice.gov.in  
- **Email:** mandi.city@hppolice.gov.in
- **Password:** password123 (for all)

### Database Tables
- **users:** User accounts and status
- **firs:** Police FIR submissions
- **forensic_cases:** Lab case assignments
- **audit_logs:** Activity tracking

---

## 🎉 Success Indicators

✅ **Admin dashboard loads with pending registrations**  
✅ **Approve/reject buttons work correctly**  
✅ **Approved users can login to police dashboard**  
✅ **Pending users cannot login**  
✅ **All activities logged in audit trail**

---

**Need Help?** Check the LOGIN_CREDENTIALS.txt file for complete account details!