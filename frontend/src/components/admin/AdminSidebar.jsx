import { NavLink, useNavigate } from 'react-router-dom'
import { useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'

function AdminSidebar() {
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
      icon: '📊',
    },
    {
      path: '/admin/offers',
      label: 'Offers Management',
      icon: '🎁',
    },
    {
      path: '/admin/payments',
      label: 'Payments',
      icon: '💳',
    },
    // Add more navigation items here as needed
    // {
    //   path: '/admin/users',
    //   label: 'Users',
    //   icon: '👥',
    // },
    // {
    //   path: '/admin/settings',
    //   label: 'Settings',
    //   icon: '⚙️',
    // },
  ]

  return (
    <aside
      style={{
        width: '250px',
        minHeight: '100vh',
        backgroundColor: '#1a1a1a',
        color: '#ffffff',
        padding: '20px 0',
        position: 'fixed',
        left: 0,
        top: 0,
        display: 'flex',
        flexDirection: 'column',
        borderRight: '1px solid #333',
      }}
    >
      {/* Header */}
      <div style={{ padding: '0 20px 30px 20px', borderBottom: '1px solid #333' }}>
        <h2 style={{ margin: 0, fontSize: '1.5rem', color: '#646cff' }}>Admin Panel</h2>
        {user && (
          <p style={{ margin: '5px 0 0 0', fontSize: '0.9rem', color: '#888' }}>
            {user.name}
          </p>
        )}
      </div>

      {/* Navigation */}
      <nav style={{ flex: 1, padding: '20px 0' }}>
        {navItems.map((item) => {
          const isActive = location.pathname === item.path || location.pathname.startsWith(item.path + '/')
          return (
            <NavLink
              key={item.path}
              to={item.path}
              style={{
                display: 'block',
                padding: '12px 20px',
                color: isActive ? '#646cff' : '#ffffff',
                textDecoration: 'none',
                backgroundColor: isActive ? 'rgba(100, 108, 255, 0.1)' : 'transparent',
                borderLeft: isActive ? '3px solid #646cff' : '3px solid transparent',
                transition: 'all 0.2s',
              }}
              onMouseEnter={(e) => {
                if (!isActive) {
                  e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.05)'
                }
              }}
              onMouseLeave={(e) => {
                if (!isActive) {
                  e.currentTarget.style.backgroundColor = 'transparent'
                }
              }}
            >
              <span style={{ marginRight: '10px' }}>{item.icon}</span>
              {item.label}
            </NavLink>
          )
        })}
      </nav>

      {/* Logout Button */}
      <div style={{ padding: '20px', borderTop: '1px solid #333' }}>
        <button
          onClick={handleLogout}
          style={{
            width: '100%',
            padding: '12px',
            backgroundColor: '#dc3545',
            color: '#ffffff',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer',
            fontSize: '1rem',
            fontWeight: 500,
            transition: 'background-color 0.2s',
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.backgroundColor = '#c82333'
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.backgroundColor = '#dc3545'
          }}
        >
          Logout
        </button>
      </div>
    </aside>
  )
}

export default AdminSidebar

