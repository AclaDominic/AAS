import { NavLink, useNavigate } from 'react-router-dom'
import { useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import './MemberSidebar.css'

function MemberSidebar({ isOpen, onToggle }) {
  const location = useLocation()
  const navigate = useNavigate()
  const { logout, user } = useAuth()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const navItems = [
    {
      path: '/membership',
      label: 'Membership',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" strokeWidth="2"/>
          <path d="M3 10h18M8 14h8" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/courts/booking',
      label: 'Scheduling',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2"/>
          <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
    {
      path: '/billing',
      label: 'Billing',
      icon: (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" strokeWidth="2"/>
          <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      ),
    },
  ]

  const getUsername = () => {
    if (user?.email) {
      const emailParts = user.email.split('@')
      return `@${emailParts[0]}`
    }
    return '@user'
  }

  return (
    <aside className={`member-sidebar ${isOpen ? 'open' : 'closed'}`}>
      <div className="member-sidebar-content">
        {/* User Profile */}
        <div className="member-profile">
          <div className="member-profile-picture">
            {user?.name ? (
              <div className="member-profile-initial">
                {user.name.charAt(0).toUpperCase()}
              </div>
            ) : (
              <div className="member-profile-initial">U</div>
            )}
          </div>
          <div className="member-profile-info">
            <div className="member-profile-name">{user?.name || 'User'}</div>
            <div className="member-profile-username">{getUsername()}</div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="member-nav">
          {navItems.map((item) => {
            const isActive = location.pathname === item.path || location.pathname.startsWith(item.path + '/')
            return (
              <NavLink
                key={item.path}
                to={item.path}
                className={`member-nav-item ${isActive ? 'active' : ''}`}
              >
                <span className="member-nav-icon">{item.icon}</span>
                <span className="member-nav-label">{item.label}</span>
              </NavLink>
            )
          })}
        </nav>

        {/* Log Out */}
        <button className="member-logout" onClick={handleLogout}>
          Log Out
        </button>
      </div>
    </aside>
  )
}

export default MemberSidebar

