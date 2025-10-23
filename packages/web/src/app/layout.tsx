import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import { ThemeProvider } from '../providers/theme-provider';
import { ResponsiveLayout } from '../components/layout/responsive-layout';
import 'mdb-react-ui-kit/dist/css/mdb.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import './globals.css';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'TTS PMS - Professional Management System',
  description: 'Comprehensive employee management and payroll system',
  viewport: 'width=device-width, initial-scale=1',
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#ffffff' },
    { media: '(prefers-color-scheme: dark)', color: '#000000' }
  ]
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
      </head>
      <body className={inter.className}>
        <ThemeProvider>
          <ResponsiveLayout>
            {children}
          </ResponsiveLayout>
        </ThemeProvider>
      </body>
    </html>
  );
}
