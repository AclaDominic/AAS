import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import PromoForm from './PromoForm'
import '../../pages/admin/AdminPages.css'

function PromosList() {
  const [promos, setPromos] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editingPromo, setEditingPromo] = useState(null)

  useEffect(() => {
    loadPromos()
  }, [])

  const loadPromos = async () => {
    try {
      setLoading(true)
      const data = await offersService.getPromos()
      setPromos(data)
    } catch (error) {
      console.error('Error loading promos:', error)
      alert('Failed to load promos')
    } finally {
      setLoading(false)
    }
  }

  const handleCreate = () => {
    setEditingPromo(null)
    setShowForm(true)
  }

  const handleEdit = (promo) => {
    setEditingPromo(promo)
    setShowForm(true)
  }

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this promo?')) {
      return
    }

    try {
      await offersService.deletePromo(id)
      loadPromos()
    } catch (error) {
      console.error('Error deleting promo:', error)
      alert('Failed to delete promo')
    }
  }

  const handleFormClose = () => {
    setShowForm(false)
    setEditingPromo(null)
  }

  const handleFormSuccess = () => {
    handleFormClose()
    loadPromos()
  }

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
  }

  const isActive = (promo) => {
    const now = new Date()
    const start = new Date(promo.start_date)
    const end = new Date(promo.end_date)
    return promo.is_active && now >= start && now <= end
  }

  if (loading) {
    return (
      <div className="admin-loading">
        <div className="admin-spinner"></div>
        <p>Loading promos...</p>
      </div>
    )
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 className="admin-card-title" style={{ margin: 0, fontSize: '1.8rem', color: 'rgba(255, 255, 255, 0.9)' }}>Promos</h2>
        <button onClick={handleCreate} className="admin-button admin-button-primary">
          Create Promo
        </button>
      </div>

      {showForm && (
        <PromoForm
          promo={editingPromo}
          onClose={handleFormClose}
          onSuccess={handleFormSuccess}
        />
      )}

      <div className="admin-table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Discount</th>
              <th>Category</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {promos.length === 0 ? (
              <tr>
                <td colSpan="7" className="admin-empty">
                  No promos found. Create your first promo!
                </td>
              </tr>
            ) : (
              promos.map((promo) => (
                <tr key={promo.id}>
                  <td>{promo.name}</td>
                  <td>
                    {promo.discount_type === 'PERCENTAGE' 
                      ? `${promo.discount_value}%` 
                      : `₱${parseFloat(promo.discount_value).toFixed(2)}`}
                  </td>
                  <td>
                    {promo.applicable_to_category ? promo.applicable_to_category.replace('_', ' ') : 'ALL'}
                  </td>
                  <td>{formatDate(promo.start_date)}</td>
                  <td>{formatDate(promo.end_date)}</td>
                  <td>
                    <span className={`admin-badge ${isActive(promo) ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                      {isActive(promo) ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td>
                    <button 
                      onClick={() => handleEdit(promo)}
                      className="admin-button admin-button-secondary"
                      style={{ marginRight: '8px', padding: '6px 12px', fontSize: '0.85rem' }}
                    >
                      Edit
                    </button>
                    <button 
                      onClick={() => handleDelete(promo.id)}
                      className="admin-button admin-button-danger"
                      style={{ padding: '6px 12px', fontSize: '0.85rem' }}
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}

export default PromosList

