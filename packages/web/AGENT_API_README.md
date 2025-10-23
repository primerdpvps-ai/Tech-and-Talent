# TTS PMS Agent API Documentation

This document provides comprehensive documentation for integrating with the TTS PMS Agent API, including authentication, HMAC signature calculation, and cURL examples for Windows development teams.

## Overview

The Agent API uses JWT-based authentication combined with HMAC-SHA256 request signing for secure communication between desktop agents and the server. All POST requests must include a signature header to prevent tampering and replay attacks.

## Authentication Flow

### 1. Device Registration & Login

First, register your device and obtain authentication credentials:

```bash
curl -X POST "https://api.tts-pms.com/api/agent/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee@company.com",
    "password": "user_password",
    "deviceId": "WIN-DESKTOP-001",
    "deviceInfo": {
      "platform": "Windows 11",
      "version": "1.0.0",
      "userAgent": "TTS-Agent/1.0.0 (Windows NT 10.0; Win64; x64)",
      "screenResolution": "1920x1080",
      "timezone": "America/New_York"
    }
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "deviceSecret": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
    "user": {
      "id": "user123",
      "email": "employee@company.com",
      "fullName": "John Doe",
      "role": "EMPLOYEE"
    },
    "deviceId": "WIN-DESKTOP-001",
    "expiresIn": 86400,
    "permissions": {
      "canTrackTime": true,
      "canUploadScreenshots": true,
      "canSubmitActivity": true
    }
  }
}
```

**Important:** Store both the `token` and `deviceSecret` securely. The token is used for authorization, and the deviceSecret is used for HMAC signature generation.

## HMAC Signature Calculation

All POST requests must include an `X-TTS-Signature` header containing an HMAC-SHA256 signature of the request body.

### Signature Formula
```
X-TTS-Signature = HMAC_SHA256(device_secret, raw_request_body)
```

### Implementation Examples

#### PowerShell Example
```powershell
function Get-HMACSignature {
    param(
        [string]$Message,
        [string]$Secret
    )
    
    $hmacsha256 = New-Object System.Security.Cryptography.HMACSHA256
    $hmacsha256.Key = [System.Text.Encoding]::UTF8.GetBytes($Secret)
    $signature = $hmacsha256.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($Message))
    return [System.Convert]::ToHex($signature).ToLower()
}

# Usage
$deviceSecret = "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"
$requestBody = '{"timestamp":"2024-03-15T10:30:00.000Z","timezone":"America/New_York"}'
$signature = Get-HMACSignature -Message $requestBody -Secret $deviceSecret
```

#### C# Example
```csharp
using System;
using System.Security.Cryptography;
using System.Text;

public static string CalculateHMAC(string message, string secret)
{
    var keyBytes = Encoding.UTF8.GetBytes(secret);
    var messageBytes = Encoding.UTF8.GetBytes(message);
    
    using (var hmac = new HMACSHA256(keyBytes))
    {
        var hashBytes = hmac.ComputeHash(messageBytes);
        return BitConverter.ToString(hashBytes).Replace("-", "").ToLower();
    }
}
```

#### Python Example
```python
import hmac
import hashlib
import json

def calculate_hmac(message, secret):
    return hmac.new(
        secret.encode('utf-8'),
        message.encode('utf-8'),
        hashlib.sha256
    ).hexdigest()

# Usage
device_secret = "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"
request_body = json.dumps({"timestamp": "2024-03-15T10:30:00.000Z"})
signature = calculate_hmac(request_body, device_secret)
```

## API Endpoints

### Timer Management

#### Start Timer Session
```bash
curl -X POST "https://api.tts-pms.com/api/agent/timer/start" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-TTS-Signature: CALCULATED_HMAC_SIGNATURE" \
  -d '{
    "timestamp": "2024-03-15T09:00:00.000Z",
    "timezone": "America/New_York",
    "metadata": {
      "startReason": "work_day_begin",
      "computerName": "WIN-DESKTOP-001"
    }
  }'
```

#### Pause Timer Session
```bash
curl -X POST "https://api.tts-pms.com/api/agent/timer/pause" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-TTS-Signature: CALCULATED_HMAC_SIGNATURE" \
  -d '{
    "timestamp": "2024-03-15T12:00:00.000Z",
    "reason": "break",
    "duration": 1800
  }'
```

#### Stop Timer Session
```bash
curl -X POST "https://api.tts-pms.com/api/agent/timer/stop" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-TTS-Signature: CALCULATED_HMAC_SIGNATURE" \
  -d '{
    "timestamp": "2024-03-15T17:00:00.000Z",
    "summary": {
      "totalActiveSeconds": 28800,
      "totalPauseSeconds": 3600,
      "tasksSummary": ["Data entry", "Email processing", "Report generation"],
      "notes": "Completed daily tasks successfully"
    }
  }'
```

### Activity Tracking

#### Submit Activity Batch
```bash
curl -X POST "https://api.tts-pms.com/api/agent/activity" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-TTS-Signature: CALCULATED_HMAC_SIGNATURE" \
  -d '{
    "activities": [
      {
        "timestamp": "2024-03-15T10:30:00.000Z",
        "type": "mouse_click",
        "data": {"x": 150, "y": 200, "button": "left"}
      },
      {
        "timestamp": "2024-03-15T10:30:01.000Z",
        "type": "key_press",
        "data": {"key": "a", "modifiers": []}
      },
      {
        "timestamp": "2024-03-15T10:30:02.000Z",
        "type": "window_focus",
        "data": {"windowTitle": "Microsoft Excel", "processName": "excel.exe"}
      }
    ],
    "batchId": "batch_1710505800000",
    "deviceInfo": {
      "screenResolution": "1920x1080",
      "activeWindow": "Microsoft Excel - Workbook1",
      "cpuUsage": 15.5,
      "memoryUsage": 68.2
    }
  }'
```

### Screenshot Upload

#### Get Presigned URL for Screenshot
```bash
curl -X POST "https://api.tts-pms.com/api/agent/screenshot/presign" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-TTS-Signature: CALCULATED_HMAC_SIGNATURE" \
  -d '{
    "timestamp": "2024-03-15T10:30:00.000Z",
    "screenshotType": "periodic",
    "quality": "medium",
    "metadata": {
      "screenResolution": "1920x1080",
      "activeWindow": "Microsoft Excel - Workbook1",
      "mousePosition": {"x": 960, "y": 540}
    }
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "uploadUrl": "https://tts-pms-screenshots.s3.us-east-1.amazonaws.com",
    "fileUrl": "https://cdn.tts-pms.com/screenshots/user123/2024-03-15/10-30-00_periodic_abc123.jpg",
    "fields": {
      "key": "screenshots/user123/2024-03-15/10-30-00_periodic_abc123.jpg",
      "Content-Type": "image/jpeg",
      "X-Amz-Algorithm": "AWS4-HMAC-SHA256",
      "X-Amz-Credential": "...",
      "X-Amz-Date": "20240315T103000Z",
      "X-Amz-Signature": "...",
      "Policy": "..."
    },
    "qualitySettings": {
      "compression": 0.8,
      "maxWidth": 1920,
      "maxHeight": 1080
    },
    "guidelines": {
      "maxFileSize": 10485760,
      "acceptedFormats": ["image/jpeg"],
      "compressionRequired": true,
      "blurSensitiveInfo": true
    }
  }
}
```

#### Upload Screenshot to S3
```bash
curl -X POST "PRESIGNED_UPLOAD_URL" \
  -F "key=screenshots/user123/2024-03-15/10-30-00_periodic_abc123.jpg" \
  -F "Content-Type=image/jpeg" \
  -F "X-Amz-Algorithm=AWS4-HMAC-SHA256" \
  -F "X-Amz-Credential=..." \
  -F "X-Amz-Date=20240315T103000Z" \
  -F "X-Amz-Signature=..." \
  -F "Policy=..." \
  -F "file=@screenshot.jpg"
```

## Error Handling

### Common Error Responses

#### Authentication Errors
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid or expired token"
  }
}
```

#### Signature Errors
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid request signature"
  }
}
```

#### Operational Window Errors
```json
{
  "success": false,
  "error": {
    "code": "OPERATIONAL_WINDOW",
    "message": "Request outside operational window: Outside working hours (09:00-17:00). Current time: 18:30"
  }
}
```

#### Validation Errors
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid timer start data",
    "details": {
      "timestamp": ["Invalid date format"]
    }
  }
}
```

## Best Practices

### 1. Token Management
- Store JWT tokens securely (encrypted storage recommended)
- Implement automatic token refresh before expiration
- Handle token expiration gracefully with re-authentication

### 2. Signature Security
- Never log or expose device secrets
- Generate signatures for each request (no caching)
- Use secure random generation for device IDs

### 3. Request Timing
- Use accurate timestamps (synchronized with NTP)
- Handle timezone conversions properly
- Implement retry logic with exponential backoff

### 4. Activity Batching
- Batch activities every 5 minutes (recommended)
- Limit batch size to 1000 activities maximum
- Include relevant metadata for better tracking

### 5. Screenshot Guidelines
- Compress images before upload (follow quality settings)
- Blur sensitive information automatically
- Respect privacy settings and user preferences
- Implement proper error handling for upload failures

## Windows-Specific Implementation Notes

### Registry Storage
Store configuration in Windows Registry:
```
HKEY_CURRENT_USER\Software\TTS-PMS\Agent
- DeviceId (REG_SZ)
- DeviceSecret (REG_BINARY, encrypted)
- ServerUrl (REG_SZ)
- LastTokenRefresh (REG_QWORD)
```

### Service Implementation
- Run as Windows Service for reliability
- Implement proper service start/stop handling
- Use Windows Event Log for error reporting
- Handle user session changes (lock/unlock)

### Screenshot Capture
```csharp
// Example screenshot capture
using System.Drawing;
using System.Drawing.Imaging;

public static byte[] CaptureScreen()
{
    var bounds = Screen.PrimaryScreen.Bounds;
    using (var bitmap = new Bitmap(bounds.Width, bounds.Height))
    {
        using (var graphics = Graphics.FromImage(bitmap))
        {
            graphics.CopyFromScreen(bounds.X, bounds.Y, 0, 0, bounds.Size);
        }
        
        using (var stream = new MemoryStream())
        {
            bitmap.Save(stream, ImageFormat.Jpeg);
            return stream.ToArray();
        }
    }
}
```

### Activity Monitoring
```csharp
// Example activity monitoring setup
using System.Windows.Forms;
using System.Runtime.InteropServices;

public class ActivityMonitor
{
    private LowLevelMouseProc _mouseProc = HookCallback;
    private LowLevelKeyboardProc _keyboardProc = HookCallback;
    private IntPtr _mouseHookID = IntPtr.Zero;
    private IntPtr _keyboardHookID = IntPtr.Zero;

    public ActivityMonitor()
    {
        _mouseHookID = SetHook(_mouseProc);
        _keyboardHookID = SetHook(_keyboardProc);
    }
    
    // Implementation details...
}
```

## Testing

### Test Authentication
```bash
# Test login
curl -X POST "http://localhost:3000/api/agent/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "deviceId": "TEST-DEVICE-001"
  }'
```

### Test Signature Calculation
```bash
# Test with known values
SECRET="test_secret_key"
BODY='{"test":"data"}'
EXPECTED_SIGNATURE="a1b2c3d4e5f6..."

# Calculate and compare
CALCULATED=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | cut -d' ' -f2)
echo "Expected: $EXPECTED_SIGNATURE"
echo "Calculated: $CALCULATED"
```

## Support

For technical support and questions:
- Email: dev-support@tts-pms.com
- Documentation: https://docs.tts-pms.com/agent-api
- Status Page: https://status.tts-pms.com

## Changelog

### Version 1.0.0 (2024-03-15)
- Initial API release
- JWT + HMAC authentication
- Timer management endpoints
- Activity tracking
- Screenshot upload with S3 presigned URLs
- Operational window validation
