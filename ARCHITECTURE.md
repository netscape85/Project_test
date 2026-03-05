# Architecture Notes - TCG Engineering Hub MVP

## 🏗️ Architecture Decisions

### 1. API-First Architecture
**Decision**: Separate Laravel API backend and Vue 3 frontend.

**Rationale**: 
- Clear separation of concerns
- Frontend can be replaced/scaled independently
- API can serve multiple clients (web, mobile, CLI)
- Easier to test and maintain

### 2. Domain-Driven Design
**Decision**: Business logic in Services, not Controllers.

**Implementation**:
- `ArtifactGateService`: Encapsulates all gate validation logic
- Controllers are thin, only handle HTTP concerns
- Policies handle authorization, Services handle business rules

**Example**: Gate validation is centralized in one service, making it easy to modify rules without touching multiple controllers.

### 3. Policy-Based Authorization (RBAC)
**Decision**: Use Laravel Policies instead of hardcoded role checks.

**Benefits**:
- Centralized authorization logic
- Easy to test and modify
- Follows Laravel best practices
- No `if ($user->role === 'admin')` in controllers

**Implementation**: 4 policies (Project, Artifact, Module, User) with granular permissions.

### 4. Audit Trail with JSON Diffs
**Decision**: Store `before_json` and `after_json` for all changes.

**Rationale**:
- Full traceability of changes
- Can reconstruct history
- Useful for compliance and debugging
- Minimal storage overhead

### 5. Structured Content (JSON)
**Decision**: Store artifact content as JSON, not files.

**Benefits**:
- Queryable and searchable
- Easy to validate
- No file storage complexity
- Fast to render in UI

**Trade-off**: Not suitable for large documents, but perfect for MVP structured data.

### 6. Sanctum for Authentication
**Decision**: Laravel Sanctum for SPA authentication.

**Rationale**:
- Lightweight and simple
- Perfect for SPA (Vue 3)
- Built-in CSRF protection
- Easy token management

### 7. Soft Deletes for Projects
**Decision**: Archive instead of hard delete.

**Benefits**:
- Data preservation
- Can restore if needed
- Audit trail remains intact

## 🔄 Data Flow

```
User Action (Vue) 
  → API Request (axios)
    → Route (api.php)
      → Controller (thin)
        → Policy Check (authorization)
          → Service (business logic)
            → Model (data access)
              → Database
                → Audit Event (logged)
```

## 📦 Key Components

### Backend
- **Controllers**: HTTP layer only
- **Services**: Business logic (gates, validation)
- **Policies**: Authorization rules
- **Models**: Data access + relationships
- **Migrations**: Database schema

### Frontend
- **Views**: Page components
- **Composables**: Reusable logic (useAuth)
- **Plugins**: API client, Vuetify
- **Components**: Reusable UI (Toolbar, Sidebar, Footer)

## 🎯 Design Patterns Used

1. **Repository Pattern**: Models act as repositories
2. **Service Layer**: Business logic separation
3. **Policy Pattern**: Authorization logic
4. **Observer Pattern**: Audit events on model changes
5. **Factory Pattern**: Database factories for testing

## 🚀 What Would Be Improved Next

### Short Term (1-2 weeks)
1. **Templates System**: Create projects from templates with pre-configured artifacts
2. **Export to JSON**: Download project data for backup/sharing
3. **File Attachments**: Allow uploading documents to artifacts
4. **Email Notifications**: Notify users of assignments and status changes
5. **Advanced Search**: Full-text search across projects/artifacts/modules

### Medium Term (1-2 months)
1. **Real-time Updates**: WebSockets for live collaboration
2. **Version Control**: Track artifact/module versions with rollback
3. **Dashboard Analytics**: Charts and metrics for project progress
4. **Bulk Operations**: Batch update multiple items
5. **API Versioning**: `/api/v1/` for future compatibility

### Long Term (3-6 months)
1. **Multi-tenancy**: Support multiple organizations
2. **Advanced RBAC**: Custom roles and permissions
3. **Integration APIs**: Connect with external tools (Jira, Slack)
4. **Mobile App**: Native iOS/Android apps
5. **AI Assistance**: Suggest module dependencies, validate completeness

## 🔒 Security Considerations

- ✅ CSRF protection (Sanctum)
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS prevention (Vue escaping)
- ✅ Authorization on every endpoint (Policies)
- ✅ Password hashing (bcrypt)
- ✅ Rate limiting (Laravel throttle)

## 📊 Performance Considerations

- ✅ Eager loading to prevent N+1 queries
- ✅ Pagination for large lists
- ✅ Database indexes on foreign keys
- ✅ Caching strategy ready (Redis/Memcached)
- ⚠️ Future: Queue jobs for heavy operations

## 🧪 Testing Strategy

- **Feature Tests**: Test business rules and gates (4 tests implemented)
- **Unit Tests**: Test individual services/helpers (can be expanded)
- **Integration Tests**: Test API endpoints with authentication
- **Frontend Tests**: E2E tests with Cypress (future)

## 📝 Code Quality Standards

- PSR-12 coding standard (Laravel Pint)
- Type hints on all methods
- Descriptive variable/method names
- Comments only where necessary (self-documenting code)
- Single Responsibility Principle
- DRY (Don't Repeat Yourself)

---

**Total Development Time**: ~40-60 hours for MVP
**Lines of Code**: ~5,000 (backend) + ~3,000 (frontend)
**Test Coverage**: Core business rules covered
