import { useAuth } from '../../contexts/AuthContext'

function AdminDashboard() {
  const { user } = useAuth()

  return (
    <div style={{ padding: '40px' }}>
      <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Admin Dashboard</h1>
      
      <div style={{ padding: '20px', border: '1px solid #ccc', borderRadius: '8px', marginBottom: '20px' }}>
        <h2>Welcome, {user?.name}!</h2>
        <p><strong>Email:</strong> {user?.email}</p>
        <p><strong>Role:</strong> Admin</p>
      </div>
      
      <div style={{ marginTop: '20px', padding: '15px', backgroundColor: '#f0f0f0', borderRadius: '5px' }}>
        <h3>Admin Panel</h3>
        <p>You have admin privileges. This is your dedicated admin dashboard.</p>
        <p style={{ marginTop: '10px', color: '#666' }}>
          Additional admin features and navigation items can be added to the sidebar.
        </p>
      </div>
    </div>
  )
}

export default AdminDashboard

