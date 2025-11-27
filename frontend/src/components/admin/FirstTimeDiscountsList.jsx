import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import FirstTimeDiscountForm from './FirstTimeDiscountForm'
import '../../pages/admin/AdminPages.css'

function FirstTimeDiscountsList() {
  const [discounts, setDiscounts] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editingDiscount, setEditingDiscount] = useState(null)

  useEffect(() => {
    loadDiscounts()
  }, [])

  const loadDiscounts = async () => {
    try {
      setLoading(true)
      const data = await offersService.getFirstTimeDiscounts()
      setDiscounts(data)
    } catch (error) {
      console.error('Error loading first-time discounts:', error)
      alert('Failed to load first-time discounts')
    } finally {
      setLoading(false)
    }
  }

  const handleCreate = () => {
    setEditingDiscount(null)
    setShowForm(true)
  }

  const handleEdit = (discount) => {
    setEditingDiscount(discount)
    setShowForm(true)
  }

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this first-time discount?')) {
      return
    }

    try {
      await offersService.deleteFirstTimeDiscount(id)
      loadDiscounts()
    } catch (error) {
      console.error('Error deleting first-time discount:', error)
      alert('Failed to delete first-time discount')
    }
  }

  const handleFormClose = () => {
    setShowForm(false)
    setEditingDiscount(null)
  }

  const handleFormSuccess = () => {
    handleFormClose()
    loadDiscounts()
  }

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
  }

  const isActive = (discount) => {
    const now = new Date()
    const start = new Date(discount.start_date)
    const end = new Date(discount.end_date)
    return discount.is_active && now >= start && now <= end
  }

  if (loading) {
    return (
      <div className="admin-loading">
        <div className="admin-spinner"></div>
        <p>Loading first-time discounts...</p>
      </div>
    )
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 className="admin-card-title" style={{ margin: 0, fontSize: '1.8rem', color: 'rgba(255, 255, 255, 0.9)' }}>First-Time Discounts</h2>
        <button onClick={handleCreate} className="admin-button admin-button-primary">
          Create Discount
        </button>
      </div>

      {showForm && (
        <FirstTimeDiscountForm
          discount={editingDiscount}
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
            {discounts.length === 0 ? (
              <tr>
                <td colSpan="7" className="admin-empty">
                  No first-time discounts found. Create your first discount!
                </td>
              </tr>
            ) : (
              discounts.map((discount) => (
                <tr key={discount.id}>
                  <td>{discount.name}</td>
                  <td>
                    {discount.discount_type === 'PERCENTAGE' 
                      ? `${discount.discount_value}%` 
                      : `₱${parseFloat(discount.discount_value).toFixed(2)}`}
                  </td>
                  <td>
                    {discount.applicable_to_category ? discount.applicable_to_category.replace('_', ' ') : 'ALL'}
                  </td>
                  <td>{formatDate(discount.start_date)}</td>
                  <td>{formatDate(discount.end_date)}</td>
                  <td>
                    <span className={`admin-badge ${isActive(discount) ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                      {isActive(discount) ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td>
                    <button 
                      onClick={() => handleEdit(discount)}
                      className="admin-button admin-button-secondary"
                      style={{ marginRight: '8px', padding: '6px 12px', fontSize: '0.85rem' }}
                    >
                      Edit
                    </button>
                    <button 
                      onClick={() => handleDelete(discount.id)}
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

export default FirstTimeDiscountsList

