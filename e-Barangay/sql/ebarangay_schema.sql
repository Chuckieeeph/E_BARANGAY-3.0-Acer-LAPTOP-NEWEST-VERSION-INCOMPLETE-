/*ebarangay_schema.sql*/
USE e_barangay;

/* ===================================================
   USERS TABLE — SAFE COLUMN ADD (NO DUPLICATE ERRORS)
=================================================== */

/* Add validation_status only if NOT existing */
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='users' AND COLUMN_NAME='validation_status'
)
THEN
    ALTER TABLE users 
    ADD COLUMN validation_status ENUM('pending','validated','unvalidated') 
    NOT NULL DEFAULT 'pending';
END IF;

/* Add valid_id only if NOT existing */
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='users' AND COLUMN_NAME='valid_id'
)
THEN
    ALTER TABLE users
    ADD COLUMN valid_id VARCHAR(255) DEFAULT NULL;
END IF;

/* Add date_registered if missing */
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='users' AND COLUMN_NAME='date_registered'
)
THEN
    ALTER TABLE users
    ADD COLUMN date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
END IF;

/* Modify role ENUM safely */
ALTER TABLE users
MODIFY COLUMN role ENUM('admin','secretary','resident') NOT NULL DEFAULT 'resident';


/* ===================================================
   RESIDENTS TABLE CHECK
=================================================== */

CREATE TABLE IF NOT EXISTS residents (
  resident_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  birthdate DATE NULL,
  gender VARCHAR(10) NULL,
  civil_status VARCHAR(50) NULL,
  nationality VARCHAR(50) NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;


/* ===================================================
   CLEARANCE REQUESTS — FIX FOREIGN KEYS SAFELY
=================================================== */

/* Drop FK only if it exists */
SET @fk := (
    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME='clearance_requests'
    AND COLUMN_NAME='resident_id'
    AND REFERENCED_TABLE_NAME='residents'
);

SET @sql := IF(@fk IS NOT NULL,
    CONCAT('ALTER TABLE clearance_requests DROP FOREIGN KEY ', @fk),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* Add corrected FK */
ALTER TABLE clearance_requests
ADD CONSTRAINT fk_clearance_resident
FOREIGN KEY (resident_id) REFERENCES users(user_id)
ON DELETE CASCADE;

/* Add missing columns safely */
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='clearance_requests' AND COLUMN_NAME='purpose'
)
THEN
    ALTER TABLE clearance_requests ADD COLUMN purpose TEXT;
END IF;


/* ===================================================
   CASES TABLE SAFETY
=================================================== */

CREATE TABLE IF NOT EXISTS cases (
  case_id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT NOT NULL,
  case_type VARCHAR(255),
  case_title VARCHAR(255),
  case_description TEXT,
  case_date DATE,
  status ENUM('open','under investigation','closed') DEFAULT 'open',
  handled_by INT NULL,
  FOREIGN KEY (resident_id) REFERENCES users(user_id),
  FOREIGN KEY (handled_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

/* Add case_title if missing */
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='cases' AND COLUMN_NAME='case_title'
)
THEN
    ALTER TABLE cases ADD COLUMN case_title VARCHAR(255);
END IF;


/* ===================================================
   ANNOUNCEMENTS TABLE
=================================================== */

CREATE TABLE IF NOT EXISTS announcements (
  announcement_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  posted_by INT NOT NULL,
  posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(user_id)
) ENGINE=InnoDB;


/* ===================================================
   INDEXES (SAFE)
=================================================== */

CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_validation_status ON users(validation_status);
CREATE INDEX IF NOT EXISTS idx_clearance_status ON clearance_requests(status);
CREATE INDEX IF NOT EXISTS idx_cases_status ON cases(status);

-- Complete Resident Profiles System
-- Run this SQL to add all necessary tables

USE e_barangay;

-- =====================================================
-- RESIDENT PROFILES TABLE (Complete Personal Data)
-- =====================================================
CREATE TABLE IF NOT EXISTS resident_profiles (
  profile_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  
  -- Personal Information (from registration, now centralized)
  birthplace VARCHAR(255),
  age INT,
  height DECIMAL(5,2) COMMENT 'in cm',
  weight DECIMAL(5,2) COMMENT 'in kg',
  blood_type VARCHAR(5),
  
  -- Address Details (more specific)
  house_no VARCHAR(50),
  street VARCHAR(255),
  purok VARCHAR(50),
  barangay VARCHAR(100) DEFAULT 'Cantil',
  municipality VARCHAR(100) DEFAULT 'Roxas',
  province VARCHAR(100) DEFAULT 'Oriental Mindoro',
  years_of_residency INT,
  
  -- Family Information
  spouse_name VARCHAR(255),
  spouse_occupation VARCHAR(255),
  father_name VARCHAR(255),
  father_occupation VARCHAR(255),
  mother_name VARCHAR(255),
  mother_maiden_name VARCHAR(255),
  mother_occupation VARCHAR(255),
  number_of_children INT DEFAULT 0,
  
  -- Employment & Income
  occupation VARCHAR(255),
  employer_name VARCHAR(255),
  employer_address TEXT,
  monthly_income DECIMAL(10,2),
  employment_status ENUM('Employed', 'Self-Employed', 'Unemployed', 'Student', 'Retired') DEFAULT 'Unemployed',
  
  -- Government IDs & Numbers
  barangay_id_number VARCHAR(50) UNIQUE,
  tin VARCHAR(20),
  sss_number VARCHAR(20),
  philhealth_number VARCHAR(20),
  pagibig_number VARCHAR(20),
  voters_id VARCHAR(50),
  
  -- Emergency Contact
  emergency_contact_name VARCHAR(255),
  emergency_contact_number VARCHAR(20),
  emergency_contact_relation VARCHAR(100),
  emergency_contact_address TEXT,
  
  -- Additional Information
  religion VARCHAR(100),
  educational_attainment VARCHAR(100),
  pwd BOOLEAN DEFAULT FALSE COMMENT 'Person with Disability',
  pwd_id VARCHAR(50),
  senior_citizen BOOLEAN DEFAULT FALSE,
  senior_id VARCHAR(50),
  indigenous_person BOOLEAN DEFAULT FALSE,
  tribe_name VARCHAR(100),
  
  -- Profile Status
  profile_completed BOOLEAN DEFAULT FALSE,
  profile_completeness_percentage INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_barangay_id (barangay_id_number),
  INDEX idx_profile_completed (profile_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DOCUMENT TYPES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS document_types (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(100) NOT NULL UNIQUE,
  type_code VARCHAR(20) NOT NULL UNIQUE,
  description TEXT,
  fee DECIMAL(10,2) DEFAULT 0.00,
  validity_months INT DEFAULT 6,
  template_file VARCHAR(255),
  required_fields JSON COMMENT 'JSON array of required profile fields',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_type_code (type_code),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default document types
INSERT INTO document_types (type_name, type_code, description, fee, validity_months, is_active) VALUES
('Barangay Clearance', 'BC', 'General barangay clearance for various purposes', 50.00, 6, TRUE),
('Certificate of Residency', 'CR', 'Certifies that the person is a resident of the barangay', 30.00, 12, TRUE),
('Certificate of Indigency', 'CI', 'For residents who need financial assistance', 0.00, 6, TRUE),
('Business Permit Clearance', 'BPC', 'Required for business permit applications', 100.00, 12, TRUE),
('Certificate of Good Moral Character', 'CGMC', 'Certifies good standing in the community', 50.00, 6, TRUE),
('Barangay ID', 'BID', 'Official barangay identification card', 50.00, 36, TRUE)
ON DUPLICATE KEY UPDATE type_name=VALUES(type_name);

-- =====================================================
-- GENERATED DOCUMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS generated_documents (
  document_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type_id INT NOT NULL,
  clearance_request_id INT NULL COMMENT 'Links to clearance_requests table',
  
  -- Document Details
  document_number VARCHAR(50) NOT NULL UNIQUE,
  control_number VARCHAR(50) NOT NULL UNIQUE,
  purpose TEXT,
  
  -- Generation Info
  generated_by INT NOT NULL COMMENT 'Secretary/Admin who generated',
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- File Storage
  pdf_filename VARCHAR(255),
  pdf_file_size INT,
  qr_code_data VARCHAR(500),
  
  -- Validity
  issue_date DATE NOT NULL,
  valid_until DATE,
  
  -- Status
  is_claimed BOOLEAN DEFAULT FALSE,
  claimed_at TIMESTAMP NULL,
  is_revoked BOOLEAN DEFAULT FALSE,
  revoked_at TIMESTAMP NULL,
  revoked_by INT NULL,
  revocation_reason TEXT,
  
  -- Metadata
  remarks TEXT,
  
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
  FOREIGN KEY (document_type_id) REFERENCES document_types(type_id),
  FOREIGN KEY (generated_by) REFERENCES users(user_id),
  FOREIGN KEY (clearance_request_id) REFERENCES clearance_requests(request_id) ON DELETE SET NULL,
  
  INDEX idx_user_documents (user_id),
  INDEX idx_document_number (document_number),
  INDEX idx_control_number (control_number),
  INDEX idx_generated_date (generated_at),
  INDEX idx_claimed (is_claimed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action_type VARCHAR(100) NOT NULL,
  action_description TEXT NOT NULL,
  affected_user_id INT NULL COMMENT 'For actions affecting other users',
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_user_logs (user_id),
  INDEX idx_action_type (action_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- UPDATE CLEARANCE_REQUESTS TABLE
-- =====================================================
-- Add processed_by column if not exists
ALTER TABLE clearance_requests 
ADD COLUMN IF NOT EXISTS processed_by INT NULL COMMENT 'Admin/Secretary who processed',
ADD COLUMN IF NOT EXISTS document_id INT NULL COMMENT 'Generated document reference',
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;

-- Add foreign keys
ALTER TABLE clearance_requests
ADD CONSTRAINT fk_clearance_processed_by 
FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- =====================================================
-- MIGRATE EXISTING DATA TO RESIDENT_PROFILES
-- =====================================================
-- This safely migrates data from users table to resident_profiles
INSERT INTO resident_profiles (user_id, age)
SELECT 
  user_id,
  TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age
FROM users 
WHERE role = 'resident' 
  AND birthdate IS NOT NULL
  AND user_id NOT IN (SELECT user_id FROM resident_profiles)
ON DUPLICATE KEY UPDATE age = VALUES(age);

-- =====================================================
-- DOCUMENT NUMBER SEQUENCE TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS document_sequences (
  sequence_id INT AUTO_INCREMENT PRIMARY KEY,
  document_type_code VARCHAR(20) NOT NULL,
  year INT NOT NULL,
  last_number INT DEFAULT 0,
  
  UNIQUE KEY unique_type_year (document_type_code, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- HELPFUL VIEWS
-- =====================================================

-- View: Complete Resident Information
CREATE OR REPLACE VIEW v_resident_complete AS
SELECT 
  u.user_id,
  u.fullname,
  u.email,
  u.contact_no,
  u.address,
  u.gender,
  u.birthdate,
  u.civil_status,
  u.nationality,
  u.validation_status,
  u.date_registered,
  u.valid_id,
  
  rp.age,
  rp.birthplace,
  rp.height,
  rp.weight,
  rp.blood_type,
  rp.house_no,
  rp.street,
  rp.purok,
  rp.years_of_residency,
  rp.occupation,
  rp.monthly_income,
  rp.barangay_id_number,
  rp.tin,
  rp.sss_number,
  rp.philhealth_number,
  rp.spouse_name,
  rp.father_name,
  rp.mother_name,
  rp.emergency_contact_name,
  rp.emergency_contact_number,
  rp.profile_completed,
  rp.profile_completeness_percentage
FROM users u
LEFT JOIN resident_profiles rp ON u.user_id = rp.user_id
WHERE u.role = 'resident';

-- View: Document Statistics
CREATE OR REPLACE VIEW v_document_statistics AS
SELECT 
  dt.type_name,
  COUNT(gd.document_id) as total_issued,
  SUM(CASE WHEN gd.is_claimed = TRUE THEN 1 ELSE 0 END) as claimed_count,
  SUM(CASE WHEN gd.is_claimed = FALSE THEN 1 ELSE 0 END) as unclaimed_count,
  SUM(CASE WHEN gd.valid_until >= CURDATE() THEN 1 ELSE 0 END) as valid_count,
  SUM(CASE WHEN gd.valid_until < CURDATE() THEN 1 ELSE 0 END) as expired_count
FROM document_types dt
LEFT JOIN generated_documents gd ON dt.type_id = gd.document_type_id
WHERE dt.is_active = TRUE
GROUP BY dt.type_id, dt.type_name;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_users_validation ON users(validation_status, role);
CREATE INDEX IF NOT EXISTS idx_clearance_status_date ON clearance_requests(status, request_date);
CREATE INDEX IF NOT EXISTS idx_cases_status_date ON cases(status, case_date);