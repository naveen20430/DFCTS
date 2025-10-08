-- Approve select accounts for immediate testing
-- Run this after police_logins.sql if you want some accounts immediately available for testing

USE dfcts;

-- Approve 3 test accounts from different districts for immediate testing
UPDATE users SET status = 'approved' WHERE email IN (
    'shimla.city@hppolice.gov.in',     -- Inspector Rajesh Sharma (Shimla)
    'kangra.dharamshala@hppolice.gov.in', -- Inspector Mohit Kumar (Kangra) 
    'mandi.city@hppolice.gov.in'       -- Inspector Anil Chauhan (Mandi)
);

-- Log the approvals
INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES 
(1, 'User Registration Approved', 'User Management', 'Approved registration for Inspector Rajesh Sharma (shimla.city@hppolice.gov.in)', '127.0.0.1'),
(1, 'User Registration Approved', 'User Management', 'Approved registration for Inspector Mohit Kumar (kangra.dharamshala@hppolice.gov.in)', '127.0.0.1'),
(1, 'User Registration Approved', 'User Management', 'Approved registration for Inspector Anil Chauhan (mandi.city@hppolice.gov.in)', '127.0.0.1');