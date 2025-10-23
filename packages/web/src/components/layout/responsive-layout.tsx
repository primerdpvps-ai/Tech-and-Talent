'use client';

import Link from 'next/link';
import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTheme } from 'next-themes';
import {
  MDBContainer,
  MDBNavbar,
  MDBNavbarBrand,
  MDBNavbarNav,
  MDBNavbarItem,
  MDBNavbarLink,
  MDBNavbarToggler,
  MDBCollapse,
  MDBBtn,
  MDBIcon,
  MDBDropdown,
  MDBDropdownToggle,
  MDBDropdownMenu,
  MDBDropdownItem,
  MDBSidebar,
  MDBSidebarItem,
  MDBSidebarLink
} from 'mdb-react-ui-kit';

interface ResponsiveLayoutProps {
  children: React.ReactNode;
  showSidebar?: boolean;
}

export function ResponsiveLayout({ children, showSidebar = true }: ResponsiveLayoutProps) {
  const [showNavbar, setShowNavbar] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { theme, setTheme } = useTheme();

  const toggleTheme = () => {
    setTheme(theme === 'dark' ? 'light' : 'dark');
  };

  const sidebarVariants = {
    open: { x: 0, transition: { type: 'spring', stiffness: 300, damping: 30 } },
    closed: { x: '-100%', transition: { type: 'spring', stiffness: 300, damping: 30 } }
  };

  const contentVariants = {
    expanded: { marginLeft: 0, transition: { type: 'spring', stiffness: 300, damping: 30 } },
    compressed: { marginLeft: '240px', transition: { type: 'spring', stiffness: 300, damping: 30 } }
  };

  return (
    <div className="min-vh-100 bg-light" data-mdb-theme={theme}>
      {/* Top Navigation */}
      <MDBNavbar expand="lg" className="navbar-dark bg-primary shadow-sm">
        <MDBContainer fluid>
          {showSidebar && (
            <MDBBtn
              color="link"
              className="text-white me-3 d-lg-none"
              onClick={() => setSidebarOpen(!sidebarOpen)}
            >
              <MDBIcon icon="bars" />
            </MDBBtn>
          )}
          
          <MDBNavbarBrand href="/" className="text-white fw-bold">
            <MDBIcon icon="building" className="me-2" />
            TTS PMS
          </MDBNavbarBrand>

          <MDBNavbarToggler
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation"
            onClick={() => setShowNavbar(!showNavbar)}
          >
            <MDBIcon icon="bars" fas />
          </MDBNavbarToggler>

          <MDBCollapse navbar show={showNavbar}>
            <MDBNavbarNav className="ms-auto align-items-center">
              {/* Theme Toggle */}
              <MDBNavbarItem>
                <motion.div
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.9 }}
                >
                  <MDBBtn
                    color="link"
                    className="text-white"
                    onClick={toggleTheme}
                  >
                    <MDBIcon icon={theme === 'dark' ? 'sun' : 'moon'} />
                  </MDBBtn>
                </motion.div>
              </MDBNavbarItem>

              {/* Notifications */}
              <MDBNavbarItem>
                <MDBDropdown>
                  <MDBDropdownToggle color="link" className="text-white">
                    <MDBIcon icon="bell" />
                    <span className="badge rounded-pill badge-notification bg-danger">3</span>
                  </MDBDropdownToggle>
                  <MDBDropdownMenu>
                    <MDBDropdownItem link>New payroll ready</MDBDropdownItem>
                    <MDBDropdownItem link>Leave request pending</MDBDropdownItem>
                    <MDBDropdownItem link>System maintenance</MDBDropdownItem>
                  </MDBDropdownMenu>
                </MDBDropdown>
              </MDBNavbarItem>

              {/* User Menu */}
              <MDBNavbarItem>
                <MDBDropdown>
                  <MDBDropdownToggle color="link" className="text-white">
                    <MDBIcon icon="user-circle" className="me-1" />
                    John Doe
                  </MDBDropdownToggle>
                  <MDBDropdownMenu>
                    <MDBDropdownItem link>Profile</MDBDropdownItem>
                    <MDBDropdownItem link>Settings</MDBDropdownItem>
                    <MDBDropdownItem divider />
                    <MDBDropdownItem link>Logout</MDBDropdownItem>
                  </MDBDropdownMenu>
                </MDBDropdown>
              </MDBNavbarItem>
            </MDBNavbarNav>
          </MDBCollapse>
        </MDBContainer>
      </MDBNavbar>

      <div className="d-flex">
        {/* Sidebar */}
        {showSidebar && (
          <>
            {/* Mobile Overlay */}
            <AnimatePresence>
              {sidebarOpen && (
                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  className="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-lg-none"
                  style={{ zIndex: 1040 }}
                  onClick={() => setSidebarOpen(false)}
                />
              )}
            </AnimatePresence>

            {/* Sidebar */}
            <motion.div
              variants={sidebarVariants}
              animate={sidebarOpen ? 'open' : 'closed'}
              className="position-fixed position-lg-sticky top-0 start-0 h-100 d-lg-block"
              style={{ 
                width: '240px', 
                zIndex: 1050,
                marginTop: '56px' // Height of navbar
              }}
            >
              <MDBSidebar className="h-100 bg-white shadow-sm">
                <div className="p-3">
                  <h6 className="text-muted text-uppercase fw-bold mb-3">Navigation</h6>
                  
                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="tachometer-alt" className="me-3" />
                      Dashboard
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="users" className="me-3" />
                      Employees
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="calendar-alt" className="me-3" />
                      Attendance
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="money-bill-wave" className="me-3" />
                      Payroll
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="credit-card" className="me-3" />
                      Payments
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="file-invoice" className="me-3" />
                      Invoices
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="chart-bar" className="me-3" />
                      Reports
                    </MDBSidebarLink>
                  </MDBSidebarItem>

                  <MDBSidebarItem>
                    <MDBSidebarLink>
                      <MDBIcon icon="cog" className="me-3" />
                      Settings
                    </MDBSidebarLink>
                  </MDBSidebarItem>
                </div>
              </MDBSidebar>
            </motion.div>
          </>
        )}

        {/* Main Content */}
        <motion.main
          variants={contentVariants}
          animate={showSidebar && window.innerWidth >= 992 ? 'compressed' : 'expanded'}
          className="flex-grow-1 p-3 p-md-4"
          style={{ minHeight: 'calc(100vh - 56px)' }}
        >
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
          >
            {children}
          </motion.div>

          <footer className="mt-5 pt-4 border-top">
            <div className="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 text-muted small">
              <span>
                © {new Date().getFullYear()} Tech & Talent Solutions Ltd.
              </span>
              <div className="d-flex flex-wrap gap-3">
                <Link href="/legal/privacy" className="text-reset text-decoration-none">
                  Privacy Policy
                </Link>
                <Link href="/legal/terms" className="text-reset text-decoration-none">
                  Terms & Conditions
                </Link>
                <Link href="/legal/handbook" className="text-reset text-decoration-none">
                  Employee Handbook
                </Link>
                <Link href="/admin/settings" className="text-reset text-decoration-none">
                  Admin Settings
                </Link>
              </div>
            </div>
          </footer>
        </motion.main>
      </div>
    </div>
  );
}
