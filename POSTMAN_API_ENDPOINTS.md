# TCG Engineering Hub - API Endpoints

## Base URL
```
http://127.0.0.1:8000/api
```

## Autenticación

### 1. Login
```
POST /api/auth/login
Content-Type: application/json

Body:
{
    "email": "admin@example.com",
    "password": "password"
}

Response (200):
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    },
    "token": "1|xxxxx..."
}

Response (422 - Error):
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

### 2. Register
```
POST /api/auth/register
Content-Type: application/json

Body:
{
    "name": "Nuevo Usuario",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "viewer"  // Optional: admin, pm, engineer, viewer (default: viewer)
}

Response (201):
{
    "message": "Registration successful",
    "user": {
        "id": 2,
        "name": "Nuevo Usuario",
        "email": "user@example.com",
        "role": "viewer",
        "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "2|xxxxx..."
}
```

### 3. Logout (Requiere Auth)
```
POST /api/auth/logout
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Logout successful"
}
```

### 4. Get Current User (Requiere Auth)
```
GET /api/auth/me
Authorization: Bearer {TOKEN}

Response (200):
{
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    }
}
```

### 5. Update Profile (Requiere Auth)
```
PUT /api/auth/profile
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "name": "Nuevo Nombre",     // Optional
    "email": "nuevo@email.com"  // Optional
}

Response (200):
{
    "message": "Profile updated successfully",
    "user": {
        "id": 1,
        "name": "Nuevo Nombre",
        "email": "nuevo@email.com",
        "role": "admin",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 6. Change Password (Requiere Auth)
```
PUT /api/auth/password
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "current_password": "old_password",
    "password": "new_password",
    "password_confirmation": "new_password"
}

Response (200):
{
    "message": "Password changed successfully"
}

Response (422 - Error):
{
    "message": "The current password is incorrect.",
    "errors": {
        "current_password": ["The current password is incorrect."]
    }
}
```

---

## Users

### 7. List Users (Requiere Auth - Admin)
```
GET /api/users
Authorization: Bearer {TOKEN}

Query Parameters (Optional):
- role: admin | pm | engineer | viewer
- search: string (busca en name o email)
- sort_by: created_at | name | email (default: created_at)
- sort_order: asc | desc (default: desc)
- per_page: number (default: 15)

Ejemplo:
GET /api/users?role=engineer&search=john&sort_by=name&sort_order=asc&per_page=10

Response (200):
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com",
            "role": "admin",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/users?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/users?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/users",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

### 8. List Users for Assignment (Requiere Auth)
```
GET /api/users/list
Authorization: Bearer {TOKEN}

Response (200):
[
    {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    },
    {
        "id": 2,
        "name": "Engineer User",
        "email": "engineer@example.com",
        "role": "engineer"
    }
]
```

### 9. Create User (Requiere Auth - Admin)
```
POST /api/users
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "name": "Nuevo Usuario",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "engineer"  // Required: admin, pm, engineer, viewer
}

Response (201):
{
    "message": "User created successfully",
    "user": {
        "id": 3,
        "name": "Nuevo Usuario",
        "email": "user@example.com",
        "role": "engineer",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}

Response (422 - Validation Error):
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "role": ["The selected role is invalid."]
    }
}
```

### 10. Get User (Requiere Auth)
```
GET /api/users/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}

Response (403 - Forbidden):
{
    "message": "This action is unauthorized."
}

Response (404 - Not Found):
{
    "message": "No query results for model [App\\Models\\User]."
}
```

### 11. Update User (Requiere Auth)
```
PUT /api/users/{id}
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body (todos los campos son opcionales):
{
    "name": "Nombre Actualizado",
    "email": "nuevo@email.com",
    "password": "newpassword",        // Optional
    "password_confirmation": "newpassword",
    "role": "pm"                      // Solo admin puede cambiar rol
}

Response (200):
{
    "message": "User updated successfully",
    "user": {
        "id": 1,
        "name": "Nombre Actualizado",
        "email": "nuevo@email.com",
        "role": "admin",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 12. Delete User (Requiere Auth - Admin)
```
DELETE /api/users/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "User deleted successfully"
}

Response (422 - Error - No puedes borrarte a ti mismo):
{
    "message": "You cannot delete your own account"
}
```

---

## Projects

### 13. List Projects (Requiere Auth)
```
GET /api/projects
Authorization: Bearer {TOKEN}

Query Parameters (Optional):
- status: draft | discovery | execution | delivered
- client_name: string (búsqueda partial)
- search: string (busca en name o client_name)
- sort_by: created_at | name | status (default: created_at)
- sort_order: asc | desc (default: desc)
- per_page: number (default: 15)

Ejemplo:
GET /api/projects?status=execution&search=Cliente&sort_by=name&per_page=10

Response (200):
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "name": "Mi Proyecto",
            "client_name": "Cliente Ejemplo",
            "status": "draft",
            "created_by": 1,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "deleted_at": null
        }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/projects?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/projects?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/projects",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

### 14. Create Project (Requiere Auth)
```
POST /api/projects
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "name": "Mi Proyecto",           // Required
    "client_name": "Cliente Ejemplo", // Required
    "status": "draft"                 // Optional: draft, discovery, execution, delivered (default: draft)
}

Response (201):
{
    "message": "Project created successfully",
    "project": {
        "id": 1,
        "name": "Mi Proyecto",
        "client_name": "Cliente Ejemplo",
        "status": "draft",
        "created_by": 1,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}

Response (403 - Forbidden):
{
    "message": "This action is unauthorized."
}
```

### 15. Get Project (Requiere Auth)
```
GET /api/projects/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "id": 1,
    "name": "Mi Proyecto",
    "client_name": "Cliente Ejemplo",
    "status": "draft",
    "created_by": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "deleted_at": null
}
```

### 16. Update Project (Requiere Auth)
```
PUT /api/projects/{id}
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body (todos los campos son opcionales):
{
    "name": "Nombre Actualizado",
    "client_name": "Nuevo Cliente",
    "status": "discovery"  // draft, discovery, execution, delivered
}

Response (200):
{
    "message": "Project updated successfully",
    "project": {
        "id": 1,
        "name": "Nombre Actualizado",
        "client_name": "Nuevo Cliente",
        "status": "discovery",
        "created_by": 1,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}

Response (422 - Gate Blocked - No se puede mover a Execution):
{
    "message": "Cannot move to Execution phase",
    "error": "Required artifacts not completed",
    "missing_artifacts": [
        {
            "id": 1,
            "type": "strategic_alignment",
            "status": "not_started"
        },
        {
            "id": 2,
            "type": "big_picture",
            "status": "not_started"
        }
    ],
    "gate_blocked": true
}
```

### 17. Delete Project (Requiere Auth)
```
DELETE /api/projects/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Project deleted successfully"
}
```

### 18. Archive Project (Requiere Auth)
```
POST /api/projects/{id}/archive
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Project archived successfully",
    "project": {
        "id": 1,
        "name": "Mi Proyecto",
        "client_name": "Cliente Ejemplo",
        "status": "delivered",
        "created_by": 1,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "deleted_at": null
    }
}
```

### 19. Restore Project (Requiere Auth)
```
POST /api/projects/{id}/restore
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Project restored successfully",
    "project": {
        "id": 1,
        "name": "Mi Proyecto",
        "client_name": "Cliente Ejemplo",
        "status": "draft",
        "created_by": 1,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "deleted_at": null
    }
}
```

---

## Artifacts

### Artifact Types
| Type | Description |
|------|-------------|
| strategic_alignment | Strategic Alignment |
| big_picture | Big Picture |
| domain_breakdown | Domain Breakdown |
| module_matrix | Module Matrix |
| module_engineering | Module Engineering |
| system_architecture | System Architecture |
| phase_scope | Phase Scope |

### Artifact Statuses
| Status | Description |
|--------|-------------|
| not_started | Not Started |
| in_progress | In Progress |
| blocked | Blocked |
| done | Done |

### 20. List Artifacts (Requiere Auth)
```
GET /api/artifacts
Authorization: Bearer {TOKEN}

Query Parameters (Optional):
- project_id: number
- type: strategic_alignment | big_picture | domain_breakdown | module_matrix | module_engineering | system_architecture | phase_scope
- status: not_started | in_progress | blocked | done
- owner_user_id: number
- sort_by: created_at | status | type (default: created_at)
- sort_order: asc | desc (default: desc)
- per_page: number (default: 15)

Ejemplo:
GET /api/artifacts?project_id=1&status=not_started&sort_by=status

Response (200):
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "project_id": 1,
            "type": "strategic_alignment",
            "status": "not_started",
            "owner_user_id": null,
            "content_json": {...},
            "completed_at": null,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "blocking_reason": null
        }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "links": [],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/artifacts",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

### 21. Get Artifacts by Project (Requiere Auth)
```
GET /api/projects/{project_id}/artifacts
Authorization: Bearer {TOKEN}

Response (200):
[
    {
        "id": 1,
        "project_id": 1,
        "type": "strategic_alignment",
        "status": "not_started",
        "owner_user_id": null,
        "content_json": {...},
        "completed_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "blocking_reason": null
    }
]
```

### 22. Create Artifact (Requiere Auth)
```
POST /api/artifacts
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "project_id": 1,                    // Required
    "type": "strategic_alignment",       // Required: ver tipos arriba
    "status": "not_started",              // Optional: not_started, in_progress, blocked, done (default: not_started)
    "owner_user_id": 1,                   // Optional
    "content_json": {                     // Optional
        "transformation": "Transformación digital",
        "supported_decisions": ["Decisión 1", "Decisión 2"],
        "measurable_success": [
            {"metric": "KPIs", "target": "100%"}
        ],
        "out_of_scope": ["Scope 1"]
    }
}

Response (201):
{
    "message": "Artifact created successfully",
    "artifact": {
        "id": 1,
        "project_id": 1,
        "type": "strategic_alignment",
        "status": "not_started",
        "owner_user_id": 1,
        "content_json": {...},
        "completed_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 23. Create Artifact via Project (Requiere Auth)
```
POST /api/projects/{project_id}/artifacts
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "type": "big_picture",
    "status": "not_started",
    "content_json": {
        "ecosystem_vision": "Visión del ecosistema",
        "impacted_domains": ["Domain 1", "Domain 2"],
        "success_definition": "Definición de éxito"
    }
}

Response (201):
{
    "message": "Artifact created successfully",
    "artifact": {...}
}
```

### 24. Get Artifact (Requiere Auth)
```
GET /api/artifacts/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "id": 1,
    "project_id": 1,
    "type": "strategic_alignment",
    "status": "not_started",
    "owner_user_id": 1,
    "content_json": {
        "transformation": "Transformación digital"
    },
    "completed_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

### 25. Update Artifact (Requiere Auth)
```
PUT /api/artifacts/{id}
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body (todos los campos son opcionales):
{
    "type": "big_picture",
    "status": "in_progress",
    "owner_user_id": 2,
    "content_json": {
        "transformation": "Nueva transformación",
        "supported_decisions": ["Decisión A"]
    }
}

Response (200):
{
    "message": "Artifact updated successfully",
    "artifact": {...}
}

Response (422 - Gate Blocked):
{
    "message": "Cannot complete this artifact",
    "error": "Cannot mark as done: dependent artifacts not completed",
    "gate_blocked": true
}
```

### 26. Complete Artifact (Requiere Auth)
```
POST /api/artifacts/{id}/complete
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Artifact marked as completed",
    "artifact": {
        "id": 1,
        "project_id": 1,
        "type": "strategic_alignment",
        "status": "done",
        "owner_user_id": 1,
        "content_json": {...},
        "completed_at": "2024-01-01T00:00:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}

Response (422 - Gate Blocked):
{
    "message": "Cannot complete this artifact",
    "error": "Content is required to complete artifact",
    "gate_blocked": true
}
```

### 27. Change Artifact Status (Requiere Auth)
```
POST /api/artifacts/{id}/change-status
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "status": "in_progress"  // Required: not_started, in_progress, blocked, done
}

Response (200):
{
    "message": "Artifact status updated successfully",
    "artifact": {...}
}

Response (422 - Gate Blocked):
{
    "message": "Cannot complete this artifact",
    "error": "Cannot mark as done: content_json is required",
    "gate_blocked": true
}
```

### 28. Assign Artifact (Requiere Auth)
```
POST /api/artifacts/{id}/assign
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "owner_user_id": 1  // Required: ID del usuario o null para desasignar
}

Response (200):
{
    "message": "Artifact assigned successfully",
    "artifact": {
        "id": 1,
        "project_id": 1,
        "type": "strategic_alignment",
        "status": "not_started",
        "owner_user_id": 1,
        "content_json": {...},
        "completed_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 29. Delete Artifact (Requiere Auth)
```
DELETE /api/artifacts/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Artifact deleted successfully"
}
```

---

## Modules

### Module Statuses
| Status | Description |
|--------|-------------|
| draft | Draft |
| validated | Validated |
| ready_for_build | Ready for Build |

### 30. List Modules (Requiere Auth)
```
GET /api/modules
Authorization: Bearer {TOKEN}

Query Parameters (Optional):
- project_id: number
- domain: string
- status: draft | validated | ready_for_build
- sort_by: created_at | domain | name | status (default: created_at)
- sort_order: asc | desc (default: desc)
- per_page: number (default: 15)

Ejemplo:
GET /api/modules?project_id=1&status=draft&sort_by=domain

Response (200):
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "project_id": 1,
            "domain": "Finance",
            "name": "Payment Module",
            "status": "draft",
            "objective": "Process payments",
            "inputs": ["card_data", "amount"],
            "outputs": ["transaction_id", "receipt"],
            "data_structure": null,
            "logic_rules": null,
            "responsibility": null,
            "failure_scenarios": null,
            "audit_trail_requirements": null,
            "dependencies": null,
            "version_note": null,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "links": [],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/modules",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

### 31. Get Modules by Project (Requiere Auth)
```
GET /api/projects/{project_id}/modules
Authorization: Bearer {TOKEN}

Response (200):
[
    {
        "id": 1,
        "project_id": 1,
        "domain": "Finance",
        "name": "Payment Module",
        "status": "draft",
        "objective": "Process payments",
        "inputs": ["card_data", "amount"],
        "outputs": ["transaction_id", "receipt"],
        ...
    }
]
```

### 32. Create Module (Requiere Auth)
```
POST /api/modules
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "project_id": 1,                          // Required
    "domain": "Finance",                       // Required
    "name": "Payment Module",                  // Required
    "status": "draft",                         // Optional: draft, validated, ready_for_build (default: draft)
    "objective": "Process payments",           // Optional
    "inputs": ["card_data", "amount"],         // Optional: array
    "outputs": ["transaction_id", "receipt"],  // Optional: array
    "data_structure": "JSON schema",           // Optional
    "logic_rules": "Business rules",          // Optional
    "responsibility": "Process payment",      // Optional
    "failure_scenarios": "Card declined",     // Optional
    "audit_trail_requirements": "...",         // Optional
    "dependencies": ["external_api"],         // Optional: array
    "version_note": "v1.0"                     // Optional
}

Response (201):
{
    "message": "Module created successfully",
    "module": {
        "id": 1,
        "project_id": 1,
        "domain": "Finance",
        "name": "Payment Module",
        "status": "draft",
        "objective": "Process payments",
        "inputs": ["card_data", "amount"],
        "outputs": ["transaction_id", "receipt"],
        "data_structure": "JSON schema",
        "logic_rules": "Business rules",
        "responsibility": "Process payment",
        "failure_scenarios": "Card declined",
        "audit_trail_requirements": null,
        "dependencies": ["external_api"],
        "version_note": "v1.0",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 33. Create Module via Project (Requiere Auth)
```
POST /api/projects/{project_id}/modules
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "domain": "Finance",
    "name": "Payment Module",
    "status": "draft",
    "objective": "Process payments",
    "inputs": ["card_data", "amount"],
    "outputs": ["transaction_id", "receipt"]
}

Response (201):
{
    "message": "Module created successfully",
    "module": {...}
}
```

### 34. Get Module (Requiere Auth)
```
GET /api/modules/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "id": 1,
    "project_id": 1,
    "domain": "Finance",
    "name": "Payment Module",
    "status": "draft",
    "objective": "Process payments",
    "inputs": ["card_data", "amount"],
    "outputs": ["transaction_id", "receipt"],
    "data_structure": "JSON schema",
    "logic_rules": "Business rules",
    "responsibility": "Process payment",
    "failure_scenarios": "Card declined",
    "audit_trail_requirements": null,
    "dependencies": ["external_api"],
    "version_note": "v1.0",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

### 35. Update Module (Requiere Auth)
```
PUT /api/modules/{id}
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body (todos los campos son opcionales):
{
    "domain": "Logistics",
    "name": "Updated Module Name",
    "status": "validated",
    "objective": "New objective",
    "inputs": ["new_input"],
    "outputs": ["new_output"],
    "data_structure": "New schema",
    "logic_rules": "New rules",
    "responsibility": "New responsibility",
    "failure_scenarios": "New failure scenarios",
    "audit_trail_requirements": "New audit requirements",
    "dependencies": ["new_dep"],
    "version_note": "v2.0"
}

Response (200):
{
    "message": "Module updated successfully",
    "module": {...}
}
```

### 36. Validate Module (Requiere Auth)
```
POST /api/modules/{id}/validate
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Module validated successfully",
    "module": {
        "id": 1,
        "project_id": 1,
        "domain": "Finance",
        "name": "Payment Module",
        "status": "validated",
        "objective": "Process payments",
        "inputs": ["card_data", "amount"],
        "outputs": ["transaction_id", "receipt"],
        "responsibility": "Process payment",
        ...
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}

Response (422 - Validation Required Fields Missing):
{
    "message": "The given data was invalid.",
    "errors": {
        "objective": ["The objective field is required for validation."],
        "inputs": ["The inputs field must have at least 1 item."],
        "outputs": ["The outputs field must have at least 1 item."],
        "responsibility": ["The responsibility field is required for validation."]
    }
}
```

### 37. Change Module Status (Requiere Auth)
```
POST /api/modules/{id}/change-status
Authorization: Bearer {TOKEN}
Content-Type: application/json

Body:
{
    "status": "ready_for_build"  // Required: draft, validated, ready_for_build
}

Response (200):
{
    "message": "Module status updated successfully",
    "module": {...}
}
```

### 38. Delete Module (Requiere Auth)
```
DELETE /api/modules/{id}
Authorization: Bearer {TOKEN}

Response (200):
{
    "message": "Module deleted successfully"
}
```

---

## Cómo usar Postman

1. **Crear una colección** llamada "TCG API"

2. **Configurar autenticación**:
   - Ve a la pestaña "Authorization"
   - Selecciona tipo "Bearer Token"
   - Usa el token del login

3. **Para endpoints protegidos**:
   - Añade el header: `Authorization: Bearer {TU_TOKEN}`

4. **Orden recomendado de prueba**:
   1. Login (guardar token)
   2. GET /projects
   3. POST /projects
   4. GET /projects/{id}
   5. POST /artifacts
   6. POST /modules

---

## Roles de Usuario

| Rol | Descripción |
|-----|-------------|
| admin | Acceso total |
| pm | Gestionar proyectos, artifacts, modules |
| engineer | Editar modules, ver artifacts |
| viewer | Solo lectura |

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | No encontrado |
| 422 | Error de validación |
| 500 | Error del servidor |

---

## Sistema de Gates/Bloqueos

### Gate de Proyectos
- **Bloqueo**: No se puede mover un proyecto de `discovery` a `execution` si los artifacts requeridos (`strategic_alignment`, `big_picture`) no están completados (`status: done`).

### Gate de Artifacts
- **Bloqueo**: No se puede marcar un artifact como `done` si:
  - No tiene `content_json` definido
  - Hay artifacts dependientes que no están completados

### Gate de Módulos
- **Validación**: Para mover un módulo a `validated`, debe tener:
  - `objective` no vacío
  - Al menos 1 `input`
  - Al menos 1 `output`
  - `responsibility` no vacío

---

## Campos Requeridos por Entidad

### User
| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| name | string | Sí | max:255 |
| email | string | Sí | email único |
| password | string | Sí | min:8, confirmed |
| role | string | Sí (solo admin) | admin, pm, engineer, viewer |

### Project
| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| name | string | Sí | max:255 |
| client_name | string | Sí | max:255 |
| status | string | No | draft, discovery, execution, delivered |

### Artifact
| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| project_id | integer | Sí | existe en projects |
| type | string | Sí | ver tipos de artifacts |
| status | string | No | not_started, in_progress, blocked, done |
| owner_user_id | integer | No | existe en users |
| content_json | object | No | objecto JSON |

### Module
| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| project_id | integer | Sí | existe en projects |
| domain | string | Sí | max:255 |
| name | string | Sí | max:255 |
| status | string | No | draft, validated, ready_for_build |
| objective | string | No* | *Requerido para validar |
| inputs | array | No* | *Requerido para validar (min:1) |
| outputs | array | No* | *Requerido para validar (min:1) |
| responsibility | string | No* | *Requerido para validar |
| data_structure | string | No | |
| logic_rules | string | No | |
| failure_scenarios | string | No | |
| audit_trail_requirements | string | No | |
| dependencies | array | No | |
| version_note | string | No | |

---

## Auditoría

Todos los cambios importantes generan eventos de auditoría que incluyen:
- Entity type (project, artifact, module, user)
- Entity ID
- Acción realizada (created, updated, deleted, status_changed, etc.)
- Usuario que realizó el cambio
- Estado anterior y nuevo
- Timestamp

