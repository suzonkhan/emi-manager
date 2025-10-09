# Simple Message Display API

## 🎯 Purpose

Send a **simple notification/message** to customer's device screen **without executing any command**.

Perfect for:
- ✅ Payment reminders
- ✅ General announcements
- ✅ EMI due date notifications
- ✅ Promotional messages
- ✅ Customer service updates
- ✅ Holiday greetings

---

## 📡 API Endpoint

```
POST /api/devices/send-message
```

### Authentication
```
Authorization: Bearer {token}
```

---

## 🔧 Request Parameters

### Request Body (JSON)

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `customer_id` | integer | ✅ Yes | Customer ID whose device will receive message |
| `message` | string | ✅ Yes | Message to display (max 500 characters) |
| `title` | string | ❌ No | Message title (default: "Notification") |

---

## 📝 Request Examples

### Example 1: Simple Payment Reminder

```bash
curl -X POST "http://your-api.com/api/devices/send-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "message": "আপনার EMI পেমেন্ট আগামীকাল দিতে হবে। Your EMI payment is due tomorrow.",
    "title": "পেমেন্ট রিমাইন্ডার / Payment Reminder"
  }'
```

### Example 2: General Announcement

```bash
curl -X POST "http://your-api.com/api/devices/send-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "message": "আমাদের অফিস ঈদের ছুটিতে বন্ধ থাকবে। Office closed during Eid holidays.",
    "title": "ছুটির নোটিশ / Holiday Notice"
  }'
```

### Example 3: Thank You Message

```bash
curl -X POST "http://your-api.com/api/devices/send-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "message": "ধন্যবাদ! আপনার পেমেন্ট সফল হয়েছে। Thank you! Your payment was successful.",
    "title": "পেমেন্ট সফল / Payment Success"
  }'
```

### Example 4: Promotional Message

```bash
curl -X POST "http://your-api.com/api/devices/send-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "message": "নতুন মোবাইল এসেছে! কম দামে EMI এ কিনুন। New phones available on easy EMI!",
    "title": "বিশেষ অফার / Special Offer"
  }'
```

### Example 5: Overdue Reminder

```bash
curl -X POST "http://your-api.com/api/devices/send-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "message": "আপনার ৳5,833 টাকা বকেয়া। অনুগ্রহ করে আজই পরিশোধ করুন। Your payment of ৳5,833 is overdue.",
    "title": "জরুরি / URGENT"
  }'
```

---

## ✅ Success Response

```json
{
  "success": true,
  "data": {
    "success": true,
    "message": "Message sent successfully",
    "command": "SHOW_MESSAGE",
    "title": "Payment Reminder",
    "sent_message": "Your EMI payment is due tomorrow.",
    "customer_id": 123,
    "sent_at": "2025-10-09T14:30:00Z"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Overall request success |
| `data.success` | boolean | Message delivery success |
| `data.message` | string | Status message |
| `data.command` | string | Command type (always "SHOW_MESSAGE") |
| `data.title` | string | Title shown on device |
| `data.sent_message` | string | Message shown on device |
| `data.customer_id` | integer | Customer who received message |
| `data.sent_at` | string | Timestamp when sent |

---

## ❌ Error Responses

### Customer Not Found
```json
{
  "success": false,
  "message": "Customer not found",
  "errors": null,
  "status": 404
}
```

### Device Not Registered
```json
{
  "success": false,
  "message": "Device not registered or FCM token missing",
  "errors": null,
  "status": 400
}
```

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_id": ["The customer id field is required."],
    "message": ["The message field is required."]
  },
  "status": 422
}
```

---

## 🔄 How It Works

```
1. API receives request
   ↓
2. Validates customer_id and message
   ↓
3. Checks if device is registered (has FCM token)
   ↓
4. Sends FCM notification to device
   ↓
5. Device receives notification
   ↓
6. Android app shows notification/dialog on screen
   ↓
7. Returns success response
```

---

## 📱 Mobile App Integration

### Android FCM Handler

```kotlin
// In your FirebaseMessagingService
override fun onMessageReceived(remoteMessage: RemoteMessage) {
    val command = remoteMessage.data["command"]
    
    if (command == "SHOW_MESSAGE") {
        val title = remoteMessage.data["title"] ?: "Notification"
        val message = remoteMessage.data["message"] ?: ""
        
        // Show as notification
        showNotification(title, message)
        
        // OR show as dialog/alert
        showAlertDialog(title, message)
        
        // OR show as full-screen overlay
        showFullScreenMessage(title, message)
    }
}

private fun showNotification(title: String, message: String) {
    val notification = NotificationCompat.Builder(this, CHANNEL_ID)
        .setSmallIcon(R.drawable.ic_notification)
        .setContentTitle(title)
        .setContentText(message)
        .setStyle(NotificationCompat.BigTextStyle().bigText(message))
        .setPriority(NotificationCompat.PRIORITY_HIGH)
        .setAutoCancel(true)
        .build()
        
    notificationManager.notify(Random.nextInt(), notification)
}
```

---

## 🆚 Comparison: 3 API Endpoints

### 1. Send Message Only (NEW!)
```
POST /api/devices/send-message
{
  "customer_id": 123,
  "message": "Payment due tomorrow",
  "title": "Reminder"
}

Result:
✗ No command executed
✓ Message shown on device
```

### 2. Send Command Only
```
POST /api/devices/command/lock
{
  "customer_id": 123
}

Result:
✓ Device locked
✗ No message shown
```

### 3. Send Command + Message
```
POST /api/devices/command-with-message/lock
{
  "customer_id": 123,
  "display_message": "Device locked",
  "display_title": "Locked"
}

Result:
✓ Device locked
✓ Message shown on device
```

---

## 🎨 Message Best Practices

### ✅ Good Messages

```
✓ Clear and specific:
"আপনার EMI পেমেন্ট ৳5,833 আগামীকাল দিতে হবে।"
"Your EMI payment of ৳5,833 is due tomorrow."

✓ Action-oriented:
"পেমেন্ট করতে কল করুন: 01712-345678"
"Call to pay: 01712-345678"

✓ Bilingual:
"অফিস বন্ধ / Office closed during Eid"

✓ Friendly tone:
"ধন্যবাদ! আপনার পেমেন্ট পাওয়া গেছে। 😊"
"Thank you! Payment received. 😊"
```

### ❌ Bad Messages

```
✗ Too short:
"Pay now"

✗ Too long:
(500+ character messages are hard to read)

✗ Unclear:
"Action required"

✗ English only:
"Your payment is overdue by 5 days"
(Many customers prefer Bangla)
```

---

## 🧪 Testing with Postman

### Step 1: Setup Environment
```
Variable: api_url
Value: http://localhost:8000/api

Variable: token
Value: YOUR_AUTH_TOKEN
```

### Step 2: Create Request
```
Method: POST
URL: {{api_url}}/devices/send-message

Headers:
  Authorization: Bearer {{token}}
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "customer_id": 1,
  "message": "This is a test message from API",
  "title": "Test Notification"
}
```

### Step 3: Send and Verify
- Check response: 200 OK
- Verify `success: true`
- Check device for notification

---

## 📊 Use Cases

### 1. 📅 Payment Reminders (Most Common)

```json
{
  "customer_id": 123,
  "message": "আপনার মাসিক EMI ৳5,833 আগামীকাল দিতে হবে। Your monthly EMI of ৳5,833 is due tomorrow. Call: 01712-345678",
  "title": "পেমেন্ট রিমাইন্ডার / Payment Reminder"
}
```

### 2. 🎉 Thank You Messages

```json
{
  "customer_id": 123,
  "message": "ধন্যবাদ! আপনার পেমেন্ট সফলভাবে পাওয়া গেছে। ৳5,833 রসিদ নম্বর: #12345. Thank you for your payment!",
  "title": "পেমেন্ট নিশ্চিতকরণ / Payment Confirmed"
}
```

### 3. ⚠️ Overdue Alerts

```json
{
  "customer_id": 123,
  "message": "জরুরি: আপনার ২টি EMI বকেয়া (মোট ৳11,666)। আজই পরিশোধ করুন। URGENT: 2 EMI payments overdue (৳11,666 total).",
  "title": "⚠️ জরুরি / URGENT"
}
```

### 4. 📢 General Announcements

```json
{
  "customer_id": 123,
  "message": "আমাদের অফিস ঈদের ছুটিতে ১০-১৫ অক্টোবর বন্ধ থাকবে। Our office will be closed Oct 10-15 for Eid.",
  "title": "ছুটির নোটিশ / Holiday Notice"
}
```

### 5. 🎁 Promotional Offers

```json
{
  "customer_id": 123,
  "message": "বিশেষ অফার! নতুন Samsung Galaxy A54 এখন মাত্র ৳4,500/মাসে EMI তে পাওয়া যাচ্ছে। Special offer on new phones!",
  "title": "নতুন অফার / New Offer"
}
```

### 6. 📞 Contact Request

```json
{
  "customer_id": 123,
  "message": "অনুগ্রহ করে আমাদের সাথে যোগাযোগ করুন। গুরুত্বপূর্ণ বিষয়ে আলোচনা করতে হবে। Please contact us: 01712-345678",
  "title": "যোগাযোগ করুন / Contact Us"
}
```

---

## 🔐 Security & Limits

### Rate Limiting
- Max 100 messages per minute per user
- Max 1000 messages per day per customer

### Authorization
- User must have permission to send messages
- Super Admin: Can send to any customer
- Dealers/Sub-Dealers: Only their customers
- Salesmen: Only their assigned customers

### Message Limits
- Max message length: 500 characters
- Max title length: 100 characters
- HTML tags stripped for security
- Emoji support: ✅ Yes
- Bangla Unicode: ✅ Yes

---

## 🌐 Language Support

### Supported
- ✅ English
- ✅ বাংলা (Bangla/Bengali)
- ✅ Mixed (Bilingual messages)
- ✅ Emoji: 😊 👍 ⚠️ 💰 📱 🎉
- ✅ Bangla Numbers: ০১২৩৪৫৬৭৮৯

### Message Examples

```
Bangla: "আপনার পেমেন্ট বকেয়া।"
English: "Your payment is overdue."
Mixed: "আপনার পেমেন্ট বকেয়া / Payment overdue"
With Emoji: "ধন্যবাদ! পেমেন্ট সফল 😊 Thank you!"
```

---

## 💡 Pro Tips

### 1. Always Include Contact Info
```
✅ "পেমেন্ট করতে কল করুন: 01712-345678"
✅ "Call to pay: 01712-345678 or visit Mirpur-10 office"
```

### 2. Use Bilingual Messages
```
✅ "পেমেন্ট বকেয়া / Payment Overdue"
✅ "আগামীকাল / Due Tomorrow"
```

### 3. Be Specific with Amounts
```
✅ "আপনার ৳5,833 টাকা বকেয়া"
✅ "Your ৳5,833 payment is due"
```

### 4. Add Urgency When Needed
```
Low: "📅 Reminder: Payment due in 3 days"
Medium: "⚠️ Warning: Payment overdue"
High: "🚨 URGENT: Payment required today"
```

### 5. Friendly Tone for Success
```
✅ "ধন্যবাদ! 😊 Thank you!"
✅ "পেমেন্ট সফল! Payment successful! 🎉"
```

---

## 📞 Support

For issues or questions:
- **Email**: support@emimanager.com
- **Phone**: +880 1712-XXXXXX
- **Documentation**: https://docs.emimanager.com

---

## 🎉 Summary

### What This API Does
✅ Sends **message only** to device  
✅ **No command execution** (no lock, unlock, etc.)  
✅ Perfect for **notifications and reminders**  
✅ **Simple** - just 3 parameters  
✅ **Fast** - single FCM push  

### When to Use
- 📅 Payment reminders
- 🎉 Thank you messages
- ⚠️ Overdue alerts
- 📢 Announcements
- 🎁 Promotional offers
- 📞 Contact requests

### Simple Example
```bash
POST /api/devices/send-message
{
  "customer_id": 123,
  "message": "Your payment is due tomorrow",
  "title": "Payment Reminder"
}
```

🚀 **Start sending messages today!**
