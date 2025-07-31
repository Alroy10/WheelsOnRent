# 🚗 WheelsOnRent - Vehicle Rental System

WheelsOnRent is a modern, responsive, and user-friendly web-based vehicle rental platform designed to streamline the process of renting vehicles online. 
It empowers users to browse, book, and manage vehicle rentals effortlessly, while offering administrators robust control over vehicles, users, and bookings.



## 📌 Features

### 👥 User Module
- Register/Login functionality with secure authentication
- Profile management
- Vehicle search and filtering
- Real-time booking and cancellation
- Contact/feedback system

### 🚘 Vehicle Management (Admin)
- Add, update, or remove vehicles
- Upload images and details
- Manage vehicle availability and pricing

### 🔍 Search & Filter
- Filter vehicles by location, type, price, and availability
- Responsive and intuitive UI for faster access

### 📅 Booking System
- Book vehicle with preferred location and dates
- Optional doorstep delivery
- View and cancel bookings

### 💳 Payment Integration (Planned)
- Secure online payment processing (future enhancement)

### 📊 Admin Dashboard
- View stats on rentals, revenue, and user activity
- Manage all bookings and user queries

---

## 🛠️ Technology Stack

| Layer        | Technologies                       |
|-------------|------------------------------------|
| Frontend     | HTML, CSS, JavaScript              |
| Backend      | PHP                                |
| Database     | MySQL                              |
| Tools        | VS Code, XAMPP                     |
| OS Support   | Windows / Linux / Mac              |
| Browser      | Chrome, Firefox                    |

---

## 📐 System Architecture

- **Client Side:** Built with HTML/CSS/JavaScript
- **Server Side:** PHP for server-side scripting
- **Database:** MySQL for persistent data storage
- **Architecture:** Standard Client-Server model

---

## 🔒 Security Features

- Password hashing using `password_hash()`
- Email validation and input sanitization
- Session handling for authenticated access
- Future support for HTTPS and SSL certificates

---

## 🧪 Testing

- Unit testing of modules
- Integration and user acceptance testing
- Responsive testing across different screen sizes

---

## 🚀 Future Enhancements

- Full payment gateway integration (Stripe, Razorpay, etc.)
- Mobile app support (React Native or Flutter)
- Email and SMS notifications
- Admin analytics with charts

---

## 📁 Project Setup (Local Development)

1. Clone the repo:
   ```bash
   git clone https://github.com/your-username/wheelsonrent.git
   cd wheelsonrent
2. Set up XAMPP or any local server with PHP & MySQL.
3. Import the database:
  - Open phpMyAdmin
  - Create a new database wheelsonrent
  - Import the .sql file provided in /database folder
4. Start Apache and MySQL from XAMPP.
5. Access the app
