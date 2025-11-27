import { NavLink, useNavigate } from 'react-router-dom'
import { useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import './AdminSidebar.css'

function AdminSidebar({ isOpen, onToggle }) {
  const location = useLocation()
  const navigate = useNavigate()
  const { logout, user } = useAuth()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  // Navigation items configuration - easy to extend
  const navItems = [
    {
      path: '/dashboard',
      label: 'Dashboard',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="3" width="7" height="7" stroke="currentColor" strokeWidth="2" rx="1"/>
          <rect x="14" y="3" width="7" height="7" stroke="currentColor" strokeWidth="2" rx="1"/>
          <rect x="3" y="14" width="7" height="7" stroke="currentColor" strokeWidth="2" rx="1"/>
          <rect x="14" y="14" width="7" height="7" stroke="currentColor" strokeWidth="2" rx="1"/>
        </svg>
      ),
    },
    {
      path: '/admin/members',
      label: 'Members Management',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" strokeWidth="2"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/admin/offers',
      label: 'Offers Management',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      ),
    },
    {
      path: '/admin/payments',
      label: 'Payments',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" strokeWidth="2"/>
          <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" strokeWidth="2"/>
        </svg>
      ),
    },
    {
      path: '/admin/reports',
      label: 'Reports',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" strokeWidth="2"/>
          <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/admin/facility-schedule',
      label: 'Facility Schedule',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2"/>
          <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/admin/facility-settings',
      label: 'Facility Settings',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="2"/>
          <path d="M12 1v6m0 6v6M23 12h-6M7 12H1m19.07-7.07l-4.24 4.24M8.17 15.83l-4.24 4.24m15.9-4.24l-4.24-4.24M8.17 8.17L3.93 3.93" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/admin/reservations',
      label: 'Court Reservations',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" strokeWidth="2"/>
          <path d="M9 22V12h6v10" stroke="currentColor" strokeWidth="2"/>
        </svg>
      ),
    },
  ]

  const getUsername = () => {
    if (user?.email) {
      const emailParts = user.email.split('@')
      return `@${emailParts[0]}`
    }
    return '@admin'
  }

  return (
    <aside className={`admin-sidebar ${isOpen ? 'open' : 'closed'}`}>
      <div className="admin-sidebar-content">
        {/* User Profile */}
        <div className="admin-profile">
          <div className="admin-profile-picture">
            {user?.name ? (
              <div className="admin-profile-initial">
                {user.name.charAt(0).toUpperCase()}
              </div>
            ) : (
              <div className="admin-profile-initial">A</div>
            )}
          </div>
          <div className="admin-profile-info">
            <div className="admin-profile-name">{user?.name || 'Admin'}</div>
            <div className="admin-profile-username">{getUsername()}</div>
            <div className="admin-profile-badge">Admin</div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="admin-nav">
          {navItems.map((item) => {
            const isActive = location.pathname === item.path || location.pathname.startsWith(item.path + '/')
            return (
              <NavLink
                key={item.path}
                to={item.path}
                className={`admin-nav-item ${isActive ? 'active' : ''}`}
              >
                <span className="admin-nav-icon">{item.icon}</span>
                <span className="admin-nav-label">{item.label}</span>
              </NavLink>
            )
          })}
        </nav>

        {/* Log Out */}
        <button className="admin-logout" onClick={handleLogout}>
          Log Out
        </button>
      </div>
    </aside>
  )
}

export default AdminSidebar

