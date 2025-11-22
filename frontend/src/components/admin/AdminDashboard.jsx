import { useState, useEffect } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { membersService } from '../../services/membersService'

function AdminDashboard() {
  const { user } = useAuth()
  const [stats, setStats] = useState(null)

  useEffect(() => {
    loadStats()
  }, [])

  const loadStats = async () => {
    try {
      const data = await membersService.getStats()
      setStats(data)
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }

  return (
    <div style={{ padding: '40px' }}>
      <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Admin Dashboard</h1>
      
      <div style={{ padding: '20px', border: '1px solid #ccc', borderRadius: '8px', marginBottom: '20px' }}>
        <h2>Welcome, {user?.name}!</h2>
        <p><strong>Email:</strong> {user?.email}</p>
        <p><strong>Role:</strong> Admin</p>
      </div>

      {/* Member Statistics Cards */}
      {stats && (
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '20px',
            marginBottom: '20px',
          }}
        >
          <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
            <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Total Members</div>
            <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#646cff' }}>{stats.total_members}</div>
          </div>
          <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
            <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Active</div>
            <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#28a745' }}>{stats.active_members}</div>
          </div>
          <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
            <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Expired</div>
            <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#ffc107' }}>{stats.expired_members}</div>
          </div>
          <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
            <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Total Revenue</div>
            <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#646cff' }}>
              ₱{new Intl.NumberFormat('en-PH').format(stats.total_revenue)}
            </div>
          </div>
        </div>
      )}
      
      <div style={{ marginTop: '20px', padding: '15px', backgroundColor: '#f0f0f0', borderRadius: '5px' }}>
        <h3>Admin Panel</h3>
        <p>You have admin privileges. This is your dedicated admin dashboard.</p>
        <p style={{ marginTop: '10px', color: '#666' }}>
          Use the sidebar to navigate to different management sections.
        </p>
      </div>
    </div>
  )
}

export default AdminDashboard


