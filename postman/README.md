# EMI Manager API - Postman Collection

This Postman collection provides comprehensive API testing for the EMI Manager Laravel application. It includes all endpoints with proper authentication, validation, and sample data.

## 📋 Collection Overview

### 🔐 **Authentication**
- **Login** - Authenticate with email/phone and password
- **Logout** - Invalidate current access token
- **Get Profile** - Retrieve authenticated user profile
- **Change Password** - Update user password

### 👥 **User Management**
- **Get Users** - List users based on hierarchy
- **Create User** - Create new user with complete profile
- **Get User Details** - View specific user information
- **Update User** - Modify user information
- **Reset User Password** - Admin password reset
- **Get Available Roles** - View assignable roles

### 📍 **Locations**
- **Get Divisions** - List all divisions
- **Get Districts by Division** - List districts for a division
- **Get Upazillas by District** - List upazillas for a district

### 📊 **Dashboard**
- **Get Dashboard Stats** - User-specific or global statistics

### 🔧 **System**
- **API Test** - Connectivity test endpoint
- **Health Check** - Service health monitoring

### 🐛 **Debug (Development Only)**
- **Debug Users** - View all users with roles
- **Debug Roles** - View all roles and permissions

## 🚀 Quick Start

### 1. **Import Collection**
- Import the `48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json` file into Postman
- Collection variables will be automatically set

### 2. **Set Environment Variables**
The collection includes these variables:
- `base_url`: `http://localhost:8000/api` (auto-configured)
- `token`: `` (auto-populated after login)

### 3. **Login Process**
1. **First**, test connectivity with the **API Test** endpoint
2. **Then**, use the **Login** request with valid credentials:
   ```json
   {
     "email_or_phone": "admin@example.com",
     "password": "password123"
   }
   ```
3. **Token will be automatically extracted** and saved for subsequent requests

## 🔑 Authentication

### **Bearer Token Authentication**
- Collection is configured with Bearer token authentication
- Token is automatically extracted from login response
- All protected endpoints use `Authorization: Bearer {{token}}`

### **Required Headers**
- `Content-Type: application/json` (for POST/PUT requests)
- `Accept: application/json` (for all requests)
- `Authorization: Bearer {{token}}` (for protected endpoints)

## 📝 Sample Data

### **Login Request**
```json
{
  "email_or_phone": "admin@example.com",
  "password": "password123"
}
```

### **Create User Request**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "01712345678",
  "password": "password123",
  "role": "customer",
  "present_address": {
    "street_address": "123 Main Street",
    "landmark": "Near City Bank",
    "postal_code": "1200",
    "division_id": 1,
    "district_id": 1,
    "upazilla_id": 1
  },
  "permanent_address": {
    "street_address": "456 Home Street",
    "landmark": "Near School",
    "postal_code": "1201",
    "division_id": 1,
    "district_id": 1,
    "upazilla_id": 1
  },
  "bkash_merchant_number": "01712345678",
  "nagad_merchant_number": "01712345679"
}
```

### **Update User Request**
```json
{
  "name": "John Doe Updated",
  "email": "john.updated@example.com",
  "phone": "01712345679",
  "bkash_merchant_number": "01712345680",
  "nagad_merchant_number": "01712345681",
  "is_active": true
}
```

### **Change Password Request**
```json
{
  "current_password": "oldpassword123",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

### **Reset Password Request**
```json
{
  "new_password": "newpassword123",
  "can_change_password": true
}
```

## 🏗️ Collection Features

### **Automatic Token Management**
- Login response automatically extracts and stores token
- Token is used for all subsequent authenticated requests
- No manual token copying required

### **Response Validation**
- Automatic tests for successful status codes (200, 201, 204)
- JSON response format validation
- API response structure validation

### **Environment Setup**
- Base URL automatically configured for local development
- Variables can be easily modified for different environments

### **Request Organization**
- Logical folder structure for different API sections
- Descriptive names and detailed descriptions
- Sample data for quick testing

## 🔧 Configuration

### **Local Development**
```
base_url: http://localhost:8000/api
```

### **Production/Staging**
Update the `base_url` variable to match your environment:
```
base_url: https://your-api-domain.com/api
```

## 📊 Response Examples

### **Successful Login Response**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "unique_id": "USR-001",
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "super_admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer"
  }
}
```

### **User List Response**
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "unique_id": "USR-001",
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "01712345678",
        "role": "customer",
        "is_active": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

## 🚨 Error Handling

### **Validation Error Response**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

### **Authentication Error Response**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### **Authorization Error Response**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

## 🧪 Testing Workflow

### **Complete Testing Sequence**
1. **System Check**: Run "API Test" to verify connectivity
2. **Authentication**: Login with valid credentials
3. **Profile**: Get user profile to verify authentication
4. **User Management**: Test user CRUD operations
5. **Locations**: Test location-based endpoints
6. **Dashboard**: Verify dashboard data access
7. **Debug**: Use debug endpoints in development

### **Role-Based Testing**
- **Super Admin**: Full access to all endpoints
- **Dealer/Sub-Dealer/Salesman**: Limited user management
- **Customer**: Profile and basic operations only

## 📝 Notes

- **Development Environment**: Debug endpoints only work in local/development
- **Hierarchy System**: User creation/access follows role hierarchy
- **Address Validation**: Location IDs must exist in database
- **Password Policy**: Minimum 8 characters required
- **Phone Format**: Bangladesh mobile format expected (01XXXXXXXXX)

## 🔗 Related Documentation

- [Laravel API Documentation](../docs/api.md)
- [Authentication Guide](../docs/authentication.md)
- [User Management Guide](../docs/user-management.md)
- [Database Schema](../docs/database.md)