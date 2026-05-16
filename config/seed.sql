USE villa_reservation;

INSERT IGNORE INTO user_roles (role_name) VALUES ('Admin'), ('Receptionist'), ('Cashier'), ('Manager');

INSERT IGNORE INTO users (username, password, role_id, status) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

INSERT IGNORE INTO room_types (type_name) VALUES ('Deluxe'), ('Standard'), ('Suite');

INSERT IGNORE INTO rooms (room_number, room_type_id, capacity, price, status, description) VALUES
('101', 2, 2, 80.00, 'Available', 'Standard room with city view'),
('102', 2, 2, 80.00, 'Available', 'Standard room with garden view'),
('201', 1, 3, 150.00, 'Available', 'Deluxe room with ocean view'),
('202', 1, 3, 150.00, 'Available', 'Deluxe room with pool view'),
('301', 3, 4, 250.00, 'Available', 'Suite with living room and balcony');

INSERT IGNORE INTO customers (full_name, nic_passport, phone, email, address) VALUES
('John Doe', 'NIC123456', '555-0101', 'john@email.com', '123 Main St'),
('Jane Smith', 'NIC789012', '555-0202', 'jane@email.com', '456 Oak Ave');

INSERT IGNORE INTO products (product_name, category, quantity, price) VALUES
('Mineral Water', 'Drinks', 50, 2.00),
('Orange Juice', 'Drinks', 30, 3.50),
('Sandwich', 'Food', 20, 8.00),
('Pasta', 'Food', 15, 12.00),
('Wine Bottle', 'Drinks', 10, 25.00),
('Toothbrush Kit', 'Room Service', 40, 3.00),
('Shampoo', 'Room Service', 30, 5.00);

INSERT IGNORE INTO staff (name, position, phone, email, salary) VALUES
('Alice Johnson', 'Manager', '555-1001', 'alice@villa.com', 5000.00),
('Bob Williams', 'Receptionist', '555-1002', 'bob@villa.com', 2500.00),
('Carol Brown', 'Cashier', '555-1003', 'carol@villa.com', 2200.00);
