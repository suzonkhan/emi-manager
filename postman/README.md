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

### � **Device Control** (23 Commands)
- **Register Device** - Register device with FCM token (public endpoint)
- **Get Device Info** - View device status and capabilities
- **Lock/Unlock Device** - Remote device lock control
- **Camera Control** - Enable/disable device camera
- **Bluetooth Control** - Enable/disable bluetooth
- **USB Control** - Lock/unlock USB port
- **App Control** - Hide/unhide apps from launcher
- **Password Management** - Set/remove device lock password
- **Reboot Device** - Restart device remotely
- **Remove App** - Uninstall EMI app
- **Wipe Device** - Factory reset (use with caution!)
- **Show Message** - Display custom notification message
- **Reminder Screen** - Show full-screen payment reminder
- **Reminder Audio** - Play audio reminder
- **Wallpaper Control** - Set/remove custom wallpaper
- **Request Location** - Get device GPS coordinates
- **Call Control** - Enable/disable phone call functionality
- **Get Command History** - View all sent commands
- **Get Available Commands** - List all device commands

### 🪙 **Token Management**
- **Get Tokens** - List tokens based on hierarchy
- **Generate Tokens** - Create new tokens (Super Admin only)
- **Assign Token** - Assign token to subordinate user
- **Distribute Tokens** - Bulk distribute to multiple dealers
- **Token Statistics** - View token counts and status
- **Get Assignable Users** - List users who can receive tokens

### 👤 **Customer Management** (7 Endpoints)
- **Get Customers** - List customers with filters
- **Create Customer** - Register new customer with EMI details
- **Get Customer Details** - View complete customer info
- **Update Customer Status** - Approve/reject customer
- **Customer Statistics** - View customer counts and loan data
- **Upload Customer Documents** - Upload NID and photos
- **Delete Customer** - Remove customer and all associated data

### 📊 **Reports**
- **Generate Report** - Create report (7 types: sales, installments, collections, products, customers, dealers, sub-dealers)
- **Download Report PDF** - Download generated report as PDF
- **Get Dealers for Filter** - List dealers for report filtering
- **Get Sub-Dealers for Filter** - List sub-dealers for report filtering

### 📨 **Preset Messages** (10 Endpoints)
- **Get All Preset Messages** - List all user's preset messages
- **Get Available Commands** - List 18 commands that support presets
- **Create Preset Message (Lock Device)** - Auto-message when device is locked
- **Create Preset Message (Unlock Device)** - Auto-message when device is unlocked
- **Create Preset Message (Disable Call)** - Auto-message when calls are disabled
- **Create Preset Message (Request Location)** - Auto-message when location is requested
- **Get Specific Preset Message** - View details of one preset
- **Update Preset Message** - Modify existing preset message
- **Toggle Preset Message Status** - Enable/disable preset without deletion
- **Delete Preset Message** - Permanently remove preset message

**Automatic Message Delivery**: When you execute a device command (like lock/unlock), the system automatically checks if you have an active preset message for that command and sends it to the device. No manual message entry needed!

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

### **Device Registration Request (Public - No Auth)**
```json
{
  "serial_number": "R2Q5X08F00Y",
  "imei1": "223762838218759",
  "fcm_token": "eXXX...firebase_cloud_messaging_token...XXXe"
}
```

### **Device Command Request**
```json
{
  "customer_id": 1
}
```

### **Lock Device Command**
```json
{
  "customer_id": 1
}
```

### **Reset Password Command**
```json
{
  "customer_id": 1,
  "password": "1234"
}
```

### **Show Message Command**
```json
{
  "customer_id": 1,
  "title": "Payment Reminder",
  "message": "আপনার এই মাসের কিস্তি বাকি আছে।"
}
```

### **Set Wallpaper Command**
```json
{
  "customer_id": 1,
  "image_url": "https://example.com/reminder-wallpaper.jpg"
}
```

### **Hide App Command**
```json
{
  "customer_id": 1,
  "package_name": "com.facebook.katana"
}
```

### **Lock USB Command**
```json
{
  "customer_id": 1
}
```

### **Unlock USB Command**
```json
{
  "customer_id": 1
}
```

### **Preset Message Requests**

#### **Create Preset Message for Lock Device**
```json
{
  "command": "LOCK_DEVICE",
  "title": "⚠️ Payment Required",
  "message": "Your device has been locked due to missed payment. Please pay your installment to unlock. Contact: 01700000000",
  "is_active": true
}
```

#### **Create Preset Message for Unlock Device**
```json
{
  "command": "UNLOCK_DEVICE",
  "title": "✅ Thank You!",
  "message": "Thank you for your payment! Your device has been unlocked. Next payment due date will be notified.",
  "is_active": true
}
```

#### **Create Preset Message for Disable Call**
```json
{
  "command": "DISABLE_CALL",
  "title": "📵 Calls Restricted",
  "message": "Phone calls have been temporarily restricted due to overdue payment. Please clear your balance to restore service.",
  "is_active": true
}
```

#### **Create Preset Message for Request Location**
```json
{
  "command": "REQUEST_LOCATION",
  "title": "📍 Location Request",
  "message": "We are requesting your device location for verification purposes. This helps us serve you better and ensure security.",
  "is_active": true
}
```

#### **Update Preset Message**
```json
{
  "title": "⚠️ Updated Payment Reminder",
  "message": "Your device has been locked. Please contact us immediately at 01700000000 to resolve payment issues.",
  "is_active": true
}
```

### **Create Customer Request**
```json
{
  "name": "রহিম উদ্দিন",
  "email": "rahim@example.com",
  "mobile": "01712345678",
  "nid_no": "1234567890123",
  "present_address": {
    "street_address": "১২৩ মুক্তিযোদ্ধা সরণি",
    "landmark": "ঢাকা মেডিকেল কলেজের কাছে",
    "postal_code": "1000",
    "division_id": 1,
    "district_id": 1,
    "upazilla_id": 1
  },
  "permanent_address": {
    "street_address": "৪৫৬ পুরাতন ঢাকা",
    "landmark": "লালবাগ কেল্লার পাশে",
    "postal_code": "1211",
    "division_id": 1,
    "district_id": 1,
    "upazilla_id": 2
  },
  "product_type": "mobile",
  "product_model": "Samsung Galaxy S23",
  "product_price": 85000,
  "down_payment": 15000,
  "emi_duration_months": 12,
  "imei_1": "123456789012345",
  "imei_2": "543210987654321",
  "serial_number": "SN123456789"
}
```

### **Generate Report Request**
```json
{
  "report_type": "sales",
  "start_date": "2024-01-01",
  "end_date": "2025-10-14",
  "dealer_id": null
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