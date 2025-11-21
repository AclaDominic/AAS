import { NavLink, useNavigate } from 'react-router-dom'
import { useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'

function MemberNavbar() {
  const location = useLocation()
  const navigate = useNavigate()
  const { logout, user } = useAuth()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const navItems = [
    {
      path: '/dashboard',
      label: 'Home',
      icon: '🏠',
    },
    {
      path: '/membership',
      label: 'Membership',
      icon: '💳',
    },
    {
      path: '/billing',
      label: 'Billing',
      icon: '💰',
    },
  ]

  return (
    <nav style={{
      backgroundColor: 'rgba(26, 26, 26, 0.95)',
      backdropFilter: 'blur(10px)',
      padding: '20px 40px',
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
      position: 'sticky',
      top: 0,
      zIndex: 100,
    }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: '40px' }}>
        <h1 style={{ margin: 0, fontSize: '1.5rem', color: '#646cff', fontWeight: 'bold' }}>
          AAS
        </h1>
        <div style={{ display: 'flex', gap: '20px' }}>
          {navItems.map((item) => {
            const isActive = location.pathname === item.path || location.pathname.startsWith(item.path + '/')
            return (
              <NavLink
                key={item.path}
                to={item.path}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  padding: '10px 20px',
                  color: isActive ? '#ff6b35' : '#ffffff',
                  textDecoration: 'none',
                  borderRadius: '8px',
                  backgroundColor: isActive ? 'rgba(255, 107, 53, 0.1)' : 'transparent',
                  transition: 'all 0.2s',
                  fontWeight: isActive ? 'bold' : 'normal',
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
                <span>{item.icon}</span>
                {item.label}
              </NavLink>
            )
          })}
        </div>
      </div>

      <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
        {user && (
          <span style={{ color: '#ffffff', fontSize: '0.9rem' }}>
            {user.name}
          </span>
        )}
        <button
          onClick={handleLogout}
          style={{
            padding: '10px 20px',
            backgroundColor: '#dc3545',
            color: '#ffffff',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer',
            fontSize: '0.9rem',
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
    </nav>
  )
}

export default MemberNavbar

