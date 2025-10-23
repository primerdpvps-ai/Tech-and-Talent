/** @type {import('next').NextConfig} */
const nextConfig = {
  experimental: {
    serverComponentsExternalPackages: ['@prisma/client'],
  },
  transpilePackages: ['@tts-pms/db', '@tts-pms/infra'],
  images: {
    domains: ['localhost'],
  },
  env: {
    APP_PUBLIC_OPERATIONAL_WINDOW: process.env.APP_PUBLIC_OPERATIONAL_WINDOW,
    APP_PUBLIC_SPECIAL_WINDOW: process.env.APP_PUBLIC_SPECIAL_WINDOW,
  },
}

module.exports = nextConfig
