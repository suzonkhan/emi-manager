# EMI Manager API System

A comprehensive Laravel-based API system for managing EMI (Easy Monthly Installment) operations with hierarchical user management and role-based access control.

## 🚀 Quick Start

### Server Setup
```bash
cd c:\laragon\www\emi-manager
php artisan serve
```

### Test Interface
Open: http://127.0.0.1:8000/test-api.html

### Super Admin Credentials
- **Email**: superadmin@emimanager.com
- **Phone**: +8801700000000
- **Password**: SuperAdmin@123

## 📊 System Architecture

### User Hierarchy
```
Super Admin
├── Dealer
│   ├── Sub Dealer
│   │   ├── Sales Man
│   │   │   └── Customer
```

### Role-Based Permissions
- **Super Admin**: Full system access, can create dealers
- **Dealer**: Can create sub-dealers and manage their hierarchy
- **Sub Dealer**: Can create salesmen and manage customers
- **Sales Man**: Can create and manage customers
- **Customer**: Basic profile management

## 🔧 Technical Stack

### Core Framework
- **Laravel**: 12.28.1 (Latest)
- **PHP**: 8.2+
- **Database**: MySQL
- **Authentication**: Laravel Sanctum (API tokens)

### Development Tools
- **Laravel Telescope**: Application monitoring
- **Laravel Debugbar**: Debug information
- **Query Detector**: N+1 query detection
- **Laravel Ray**: Advanced debugging
- **IDE Helper**: Enhanced IDE support

### Packages
- **Spatie Laravel Permission**: Role-based access control
- **Laravel Boost**: Development productivity

## 📡 API Endpoints

### Authentication
```
POST   /api/auth/login           # Login with email/phone
POST   /api/auth/logout          # Logout (revoke token)
GET    /api/auth/profile         # Get user profile
POST   /api/auth/change-password # Change password
```

### User Management
```
GET    /api/users                # List users (hierarchical)
POST   /api/users                # Create new user
GET    /api/users/{id}           # Get user details
PUT    /api/users/{id}           # Update user
DELETE /api/users/{id}           # Delete user
POST   /api/users/{id}/reset-password # Reset user password
GET    /api/users/available-roles # Get roles user can assign
```

### Locations
```
GET    /api/locations/divisions           # Get all divisions
GET    /api/locations/districts/{division} # Get districts by division
GET    /api/locations/upazillas/{district} # Get upazillas by district
```

### Dashboard
```
GET    /api/dashboard/stats      # Get dashboard statistics
```

### Debug (Development only)
```
GET    /api/debug/phpinfo        # PHP information
GET    /api/debug/routes         # List all routes
```

## 🗄️ Database Schema

### Users Table
- `id` - Primary key
- `unique_id` - Auto-generated unique identifier
- `name` - Full name (required)
- `email` - Email address (nullable, unique)
- `phone` - Phone number (required, unique)
- `password` - Encrypted password
- `parent_id` - Hierarchical parent reference
- `merchant_number` - Business identifier
- `emergency_contact` - Emergency contact number
- Role and permission fields via Spatie package

### Address System
- **divisions** - Top-level administrative divisions
- **districts** - District-level locations
- **upazillas** - Sub-district level
- **addresses** - User addresses with full location hierarchy

## 🔐 Security Features

### Authentication
- Laravel Sanctum API token authentication
- Email or phone number login support
- Secure password requirements (8+ chars, mixed case, numbers, symbols)

### Authorization
- Role-based access control with 5-tier hierarchy
- Permission-based endpoint protection
- Hierarchical user creation restrictions
- Self-password change limitations based on role

### Data Protection
- Encrypted passwords (bcrypt)
- API rate limiting
- CORS protection
- Input validation and sanitization

## 🛠️ Development Features

### Debugging Tools
- **Telescope**: Monitor requests, queries, cache, jobs
- **Debugbar**: Real-time performance metrics
- **Query Detector**: Identify performance issues
- **Ray**: Advanced debugging and inspection

### Code Quality
- **IDE Helper**: Enhanced IDE support with model hints
- **Laravel Boost**: Development productivity tools
- Comprehensive validation and error handling

## 📱 Testing

### Manual Testing
1. Open http://127.0.0.1:8000/test-api.html
2. Login with super admin credentials
3. Test various API endpoints through the interface

### API Testing Tools
- Use Postman, Insomnia, or curl for detailed API testing
- All endpoints return JSON responses
- Include `Authorization: Bearer {token}` header after login

## 🚦 Status

### ✅ Completed Features
- Laravel 12.28.1 installation and configuration
- API-only setup with Sanctum authentication
- Complete debugging suite installation
- Hierarchical user management system
- Role-based permission system
- Bangladesh location data structure
- Comprehensive API endpoints
- Test interface for manual testing

### 🔄 Next Steps
- Add EMI-specific business logic (loan management, payment tracking)
- Implement advanced reporting features
- Add file upload capabilities
- Create comprehensive API documentation
- Add automated testing suite

## 📞 Support

The system is ready for production use with the following capabilities:
- Multi-level user hierarchy management
- Secure API authentication
- Comprehensive location management
- Real-time debugging and monitoring
- Scalable role-based permissions

For additional features or customizations, the foundation provides a solid base for extending EMI-specific functionality.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
