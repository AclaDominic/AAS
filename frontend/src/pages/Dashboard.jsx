import { useAuth } from '../contexts/AuthContext'
import { useNavigate } from 'react-router-dom'
import AdminLayout from '../components/layout/AdminLayout'
import AdminDashboard from '../components/admin/AdminDashboard'
import MemberLayout from '../components/layout/MemberLayout'

function Dashboard() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  if (!user) {
    return <div>Loading...</div>
  }

  // If user is admin, render admin layout with admin dashboard
  if (user.is_admin) {
    return (
      <AdminLayout>
        <AdminDashboard />
      </AdminLayout>
    )
  }

  // Regular dashboard for members
  return (
    <MemberLayout>
      <div style={{ 
        width: '100%', 
        maxWidth: '1200px', 
        margin: '0 auto', 
        color: '#ffffff',
        padding: '20px 0',
      }}>
        <h1 style={{ fontSize: '3rem', marginBottom: '20px', color: '#ff6b35', fontWeight: 800 }}>Welcome, {user.name}!</h1>
        <div style={{ 
          padding: '30px', 
          backgroundColor: 'rgba(100, 108, 255, 0.1)', 
          borderRadius: '12px',
          backdropFilter: 'blur(10px)',
          border: '1px solid rgba(100, 108, 255, 0.3)',
        }}>
          <p style={{ fontSize: '1.1rem', marginBottom: '10px', color: 'rgba(255, 255, 255, 0.9)' }}>
            <strong style={{ color: '#ffffff' }}>Email:</strong> {user.email}
          </p>
          <p style={{ fontSize: '1.1rem', color: 'rgba(255, 255, 255, 0.9)' }}>
            <strong style={{ color: '#ffffff' }}>Role:</strong> {user.is_member ? 'Member' : 'User'}
          </p>
          
          {user.is_member && (
            <div style={{ marginTop: '30px', padding: '20px', backgroundColor: 'rgba(100, 108, 255, 0.2)', borderRadius: '8px', border: '1px solid rgba(100, 108, 255, 0.3)' }}>
              <h3 style={{ color: '#ff6b35', marginBottom: '10px', fontSize: '1.3rem', fontWeight: 700 }}>Member Area</h3>
              <p style={{ color: 'rgba(255, 255, 255, 0.9)' }}>You are a member of this system. Visit the Membership page to view available offers.</p>
            </div>
          )}
        </div>
      </div>
    </MemberLayout>
  )
}

export default Dashboard

