import { useState, useEffect } from 'react'
import AdminSidebar from '../admin/AdminSidebar'
import NotificationBell from '../admin/NotificationBell'
import './AdminLayout.css'

function AdminLayout({ children }) {
  const [sidebarOpen, setSidebarOpen] = useState(true)

  useEffect(() => {
    document.body.classList.add('admin-page-active')
    document.getElementById('root')?.classList.add('admin-root')
    
    return () => {
      document.body.classList.remove('admin-page-active')
      document.getElementById('root')?.classList.remove('admin-root')
    }
  }, [])

  return (
    <div className="admin-layout">
      <AdminSidebar isOpen={sidebarOpen} onToggle={() => setSidebarOpen(!sidebarOpen)} />
      <div className="admin-main-content">
        <div className="admin-header">
          <button 
            className="admin-hamburger"
            onClick={() => setSidebarOpen(!sidebarOpen)}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </button>
          <div className="admin-header-brand">
            <span className="admin-brand-text">ADMIN PANEL</span>
            <span className="admin-brand-icon">⚙️</span>
          </div>
          <div className="admin-search-bar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" strokeWidth="2"/>
              <path d="m21 21-4.35-4.35" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
            <input type="text" placeholder="Search" />
          </div>
          <div className="admin-header-actions">
            <NotificationBell />
          </div>
        </div>
        <div className="admin-content-area">
          {children}
        </div>
      </div>
    </div>
  )
}

export default AdminLayout


