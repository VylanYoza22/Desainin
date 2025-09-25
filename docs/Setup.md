# 🚀 Desainin - Order Management System

Complete order management system with WhatsApp integration and detailed status tracking for design services.

## 📋 Features

### ✅ **Core Features**
- **User Authentication** - Registration, login, profile management
- **Order Management** - Create, track, and manage orders
- **WhatsApp Integration** - Automatic notifications to admin and customers
- **Status Tracking** - Detailed progress tracking with percentages
- **Admin Dashboard** - Order management and status updates
- **Responsive Design** - Modern UI with Tailwind CSS

### 🎯 **WhatsApp Integration**
- Order notifications to admin
- Customer confirmations
- Feedback notifications
- Activity logging
- Phone number validation

### 📊 **Status System**
- 8 detailed status levels (10% - 100% progress)
- Timeline visualization
- Progress bars
- Status badges with colors
- Custom descriptions

## 🛠️ Installation & Setup

### **Prerequisites**
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- WhatsApp API provider account (Fonnte/Wablas)

### **1. Database Setup**
```sql
-- Create database
CREATE DATABASE desainin_db;

-- Import tables (run these SQL files in order):
-- 1. Basic users table (create manually or import)
-- 2. sql/create_orders_table.sql
-- 3. sql/add_whatsapp_column.sql
-- 4. sql/update_orders_detailed_status.sql
-- 5. sql/update_feedback_table.sql
-- 6. sql/add_profile_picture_column.sql
```

### **2. Configuration**

#### **Database Configuration**
Edit `includes/config.php`:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "desainin_db";
```

#### **WhatsApp API Configuration**
Edit `includes/whatsapp_config.php`:
```php
// For Fonnte
define('WHATSAPP_API_URL', 'https://api.fonnte.com/send');
define('WHATSAPP_TOKEN', 'YOUR_FONNTE_TOKEN_HERE');
define('ADMIN_WHATSAPP', '6281234567890');

// For Wablas (alternative)
// define('WHATSAPP_API_URL', 'https://console.wablas.com/api/send-message');
// define('WHATSAPP_TOKEN', 'YOUR_WABLAS_TOKEN_HERE');
```

### **3. File Permissions**
```bash
# Create required directories
mkdir logs uploads uploads/profiles

# Set permissions
chmod 755 logs uploads uploads/profiles
```

### **4. WhatsApp API Setup**

#### **Option 1: Fonnte (Recommended)**
1. Register at [fonnte.com](https://fonnte.com)
2. Get your API token
3. Update `WHATSAPP_TOKEN` in config

#### **Option 2: Wablas**
1. Register at [wablas.com](https://wablas.com)
2. Get your API token
3. Uncomment Wablas config lines

## 🎮 Usage

### **For Customers**
1. **Register/Login** - Create account or login
2. **Create Order** - Fill order form with WhatsApp number
3. **Track Progress** - View real-time order status
4. **Receive Updates** - Get WhatsApp notifications

### **For Admins**
1. **Access Admin Panel** - Visit `admin-orders.php?admin=demo`
2. **View Orders** - See all orders with details
3. **Update Status** - Change order status and progress
4. **Monitor Activity** - Check WhatsApp logs

## 📁 File Structure

```
PKK2/
├── assets/
│   ├── css/
│   │   ├── Style-Desainin-dark.css
│   │   └── promo-popup.css
│   └── js/
│       ├── Desainin.js
│       └── promo-popup.js
├── includes/
│   ├── config.php                 # Database configuration
│   ├── whatsapp_config.php        # WhatsApp API settings
│   ├── whatsapp_functions.php     # WhatsApp integration
│   └── status_functions.php       # Status management
├── logs/
│   └── whatsapp.log              # WhatsApp activity logs
├── sql/
│   ├── create_orders_table.sql
│   ├── add_whatsapp_column.sql
│   ├── update_orders_detailed_status.sql
│   └── [other SQL files]
├── uploads/
│   └── profiles/                 # Profile pictures
├── index.php                     # Landing page
├── register.php                  # User registration
├── login.php                     # User login
├── dashboard.php                 # User dashboard
├── order.php                     # Create new order
├── my-orders.php                 # User's orders
├── order-progress.php            # Order tracking
├── admin-orders.php              # Admin panel
├── edit-profile.php              # Profile management
└── simpan_feedback.php           # Feedback handling
```

## 🔧 Configuration Details

### **Status Definitions**
```php
'pending' => 10%          // Order received
'confirmed' => 20%        // Order confirmed
'payment_pending' => 30%  // Waiting payment
'payment_confirmed' => 40% // Payment received
'in_progress' => 60%      // Work in progress
'review' => 80%           // Under review
'final_review' => 90%     // Final review
'completed' => 100%       // Completed
'cancelled' => 0%         // Cancelled
```

### **WhatsApp Message Templates**
- **Order Notification** - Sent to admin
- **Order Confirmation** - Sent to customer
- **Feedback Notification** - Sent to admin

## 🚨 Security Notes

### **Production Checklist**
- [ ] Change database credentials
- [ ] Remove demo admin access
- [ ] Implement proper admin authentication
- [ ] Enable HTTPS
- [ ] Secure file upload directories
- [ ] Validate all user inputs
- [ ] Set proper file permissions

### **Admin Access**
Current demo mode: `admin-orders.php?admin=demo`
**⚠️ Remove this in production!**

## 🐛 Troubleshooting

### **Common Issues**

#### **WhatsApp Not Sending**
1. Check API token validity
2. Verify phone number format
3. Check `logs/whatsapp.log` for errors
4. Ensure internet connection

#### **Database Errors**
1. Verify database credentials
2. Check if tables exist
3. Run SQL update scripts
4. Check MySQL service status

#### **File Upload Issues**
1. Check directory permissions
2. Verify upload directory exists
3. Check PHP upload limits

### **Debug Mode**
Enable error reporting in development:
```php
// Add to config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

### **WhatsApp API Providers**
- **Fonnte**: [fonnte.com](https://fonnte.com)
- **Wablas**: [wablas.com](https://wablas.com)

### **Documentation**
- `README_Status_System.md` - Detailed status system guide
- `README_WhatsApp.md` - WhatsApp integration guide

## 🔄 Updates & Maintenance

### **Database Updates**
Run `update_database.php` after SQL schema changes.

### **Log Maintenance**
WhatsApp logs are stored in `logs/whatsapp.log` - rotate regularly.

### **Backup**
Regular backup of:
- Database
- Upload files
- Configuration files

---

**🎉 Your order management system is ready to use!**

For questions or support, check the documentation files or review the code comments.
