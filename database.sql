-- BudgetPro Enterprise Database
CREATE DATABASE budgetpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE budgetpro;

-- Tabela użytkowników
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_type ENUM('personal', 'business', 'both') DEFAULT 'both',
    currency VARCHAR(3) DEFAULT 'PLN',
    language VARCHAR(2) DEFAULT 'pl',
    theme VARCHAR(10) DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Tabela transakcji osobistych
CREATE TABLE personal_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    tags JSON,
    recurring BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela transakcji firmowych
CREATE TABLE business_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    project_id INT NULL,
    tax DECIMAL(5,2) DEFAULT 0,
    status ENUM('paid', 'pending', 'overdue') DEFAULT 'paid',
    tax_deductible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES business_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela projektów firmowych
CREATE TABLE business_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    client VARCHAR(255) NOT NULL,
    budget DECIMAL(12,2) NOT NULL,
    spent DECIMAL(12,2) DEFAULT 0,
    status ENUM('planning', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela zadań projektowych
CREATE TABLE project_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    budget DECIMAL(12,2) DEFAULT 0,
    assignee VARCHAR(100) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES business_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela faktur
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    project_id INT NULL,
    items JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES business_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela celów oszczędnościowych
CREATE TABLE personal_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    current_amount DECIMAL(12,2) DEFAULT 0,
    deadline DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    description TEXT,
    milestones JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela ustawień użytkownika
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    tax_number VARCHAR(50) DEFAULT NULL,
    company_address TEXT DEFAULT NULL,
    notifications JSON DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
} catch (PDOException $e) {
    // To zapisze błąd do pliku zamiast tylko wyświetlać
    Logger::error("Błąd bazy danych: " . $e->getMessage()); 
    throw new Exception("Błąd połączenia z bazą");
}

-- Indexy dla wydajności
CREATE INDEX idx_user_transactions ON personal_transactions(user_id, transaction_date);
CREATE INDEX idx_user_business ON business_transactions(user_id, transaction_date);
CREATE INDEX idx_user_goals ON personal_goals(user_id, deadline);
CREATE INDEX idx_project_status ON business_projects(user_id, status);
CREATE INDEX idx_invoice_status ON invoices(user_id, status, due_date);

-- Przykładowy użytkownik (hasło: admin123)
INSERT INTO users (username, email, password_hash, account_type) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'both');