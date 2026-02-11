# SEB Exam System

A web-based exam and escape room management system with Safe Exam Browser (SEB) configuration generation, designed for XAMPP (Apache + PHP 8.x + MySQL/MariaDB).

## Features

- **Authentication**: Login with Teacher/Student roles
- **Exam/Quiz Management**: Teacher CRUD for MCQ, True/False, Short Answer questions
- **Escape Room Editor**: Create/edit/import/export node-based adventure games
- **SEB Config Generator**: Visual wizard for generating Safe Exam Browser XML configs
- **Student Pass Prediction**: Sigmoid-based probability scoring engine
- **REST API**: Token-authenticated endpoints for future Moodle integration
- **Security**: CSRF protection, prepared statements, rate limiting, session hardening

## Installation (XAMPP)

### Prerequisites
- XAMPP with Apache + PHP 8.x + MySQL/MariaDB
- phpMyAdmin (included with XAMPP)

### Steps

1. **Copy project folder**
   Copy the entire `seb-exam/` folder into your XAMPP `htdocs/` directory:
   ```
   C:\xampp\htdocs\seb-exam\
   ```

2. **Create the database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" in the left sidebar
   - Database name: `seb_exam`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import schema**
   - Select the `seb_exam` database
   - Click the "Import" tab
   - Choose file: `seb-exam/sql/schema.sql`
   - Click "Go"

4. **Configure database connection** (if needed)
   - Edit `seb-exam/app/config/db.php`
   - Update `DB_USER`, `DB_PASS` if your MySQL credentials differ from defaults

5. **Run the installer**
   - Open: http://localhost/seb-exam/public/install.php
   - This will insert demo data with proper password hashes
   - **Save the API token** displayed on the installation page
   - **Delete `public/install.php`** after installation for security

6. **Login**
   - Open: http://localhost/seb-exam/public/login.php
   - Teacher: `teacher1` / `teacher123`
   - Student: `student1` / `student123` or `student2` / `student123`

## Project Structure

```
seb-exam/
├── app/                    # Application logic (not web-accessible)
│   ├── config/             # Database and app configuration
│   ├── lib/                # Core libraries (auth, CSRF, security, SEB, prediction)
│   ├── models/             # Data models (User, Exam, Game, SebConfig, etc.)
│   └── views/              # Layout templates (header, footer)
├── public/                 # Web root (accessible via browser)
│   ├── teacher/            # Teacher pages (exam CRUD, game editor, SEB wizard)
│   ├── student/            # Student pages (take exam, play game, results)
│   ├── api/                # REST API endpoints
│   └── assets/             # CSS and JavaScript
├── storage/                # Generated files (protected by .htaccess)
│   ├── seb_configs/        # Generated .seb.xml files
│   └── game_exports/       # Cached JSON exports
├── sql/                    # Database schema and seed data
│   ├── schema.sql          # Table definitions
│   └── seed.sql            # Demo data (alternative to install.php)
└── README.md
```

## Demo Accounts

| Username  | Password    | Role    |
|-----------|-------------|---------|
| teacher1  | teacher123  | Teacher |
| student1  | student123  | Student |
| student2  | student123  | Student |

## API Endpoints

All API endpoints require a Bearer token in the `Authorization` header:
```
Authorization: Bearer <your-api-token>
```

Or as a query parameter: `?api_token=<your-api-token>`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/export_game.php?id=1` | Export game as enriched JSON |
| GET | `/api/export_seb.php?config_id=1` | Download SEB config XML |
| POST | `/api/import_game.php` | Import game from JSON body |

## Smoke Test Checklist

1. **Login**: Visit `/public/login.php`, login as teacher1/teacher123 → see Teacher Dashboard
2. **Create Exam**: Teacher → Exams → Create Exam → add 2+ questions → save
3. **Student Take Exam**: Login as student1 → Dashboard → Take Exam → answer → submit → view result
4. **Create Escape Room**: Login as teacher → Games → Create Game → add 3+ nodes with choices → save
5. **Import Game JSON**: Teacher → Games → Import → upload a JSON file → verify imported game
6. **Export Game JSON**: Teacher → Games → Export JSON → verify downloaded file
7. **Play Escape Room**: Login as student → Dashboard → Play → navigate through nodes
8. **SEB Config**: Teacher → SEB Configs → Create → select exam → configure settings → generate → download XML
9. **Prediction**: Teacher → Predictions → select student1 + exam → view probability score and breakdown
10. **API Export**: `curl -H "Authorization: Bearer <token>" http://localhost/seb-exam/public/api/export_game.php?id=1`
11. **CSRF Check**: Try submitting a form without CSRF token → should get 403
12. **Role Check**: Login as student → try accessing `/teacher/` → should get 403
13. **Rate Limiting**: Fail login 6 times rapidly → should get lockout message

## Security Features

- Password hashing with `password_hash()` / `password_verify()` (bcrypt)
- Session ID regeneration on login
- CSRF tokens on all POST forms
- All SQL queries use PDO prepared statements
- All HTML output escaped with `htmlspecialchars()`
- Role-based access control on every page
- Login rate limiting (5 attempts per 15 minutes per IP)
- `.htaccess` protection on storage directories
- No external CDNs or dependencies




