-- HRGetafe Database Setup Script
-- Database: hrgetafee
-- Fixed version with proper foreign key order

-- Create Roles Table FIRST
CREATE TABLE IF NOT EXISTS roles (
  role_id INT PRIMARY KEY AUTO_INCREMENT,
  role_name VARCHAR(50) UNIQUE NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Departments Table
CREATE TABLE IF NOT EXISTS departments (
  dept_id INT PRIMARY KEY AUTO_INCREMENT,
  dept_name VARCHAR(100) NOT NULL,
  dept_head_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Employees Table (before users table)
CREATE TABLE IF NOT EXISTS employees (
  employee_id INT PRIMARY KEY AUTO_INCREMENT,
  employee_code VARCHAR(20) UNIQUE NOT NULL,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50),
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(20),
  position VARCHAR(100),
  dept_id INT,
  date_hired DATE,
  salary DECIMAL(10, 2),
  qr_code_path VARCHAR(255),
  status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
);

-- Create Users Table (after employees)
CREATE TABLE IF NOT EXISTS users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  employee_id INT,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login DATETIME,
  FOREIGN KEY (role_id) REFERENCES roles(role_id),
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
);

-- Create Leave Types Table
CREATE TABLE IF NOT EXISTS leave_types (
  leave_type_id INT PRIMARY KEY AUTO_INCREMENT,
  leave_type_name VARCHAR(50) NOT NULL,
  max_days_per_year INT,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
  attendance_id INT PRIMARY KEY AUTO_INCREMENT,
  employee_id INT NOT NULL,
  clock_in DATETIME,
  clock_out DATETIME,
  attendance_date DATE,
  status ENUM('present', 'absent', 'late', 'on_leave') DEFAULT 'present',
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
);

-- Create Leave Requests Table
CREATE TABLE IF NOT EXISTS leave_requests (
  leave_id INT PRIMARY KEY AUTO_INCREMENT,
  employee_id INT NOT NULL,
  leave_type_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  number_of_days INT,
  reason TEXT,
  status ENUM('pending', 'approved', 'denied', 'cancelled') DEFAULT 'pending',
  approved_by INT,
  approved_date DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
  FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id),
  FOREIGN KEY (approved_by) REFERENCES employees(employee_id)
);

-- Create Payroll Table
CREATE TABLE IF NOT EXISTS payroll (
  payroll_id INT PRIMARY KEY AUTO_INCREMENT,
  employee_id INT NOT NULL,
  payroll_month INT,
  payroll_year INT,
  gross_salary DECIMAL(10, 2),
  deductions DECIMAL(10, 2),
  net_salary DECIMAL(10, 2),
  status ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
);

-- Create Holidays Table
CREATE TABLE IF NOT EXISTS holidays (
  holiday_id INT PRIMARY KEY AUTO_INCREMENT,
  holiday_name VARCHAR(100) NOT NULL,
  holiday_date DATE NOT NULL,
  is_special BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Roles
INSERT IGNORE INTO roles (role_id, role_name, description) VALUES
(1, 'HR Administrator', 'Full system control, security, and data overrides'),
(2, 'HR Staff', 'Employee management, payroll, and report generation'),
(3, 'Department Head', 'Leave approvals and team attendance monitoring'),
(4, 'Employee', 'Clock in/out, view records, apply for leaves');

-- Insert Departments
INSERT IGNORE INTO departments (dept_id, dept_name) VALUES
(1, 'Human Resources'),
(2, 'Finance'),
(3, 'Operations'),
(4, 'Planning and Development');

-- Insert Sample Employees
INSERT IGNORE INTO employees (employee_id, employee_code, first_name, last_name, middle_name, email, phone, position, dept_id, date_hired, salary, status) VALUES
(1, 'GETAFE-2026-001', 'Admin', 'User', 'System', 'admin@getafe.gov.ph', '09123456789', 'HR Administrator', 1, '2020-01-01', 50000, 'active'),
(2, 'GETAFE-2026-002', 'John', 'Doe', 'Staff', 'john.doe@getafe.gov.ph', '09234567890', 'HR Staff', 1, '2021-03-15', 35000, 'active'),
(3, 'GETAFE-2026-003', 'Maria', 'Cruz', 'Head', 'maria.cruz@getafe.gov.ph', '09345678901', 'Department Head', 2, '2020-06-10', 40000, 'active'),
(4, 'GETAFE-2026-004', 'Pedro', 'Santos', 'Employee', 'pedro.santos@getafe.gov.ph', '09456789012', 'Administrative Officer', 2, '2022-01-20', 25000, 'active'),
(5, 'GETAFE-2026-005', 'Rosa', 'Martinez', 'Employee', 'rosa.martinez@getafe.gov.ph', '09567890123', 'Clerk', 3, '2022-05-10', 22000, 'active');

-- Insert Sample Users
INSERT IGNORE INTO users (username, password, role_id, employee_id, status) VALUES
('admin', 'admin123', 1, 1, 'active'),
('STAFF001', 'password123', 2, 2, 'active'),
('HEAD001', 'password123', 3, 3, 'active'),
('EMP001', 'password123', 4, 4, 'active'),
('EMP002', 'password123', 4, 5, 'active');

-- Insert Leave Types
INSERT IGNORE INTO leave_types (leave_type_name, max_days_per_year, description) VALUES
('Vacation Leave', 15, 'Annual vacation leave'),
('Sick Leave', 10, 'Medical leave'),
('Emergency Leave', 5, 'Emergency situations'),
('Study Leave', 3, 'Educational purposes');

-- Insert Sample Holidays
INSERT IGNORE INTO holidays (holiday_name, holiday_date, is_special) VALUES
('New Year', '2026-01-01', FALSE),
('EDSA Revolution', '2026-02-25', FALSE),
('Maundy Thursday', '2026-04-09', FALSE),
('Good Friday', '2026-04-10', FALSE),
('Black Saturday', '2026-04-11', FALSE),
('Araw ng Kagitingan', '2026-04-09', FALSE),
('Labor Day', '2026-05-01', FALSE),
('Independence Day', '2026-06-12', FALSE),
('Ninoy Aquino Day', '2026-08-21', FALSE),
('National Heroes Day', '2026-08-23', FALSE),
('All Saints Day', '2026-11-01', FALSE),
('Bonifacio Day', '2026-11-30', FALSE),
('Christmas Day', '2026-12-25', FALSE),
('Rizal Day', '2026-12-30', FALSE);

-- Sample Attendance Records
INSERT IGNORE INTO attendance (employee_id, clock_in, clock_out, attendance_date, status) VALUES
(4, '2026-07-03 08:00:00', '2026-07-03 17:00:00', '2026-07-03', 'present'),
(4, '2026-07-02 08:15:00', '2026-07-02 17:05:00', '2026-07-02', 'late'),
(5, '2026-07-03 08:00:00', '2026-07-03 17:00:00', '2026-07-03', 'present');

-- Sample Leave Request
INSERT IGNORE INTO leave_requests (employee_id, leave_type_id, start_date, end_date, number_of_days, reason, status, approved_by) VALUES
(4, 1, '2026-07-15', '2026-07-17', 3, 'Family vacation', 'approved', 3),
(5, 2, '2026-07-10', '2026-07-10', 1, 'Medical appointment', 'pending', NULL);
