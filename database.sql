CREATE DATABASE wheelsonrent;
USE wheelsonrent;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    dob DATE,
    address VARCHAR(255),
    license_no VARCHAR(50),
    profile_picture TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trip_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100),
    trip_start DATE,
    trip_end DATE,
    delivery_pickup BOOLEAN
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    full_name VARCHAR(100),
    phone VARCHAR(15),
    pickup_location VARCHAR(255),
    pickup_date DATE,
    dropoff_date DATE,
    delivery_required BOOLEAN,
    vehicle_name VARCHAR(100),
    vehicle_type VARCHAR(50),
    total_days INT,
    total_amount DECIMAL(10,2),
    payment_method VARCHAR(20),
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'replied') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);
ALTER TABLE contacts ADD is_admin_message TINYINT(1) DEFAULT 0;



CREATE TABLE replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seats INT NOT NULL,
    fuel_type VARCHAR(20) NOT NULL,
    transmission VARCHAR(20) DEFAULT NULL
);
