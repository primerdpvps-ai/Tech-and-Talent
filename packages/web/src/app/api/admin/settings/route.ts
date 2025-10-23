import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiError, createApiResponse, UserRole } from '@tts-pms/infra';
import { requireRole } from '@/lib/rbac';

const SETTINGS_KEYS = {
  company: 'company_info',
  policy: 'policy_metadata',
  payroll: 'payroll_settings',
} as const;

type SettingKey = typeof SETTINGS_KEYS[keyof typeof SETTINGS_KEYS];

type SettingMap = Record<SettingKey, unknown>;

const DEFAULT_SETTINGS = {
  [SETTINGS_KEYS.company]: {
    name: 'Tech & Talent Solutions Ltd.',
    website: '',
    contactEmail: 'info@tts.com.pk',
    address: '',
  },
  [SETTINGS_KEYS.policy]: {
    effectiveDate: new Date().toISOString().slice(0, 10),
    updatedDate: new Date().toISOString().slice(0, 10),
  },
  [SETTINGS_KEYS.payroll]: {
    payslipNote: 'This payslip is auto-generated. For questions, contact HR.',
  },
} satisfies SettingMap;

const SystemSettingsSchema = z.object({
  company: z.object({
    name: z.string().min(1),
    website: z.string().url().optional().or(z.literal('')),
    contactEmail: z.string().email(),
    address: z.string().optional().or(z.literal('')),
  }),
  policy: z.object({
    effectiveDate: z.string().min(1),
    updatedDate: z.string().min(1),
  }),
  payroll: z.object({
    payslipNote: z.string().min(1),
  }),
});

async function loadSettings(): Promise<typeof DEFAULT_SETTINGS> {
  const entries = await prisma.systemSetting.findMany();
  const map = entries.reduce<SettingMap>((acc, entry) => {
    acc[entry.key as SettingKey] = entry.value;
    return acc;
  }, {} as SettingMap);

  return {
    [SETTINGS_KEYS.company]: {
      ...DEFAULT_SETTINGS[SETTINGS_KEYS.company],
      ...(map[SETTINGS_KEYS.company] as Record<string, unknown> | undefined),
    },
    [SETTINGS_KEYS.policy]: {
      ...DEFAULT_SETTINGS[SETTINGS_KEYS.policy],
      ...(map[SETTINGS_KEYS.policy] as Record<string, unknown> | undefined),
    },
    [SETTINGS_KEYS.payroll]: {
      ...DEFAULT_SETTINGS[SETTINGS_KEYS.payroll],
      ...(map[SETTINGS_KEYS.payroll] as Record<string, unknown> | undefined),
    },
  };
}

async function persistSettings(settings: typeof DEFAULT_SETTINGS) {
  await Promise.all(
    Object.entries(settings).map(([key, value]) =>
      prisma.systemSetting.upsert({
        where: { key },
        create: { key, value },
        update: { value },
      })
    )
  );
}

export async function GET(request: NextRequest) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const settings = await loadSettings();
    return NextResponse.json(
      createApiResponse({
        company: settings[SETTINGS_KEYS.company],
        policy: settings[SETTINGS_KEYS.policy],
        payroll: settings[SETTINGS_KEYS.payroll],
      })
    );
  } catch (error) {
    console.error('Failed to load system settings:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

export async function PUT(request: NextRequest) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const body = await request.json();
    const parseResult = SystemSettingsSchema.safeParse(body);

    if (!parseResult.success) {
      return NextResponse.json(
        createApiError('Invalid settings payload', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const payload = parseResult.data;
    const merged = {
      [SETTINGS_KEYS.company]: payload.company,
      [SETTINGS_KEYS.policy]: payload.policy,
      [SETTINGS_KEYS.payroll]: payload.payroll,
    } as typeof DEFAULT_SETTINGS;

    await persistSettings(merged);
    const settings = await loadSettings();

    return NextResponse.json(
      createApiResponse({
        company: settings[SETTINGS_KEYS.company],
        policy: settings[SETTINGS_KEYS.policy],
        payroll: settings[SETTINGS_KEYS.payroll],
      }, 'Settings updated successfully')
    );
  } catch (error) {
    console.error('Failed to update system settings:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
