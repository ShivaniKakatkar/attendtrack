CREATE DATABASE IF NOT EXISTS attendance_db CHARACTER SET utf8mb4;
USE attendance_db;

CREATE TABLE students(
    roll_no VARCHAR(30) PRIMARY KEY, 
    name VARCHAR(80) NOT NULL, 
    password VARCHAR(255) NOT NULL);
    
CREATE TABLE teachers(
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(80) UNIQUE NOT NULL, 
    password VARCHAR(255) NOT NULL);

CREATE TABLE attendance(
    id INT AUTO_INCREMENT PRIMARY KEY, 
    roll_no VARCHAR(30) NOT NULL, 
    subject VARCHAR(80) NOT NULL, 
    att_date DATE NOT NULL, 
    status CHAR(1) NOT NULL, 
    UNIQUE KEY u(roll_no,subject,att_date));

CREATE TABLE assignments(
    id INT AUTO_INCREMENT PRIMARY KEY, 
    subject VARCHAR(80) NOT NULL, 
    title VARCHAR(150) NOT NULL, 
    file VARCHAR(120) NOT NULL, 
    orig_name VARCHAR(150) NOT NULL, 
    teacher_id INT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE attendance_summary(
    roll_no VARCHAR(30) NOT NULL, 
    subject VARCHAR(80) NOT NULL, 
    attended INT NOT NULL, 
    total INT NOT NULL, 
    PRIMARY KEY(roll_no,subject));