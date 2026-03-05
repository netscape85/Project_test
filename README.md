# TCG Engineering Hub - MVP SaaS

Internal SaaS platform for executing the TCG Engineering Framework across projects.

## 🚀 Tech Stack

- **Backend**: Laravel 11 (API)
- **Frontend**: Vue 3 + Vuetify 3
- **Database**: MariaDB/MySQL
- **Authentication**: Laravel Sanctum

## 📋 Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MariaDB/MySQL
- npm or yarn

## ⚙️ Setup Instructions

### 1. Clone Repository

```bash
git clone <repository-url>
cd Project_test
```

### 2. Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_test
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed demo users
php artisan db:seed

# Start backend server
php artisan serve
```

Backend will run on: `http://localhost:8000`

### 3. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Configure API endpoint in .env or src/plugins/api.js
# Default: http://localhost:8000/api

# Start development server
npm run dev
```

Frontend will run on: `http://localhost:5173`

## 👥 Demo Users

After seeding, you can login with:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| PM | pm@example.com | password |
| Engineer | engineer@example.com | password |
| Viewer | viewer@example.com | password |

## 🧪 Running Tests

```bash
cd backend

# Create test database
mysql -u root -p
CREATE DATABASE tcg_hub_test;
exit;

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=Gate1Test
```

## 📁 Project Structure

```
Project_test/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── tests/Feature/
├── frontend/             # Vue 3 UI
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── composables/
│   │   └── plugins/
│   └── package.json
└── README.md
```

## 🎯 Core Features

### Authentication & Authorization (RBAC)
- 4 roles: admin, pm, engineer, viewer
- Policy-based authorization
- Sanctum token authentication

### Projects Management
- CRUD operations
- Status workflow: draft → discovery → execution → delivered
- Soft delete (archive)

### Artifacts (7 types)
1. Strategic Alignment
2. Big Picture
3. Domain Breakdown
4. Module Matrix
5. Module Engineering
6. System Architecture
7. Phase Scope

### Business Rules (Gates)
- **Gate 1**: domain_breakdown requires big_picture done
- **Gate 2**: module_matrix requires domain_breakdown done
- **Gate 3**: system_architecture requires 3+ validated modules
- **Gate 4**: Project execution phase requires 4 artifacts done

### Modules (TCG Framework)
10 engineering fields per module:
- objective, inputs, data_structure, logic_rules, outputs
- responsibility, failure_scenarios, audit_trail_requirements
- dependencies, version_note

### Audit Trail
- Tracks all changes to projects, artifacts, modules
- Stores before/after JSON diffs
- Timeline view in project detail

## 🔑 API Endpoints

### Authentication
```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
GET    /api/auth/me
```

### Projects
```
GET    /api/projects
POST   /api/projects
GET    /api/projects/{id}
PUT    /api/projects/{id}
DELETE /api/projects/{id}
POST   /api/projects/{id}/archive
```

### Artifacts
```
GET    /api/artifacts
POST   /api/artifacts
GET    /api/artifacts/{id}
PUT    /api/artifacts/{id}
DELETE /api/artifacts/{id}
POST   /api/artifacts/{id}/complete
POST   /api/artifacts/{id}/assign
```

### Modules
```
GET    /api/modules
POST   /api/modules
GET    /api/modules/{id}
PUT    /api/modules/{id}
DELETE /api/modules/{id}
POST   /api/modules/{id}/validate
```

### Users (Admin only)
```
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
GET    /api/users/list  (for assignment - admin/pm/engineer)
```

### Templates (Optional)
```
GET    /api/templates
POST   /api/templates
POST   /api/projects/from-template/{id}
```

### Export (Optional)
```
GET    /api/projects/{id}/export
```

## 📊 Role Permissions

| Action | Admin | PM | Engineer | Viewer |
|--------|-------|-----|----------|--------|
| Manage Users | ✅ | ❌ | ❌ | ❌ |
| Manage Projects | ✅ | ✅ | ❌ | ❌ |
| Manage Artifacts | ✅ | ✅ | ❌ | ❌ |
| Edit Modules | ✅ | ✅ | ✅ | ❌ |
| View All | ✅ | ✅ | ✅ | ✅ |

## 🐛 Troubleshooting

### CORS Issues
Ensure `config/cors.php` allows your frontend origin.

### Database Connection
Verify MariaDB/MySQL is running and credentials are correct in `.env`.

### Token Issues
Clear browser localStorage and login again.

## 📝 License

Internal use only - The Cloud Group

---

## 👨‍💻 Developer

**Programador**: Josvany Hernández Ortega  
**Fecha**: Marzo 2026
