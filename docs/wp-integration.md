# WordPress Integration Guide

This document outlines two approaches for integrating the TTS PMS Next.js application with WordPress.

## Option A: Headless Embed with Secure iframe

### Overview
Deploy the Next.js app separately and embed it in WordPress using a secure iframe with SSO authentication.

### Architecture
```
WordPress (Divi) → Secure iframe → Next.js App (External Domain)
                ↓
            SSO Token Exchange
```

### Implementation

#### 1. WordPress SSO Token Generation

```php
// wp-content/themes/your-theme/functions.php or custom plugin
function generate_pms_sso_token($user_id) {
    $secret = get_option('pms_sso_secret');
    $payload = [
        'user_id' => $user_id,
        'email' => get_userdata($user_id)->user_email,
        'role' => get_userdata($user_id)->roles[0],
        'exp' => time() + 300, // 5 minute expiry
        'iat' => time()
    ];
    
    return jwt_encode($payload, $secret, 'HS256');
}

// Shortcode for embedding PMS dashboard
function pms_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access the dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    $token = generate_pms_sso_token($user_id);
    $pms_url = get_option('pms_app_url', 'https://pms.yoursite.com');
    
    return sprintf(
        '<iframe src="%s/sso-login?token=%s" 
                width="100%%" 
                height="800px" 
                frameborder="0"
                sandbox="allow-same-origin allow-scripts allow-forms">
        </iframe>',
        esc_url($pms_url),
        esc_attr($token)
    );
}
add_shortcode('pms_dashboard', 'pms_dashboard_shortcode');
```

#### 2. Next.js SSO Validation

```typescript
// pages/api/auth/sso-login.ts
import { NextApiRequest, NextApiResponse } from 'next';
import jwt from 'jsonwebtoken';
import { getServerSession } from 'next-auth';
import { authOptions } from './[...nextauth]';

interface SSOPayload {
  user_id: string;
  email: string;
  role: string;
  exp: number;
  iat: number;
}

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const { token } = req.body;
    const secret = process.env.SSO_SECRET;
    
    // Verify JWT token
    const payload = jwt.verify(token, secret) as SSOPayload;
    
    // Create or update user in PMS database
    const user = await prisma.user.upsert({
      where: { email: payload.email },
      update: {
        wpUserId: payload.user_id,
        role: mapWPRoleToPMSRole(payload.role),
        lastLogin: new Date()
      },
      create: {
        email: payload.email,
        wpUserId: payload.user_id,
        role: mapWPRoleToPMSRole(payload.role),
        status: 'ACTIVE'
      }
    });

    // Create NextAuth session
    const session = await getServerSession(req, res, authOptions);
    // Set session data...
    
    res.status(200).json({ success: true, redirectTo: '/dashboard' });
  } catch (error) {
    res.status(401).json({ error: 'Invalid token' });
  }
}

function mapWPRoleToPMSRole(wpRole: string): string {
  const roleMap = {
    'administrator': 'ADMIN',
    'editor': 'MANAGER', 
    'subscriber': 'EMPLOYEE'
  };
  return roleMap[wpRole] || 'EMPLOYEE';
}
```

#### 3. SSO Login Page

```typescript
// pages/sso-login.tsx
import { useEffect } from 'react';
import { useRouter } from 'next/router';

export default function SSOLogin() {
  const router = useRouter();
  const { token } = router.query;

  useEffect(() => {
    if (token) {
      fetch('/api/auth/sso-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          router.push(data.redirectTo);
        } else {
          router.push('/auth/error');
        }
      });
    }
  }, [token]);

  return <div>Authenticating...</div>;
}
```

### Pros & Cons

**Pros:**
- ✅ Complete separation of concerns
- ✅ Independent deployments and scaling
- ✅ Secure token-based authentication
- ✅ Easy to maintain and update
- ✅ Can use different hosting providers

**Cons:**
- ❌ iframe limitations (mobile responsiveness)
- ❌ Cross-origin complexity
- ❌ Potential SEO issues
- ❌ Additional infrastructure costs

---

## Option B: WordPress Plugin Bridge

### Overview
Create a WordPress plugin that proxies Next.js pages and syncs users via REST API.

### Architecture
```
WordPress → Plugin Bridge → Next.js API Routes
    ↓           ↓              ↓
WP Users → User Sync → PMS Database
```

### Implementation

#### 1. WordPress Plugin Structure

```php
<?php
/**
 * Plugin Name: TTS PMS Bridge
 * Description: Integrates TTS PMS with WordPress
 * Version: 1.0.0
 */

class TTS_PMS_Bridge {
    private $api_base;
    private $api_key;

    public function __construct() {
        $this->api_base = get_option('pms_api_base', 'https://pms-api.yoursite.com');
        $this->api_key = get_option('pms_api_key');
        
        add_action('init', [$this, 'init_routes']);
        add_action('user_register', [$this, 'sync_user_create']);
        add_action('profile_update', [$this, 'sync_user_update']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function init_routes() {
        add_rewrite_rule(
            '^pms/(.*)/?',
            'index.php?pms_route=$matches[1]',
            'top'
        );
        add_query_var('pms_route');
        add_action('template_redirect', [$this, 'handle_pms_routes']);
    }

    public function handle_pms_routes() {
        $route = get_query_var('pms_route');
        if (!$route) return;

        // Authenticate user
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url("/pms/$route")));
            exit;
        }

        // Proxy to Next.js
        $this->proxy_to_nextjs($route);
    }

    private function proxy_to_nextjs($route) {
        $user = wp_get_current_user();
        $pms_token = $this->generate_pms_token($user);
        
        // Make request to Next.js API
        $response = wp_remote_get($this->api_base . "/api/proxy/$route", [
            'headers' => [
                'Authorization' => "Bearer $pms_token",
                'X-WP-User-ID' => $user->ID
            ]
        ]);

        if (is_wp_error($response)) {
            wp_die('PMS service unavailable');
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        header("Content-Type: $content_type");
        echo $body;
        exit;
    }

    public function sync_user_create($user_id) {
        $user = get_userdata($user_id);
        $this->sync_user_to_pms($user, 'create');
    }

    public function sync_user_update($user_id) {
        $user = get_userdata($user_id);
        $this->sync_user_to_pms($user, 'update');
    }

    private function sync_user_to_pms($user, $action) {
        $data = [
            'wp_user_id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->roles[0] ?? 'subscriber',
            'action' => $action
        ];

        wp_remote_post($this->api_base . '/api/sync/users', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ],
            'body' => json_encode($data)
        ]);
    }

    private function generate_pms_token($user) {
        // Generate JWT token for PMS authentication
        $payload = [
            'wp_user_id' => $user->ID,
            'email' => $user->user_email,
            'role' => $user->roles[0] ?? 'subscriber',
            'exp' => time() + 3600
        ];
        
        return jwt_encode($payload, get_option('pms_jwt_secret'), 'HS256');
    }
}

new TTS_PMS_Bridge();
```

#### 2. Next.js API Proxy Handler

```typescript
// pages/api/proxy/[...route].ts
import { NextApiRequest, NextApiResponse } from 'next';
import { renderToString } from 'react-dom/server';
import jwt from 'jsonwebtoken';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  try {
    // Verify WordPress token
    const token = req.headers.authorization?.replace('Bearer ', '');
    const payload = jwt.verify(token, process.env.JWT_SECRET);
    
    // Get route
    const route = Array.isArray(req.query.route) 
      ? req.query.route.join('/') 
      : req.query.route;

    // Route to appropriate component
    let Component;
    switch (route) {
      case 'dashboard':
        Component = (await import('../../../src/app/dashboard/page')).default;
        break;
      case 'employees':
        Component = (await import('../../../src/app/employees/page')).default;
        break;
      default:
        return res.status(404).json({ error: 'Route not found' });
    }

    // Render component with WordPress styling
    const html = renderToString(<Component />);
    const wrappedHtml = `
      <div id="pms-app" style="margin: 20px;">
        ${html}
      </div>
      <script>
        // Initialize React hydration
        ReactDOM.hydrate(React.createElement(${Component.name}), document.getElementById('pms-app'));
      </script>
    `;

    res.setHeader('Content-Type', 'text/html');
    res.status(200).send(wrappedHtml);
  } catch (error) {
    res.status(401).json({ error: 'Unauthorized' });
  }
}
```

#### 3. User Sync API

```typescript
// pages/api/sync/users.ts
import { NextApiRequest, NextApiResponse } from 'next';
import { prisma } from '../../../lib/prisma';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  // Verify API key
  const apiKey = req.headers['x-api-key'];
  if (apiKey !== process.env.WP_API_KEY) {
    return res.status(401).json({ error: 'Invalid API key' });
  }

  try {
    const { wp_user_id, email, first_name, last_name, role, action } = req.body;

    if (action === 'create') {
      await prisma.user.create({
        data: {
          email,
          firstName: first_name,
          lastName: last_name,
          wpUserId: wp_user_id.toString(),
          role: mapWPRoleToPMSRole(role),
          status: 'ACTIVE'
        }
      });
    } else if (action === 'update') {
      await prisma.user.update({
        where: { wpUserId: wp_user_id.toString() },
        data: {
          email,
          firstName: first_name,
          lastName: last_name,
          role: mapWPRoleToPMSRole(role)
        }
      });
    }

    res.status(200).json({ success: true });
  } catch (error) {
    res.status(500).json({ error: 'Sync failed' });
  }
}
```

### Pros & Cons

**Pros:**
- ✅ Seamless WordPress integration
- ✅ Unified user management
- ✅ Native WordPress styling
- ✅ Single domain/hosting
- ✅ Better SEO integration

**Cons:**
- ❌ Complex plugin maintenance
- ❌ WordPress performance impact
- ❌ Tight coupling between systems
- ❌ Limited Next.js features (SSR/SSG)
- ❌ Plugin compatibility issues

---

## Recommendation

**For most use cases, Option A (Headless Embed)** is recommended because:

1. **Maintainability**: Easier to update and maintain separately
2. **Performance**: Each system can be optimized independently  
3. **Scalability**: Can scale PMS without affecting WordPress
4. **Security**: Better isolation between systems
5. **Development**: Teams can work independently

**Choose Option B** only if you need tight WordPress integration and are willing to accept the complexity trade-offs.

## Security Considerations

### Both Options:
- Use HTTPS for all communications
- Implement proper CORS policies
- Validate all JWT tokens with short expiry times
- Use environment variables for secrets
- Implement rate limiting on API endpoints
- Log all authentication attempts

### Additional for Option A:
- Implement CSP headers for iframe security
- Use SameSite cookies where possible
- Validate iframe origins

### Additional for Option B:
- Sanitize all proxy responses
- Implement WordPress nonce verification
- Use WordPress security best practices
