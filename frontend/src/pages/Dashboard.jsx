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
      <div style={{ padding: '40px', minHeight: 'calc(100vh - 80px)' }}>
        <div style={{ maxWidth: '1200px', margin: '0 auto', color: '#ffffff' }}>
          <h1 style={{ fontSize: '3rem', marginBottom: '20px', color: '#ff6b35' }}>Welcome, {user.name}!</h1>
          <div style={{ 
            padding: '30px', 
            backgroundColor: 'rgba(100, 108, 255, 0.1)', 
            borderRadius: '12px',
            backdropFilter: 'blur(10px)',
          }}>
            <p style={{ fontSize: '1.1rem', marginBottom: '10px' }}><strong>Email:</strong> {user.email}</p>
            <p style={{ fontSize: '1.1rem' }}><strong>Role:</strong> {user.is_member ? 'Member' : 'User'}</p>
            
            {user.is_member && (
              <div style={{ marginTop: '30px', padding: '20px', backgroundColor: 'rgba(100, 108, 255, 0.2)', borderRadius: '8px' }}>
                <h3 style={{ color: '#ff6b35', marginBottom: '10px' }}>Member Area</h3>
                <p>You are a member of this system. Visit the Membership page to view available offers.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </MemberLayout>
  )
}

export default Dashboard

