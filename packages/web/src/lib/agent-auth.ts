import jwt from 'jsonwebtoken';
import crypto from 'crypto';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

const JWT_SECRET = process.env.JWT_SECRET || 'fallback-jwt-secret';
const AGENT_DEVICE_SECRET = process.env.AGENT_DEVICE_SECRET || 'fallback-device-secret';

// JWT utility functions
function generateJWT(payload: any, secret: string, expiresIn: string = '24h'): string {
  return jwt.sign(payload, secret, { expiresIn });
}

function verifyJWT(token: string, secret: string): { valid: boolean; payload?: any; error?: string } {
  try {
    const payload = jwt.verify(token, secret);
    return { valid: true, payload };
  } catch (error) {
    return { valid: false, error: error instanceof Error ? error.message : 'Invalid token' };
  }
}

// HMAC utility functions
function createHMAC(data: string, secret: string): string {
  return crypto.createHmac('sha256', secret).update(data).digest('hex');
}

function verifyHMAC(data: string, signature: string, secret: string): boolean {
  const expectedSignature = createHMAC(data, secret);
  return crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature));
}

export interface AgentTokenPayload {
  userId: string;
  deviceId: string;
  role: string;
  iat: number;
  exp: number;
}

export function generateAgentToken(userId: string, deviceId: string, role: string): string {
  return generateJWT(
    {
      userId,
      deviceId,
      role,
      type: 'agent_device',
    },
    JWT_SECRET,
    '24h'
  );
}

export function verifyAgentToken(token: string): { valid: boolean; payload?: AgentTokenPayload; error?: string } {
  const result = verifyJWT(token, JWT_SECRET);
  
  if (!result.valid) {
    return result;
  }

  // Verify it's an agent token
  if (result.payload?.type !== 'agent_device') {
    return { valid: false, error: 'Invalid token type' };
  }

  return {
    valid: true,
    payload: result.payload as AgentTokenPayload,
  };
}

export function createRequestSignature(body: string, deviceSecret: string): string {
  return createHMAC(body, deviceSecret);
}

export function verifyRequestSignature(
  body: string,
  signature: string,
  deviceSecret: string
): boolean {
  const expectedSignature = createRequestSignature(body, deviceSecret);
  return verifyHMAC(body, signature, deviceSecret);
}

export async function checkOperationalWindow(): Promise<{
  allowed: boolean;
  reason?: string;
}> {
  try {
    // Get operational settings from database
    const settings = await prisma.systemSettings.findFirst({
      select: {
        workingHoursStart: true,
        workingHoursEnd: true,
        workingDays: true,
        timezone: true
      }
    });

    if (!settings) {
      // Default to always allow if no settings found
      return { allowed: true };
    }

    const now = new Date();
    const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
    const currentTime = now.toTimeString().slice(0, 5); // HH:MM format

    // Check if current day is a working day
    if (!settings.workingDays.includes(currentDay)) {
      return { 
        allowed: false, 
        reason: `Outside working days. Current day: ${currentDay}` 
      };
    }

    // Check if current time is within working hours
    if (currentTime < settings.workingHoursStart || currentTime > settings.workingHoursEnd) {
      return { 
        allowed: false, 
        reason: `Outside working hours (${settings.workingHoursStart}-${settings.workingHoursEnd}). Current time: ${currentTime}` 
      };
    }

    return { allowed: true };
  } catch (error) {
    console.error('Error checking operational window:', error);
    // Default to allow on error
    return { allowed: true };
  }
}

export async function validateAgentRequest(
  request: Request,
  requireSignature: boolean = true
): Promise<{
  valid: boolean;
  userId?: string;
  deviceId?: string;
  error?: string;
  operationalWindow?: { allowed: boolean; reason?: string };
}> {
  try {
    // Get Authorization header
    const authHeader = request.headers.get('Authorization');
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return { valid: false, error: 'Missing or invalid Authorization header' };
    }

    const token = authHeader.substring(7);
    const tokenResult = verifyAgentToken(token);
    
    if (!tokenResult.valid) {
      return { valid: false, error: tokenResult.error || 'Invalid token' };
    }

    const { userId, deviceId } = tokenResult.payload!;

    // Verify HMAC signature if required
    if (requireSignature) {
      const signature = request.headers.get('X-TTS-Signature');
      
      if (!signature) {
        return { valid: false, error: 'Missing X-TTS-Signature header' };
      }

      const body = request.method !== 'GET' ? await request.text() : '';

      // Get device secret from database
      const device = await prisma.agentDevice.findUnique({
        where: { deviceId },
        select: { deviceSecret: true }
      });

      if (!device) {
        return { valid: false, error: 'Device not found' };
      }

      const signatureValid = verifyRequestSignature(
        body,
        signature,
        device.deviceSecret
      );

      if (!signatureValid) {
        return { valid: false, error: 'Invalid request signature' };
      }
    }

    // Verify user exists and is an employee
    const user = await prisma.user.findUnique({
      where: { id: userId },
      select: { id: true, role: true }
    });

    if (!user) {
      return { valid: false, error: 'User not found' };
    }

    if (!['EMPLOYEE', 'NEW_EMPLOYEE', 'MANAGER'].includes(user.role)) {
      return { valid: false, error: 'Invalid user role for agent access' };
    }

    // Check operational window
    const operationalWindow = await checkOperationalWindow();

    return { 
      valid: true, 
      userId, 
      deviceId, 
      operationalWindow 
    };

  } catch (error) {
    console.error('Agent request validation error:', error);
    return { valid: false, error: 'Validation failed' };
  }
}
