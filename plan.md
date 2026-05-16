# Villa Room Reservation System

## Project Overview

A complete web-based Villa Room Reservation and Management System developed using:

- PHP
- MySQLi
- HTML5
- CSS3
- Bootstrap 5

This system helps manage villa operations including room reservations, customers, staff, products, billing, and user management.

---

# Features

## 1. Authentication System

### Features

- User Login
- Logout
- Session Management
- Password Encryption
- Role-Based Access Control

### User Roles

- Admin
- Receptionist
- Manager
- Cashier

---

# 2. Room Management

## Features

- Add Rooms
- Edit Rooms
- Delete Rooms
- Room Availability Status
- Room Pricing
- Room Categories
- Room Images

## Room Details

| Field       | Description               |
| ----------- | ------------------------- |
| Room ID     | Unique Room ID            |
| Room Number | Room Number               |
| Room Type   | Deluxe / Standard / Suite |
| Capacity    | Guest Capacity            |
| Price       | Price Per Night           |
| Status      | Available / Occupied      |
| Description | Room Description          |

---

# 3. Reservation Management

## Features

- Create Reservations
- Check Room Availability
- Check-In / Check-Out
- Reservation History
- Booking Calendar
- Cancel Reservations

## Reservation Details

| Field          | Description           |
| -------------- | --------------------- |
| Reservation ID | Unique Reservation ID |
| Customer       | Customer Name         |
| Room           | Assigned Room         |
| Check-In Date  | Arrival Date          |
| Check-Out Date | Departure Date        |
| Adults         | Number of Adults      |
| Children       | Number of Children    |
| Status         | Pending / Confirmed   |

---

# 4. Customer Management

## Features

- Add Customers
- Edit Customer Information
- Customer Search
- Booking History
- Contact Records

## Customer Details

| Field        | Description      |
| ------------ | ---------------- |
| Customer ID  | Unique ID        |
| Full Name    | Customer Name    |
| NIC/Passport | Identification   |
| Phone        | Contact Number   |
| Email        | Email Address    |
| Address      | Customer Address |

---

# 5. Staff Management

## Features

- Add Staff
- Edit Staff
- Assign Roles
- Staff Information Management

## Staff Details

| Field    | Description     |
| -------- | --------------- |
| Staff ID | Unique Staff ID |
| Name     | Staff Name      |
| Position | Job Position    |
| Contact  | Phone Number    |
| Email    | Email Address   |
| Salary   | Monthly Salary  |

---

# 6. Product Management

## Features

- Add Products
- Edit Products
- Delete Products
- Stock Management
- Product Categories

## Product Examples

- Food Items
- Drinks
- Room Service Products
- Cleaning Supplies

## Product Details

| Field        | Description       |
| ------------ | ----------------- |
| Product ID   | Unique Product ID |
| Product Name | Product Name      |
| Category     | Product Category  |
| Quantity     | Available Stock   |
| Price        | Product Price     |

---

# 7. User Management

## Features

- Create Users
- Edit Users
- Activate/Deactivate Users
- Password Reset

## User Details

| Field    | Description        |
| -------- | ------------------ |
| User ID  | Unique User ID     |
| Username | Login Username     |
| Password | Encrypted Password |
| Role     | Assigned Role      |
| Status   | Active / Inactive  |

---

# 8. User Roles Management

## Role Permissions

| Module       | Admin | Receptionist | Cashier   | Manager   |
| ------------ | ----- | ------------ | --------- | --------- |
| Rooms        | Full  | View/Edit    | View      | View      |
| Reservations | Full  | Full         | View      | View      |
| Billing      | Full  | View         | Full      | View      |
| Users        | Full  | No Access    | No Access | No Access |
| Reports      | Full  | View         | View      | Full      |

---

# 9. Billing & Invoice System

## Features

- Generate Bills
- Add Room Charges
- Add Product Charges
- Payment Management
- Print Invoices
- PDF Invoice Support (Optional)

## Bill Components

- Room Charges
- Product Charges
- Taxes
- Discounts
- Grand Total

---

# 10. Reports Module

## Available Reports

- Daily Reservations
- Monthly Revenue
- Occupancy Reports
- Customer Reports
- Staff Reports
- Product Sales Reports

---

# Database Design

## Main Tables

- users
- user_roles
- rooms
- room_types
- reservations
- customers
- staff
- products
- invoices
- invoice_items
- payments

---

# Suggested Folder Structure

```bash
villa-reservation-system/
│
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│
├── config/
│   └── database.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│
├── modules/
│   ├── rooms/
│   ├── reservations/
│   ├── customers/
│   ├── staff/
│   ├── products/
│   ├── billing/
│   ├── reports/
│
├── login.php
├── dashboard.php
├── index.php
└── logout.php
```

---

# Technology Stack

| Technology  | Purpose              |
| ----------- | -------------------- |
| PHP         | Backend Development  |
| MySQLi      | Database Management  |
| HTML5       | Page Structure       |
| CSS3        | Styling              |
| Bootstrap 5 | Responsive UI        |
| JavaScript  | Interactive Features |
| jQuery      | UI Enhancements      |

---

# Security Features

- Password Hashing
- SQL Injection Prevention
- Session Security
- Role-Based Access Control
- Input Validation

---

# Future Enhancements

- Online Booking System
- Payment Gateway Integration
- Email Notifications
- SMS Notifications
- QR Code Check-In
- Mobile Application
- Multi-Branch Support

---

# Development Phases

## Phase 1

- Project Planning
- Database Design
- Authentication System

## Phase 2

- Room Management
- Reservation Module

## Phase 3

- Customer Management
- Staff Management
- Product Management

## Phase 4

- Billing System
- Reports Module

## Phase 5

- Testing
- Deployment

---

# Estimated Timeline

| Phase             | Duration   |
| ----------------- | ---------- |
| Planning          | 2 Days     |
| Database Design   | 2 Days     |
| Authentication    | 3 Days     |
| Core Modules      | 10–15 Days |
| Billing & Reports | 5 Days     |
| Testing           | 3 Days     |
| Deployment        | 2 Days     |

---

# Expected Outputs

- Fully Functional Villa Reservation System
- Responsive Admin Dashboard
- Printable Bills & Reports
- Secure User Management
- Organized Database Structure

---

# Conclusion

This Villa Room Reservation System provides a complete solution for managing:

- Rooms
- Reservations
- Customers
- Staff
- Products
- Billing
- User Roles

The system is secure, scalable, and suitable for small to medium-sized villa businesses.
