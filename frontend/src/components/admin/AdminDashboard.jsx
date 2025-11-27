import { useState, useEffect } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { membersService } from '../../services/membersService'
import '../../pages/admin/AdminPages.css'

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
    <div className="admin-page-container">
      <h1 className="admin-page-title">Admin Dashboard</h1>
      
      <div className="admin-card">
        <h2 className="admin-card-title">Welcome, {user?.name}!</h2>
        <p className="admin-card-text"><strong>Email:</strong> {user?.email}</p>
        <p className="admin-card-text"><strong>Role:</strong> Admin</p>
      </div>

      {/* Member Statistics Cards */}
      {stats && (
        <div className="admin-stats-grid">
          <div className="admin-stat-card">
            <div className="admin-stat-label">Total Members</div>
            <div className="admin-stat-value admin-stat-value-primary">{stats.total_members}</div>
          </div>
          <div className="admin-stat-card">
            <div className="admin-stat-label">Active</div>
            <div className="admin-stat-value admin-stat-value-success">{stats.active_members}</div>
          </div>
          <div className="admin-stat-card">
            <div className="admin-stat-label">Expired</div>
            <div className="admin-stat-value admin-stat-value-warning">{stats.expired_members}</div>
          </div>
          <div className="admin-stat-card">
            <div className="admin-stat-label">Total Revenue</div>
            <div className="admin-stat-value admin-stat-value-primary">
              ₱{new Intl.NumberFormat('en-PH').format(stats.total_revenue)}
            </div>
          </div>
        </div>
      )}
      
      <div className="admin-card">
        <h3 className="admin-card-title">Admin Panel</h3>
        <p className="admin-card-text">You have admin privileges. This is your dedicated admin dashboard.</p>
        <p className="admin-card-text" style={{ marginTop: '10px', color: 'rgba(255, 255, 255, 0.6)' }}>
          Use the sidebar to navigate to different management sections.
        </p>
      </div>
    </div>
  )
}

export default AdminDashboard


