import AdminSidebar from '../admin/AdminSidebar'

function AdminLayout({ children }) {
  return (
    <div style={{ display: 'flex', minHeight: '100vh' }}>
      <AdminSidebar />
      <main
        style={{
          marginLeft: '250px',
          flex: 1,
          backgroundColor: '#f5f5f5',
          minHeight: '100vh',
        }}
      >
        {children}
      </main>
    </div>
  )
}

export default AdminLayout


