import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import FirstTimeDiscountForm from './FirstTimeDiscountForm'

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
    return <div>Loading first-time discounts...</div>
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2>First-Time Discounts</h2>
        <button onClick={handleCreate} style={{ padding: '10px 20px', backgroundColor: '#646cff', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer' }}>
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

      <table style={{ width: '100%', borderCollapse: 'collapse', backgroundColor: 'white', borderRadius: '8px', overflow: 'hidden' }}>
        <thead>
          <tr style={{ backgroundColor: '#f5f5f5' }}>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Name</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Discount</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Category</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Start Date</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>End Date</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Status</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Actions</th>
          </tr>
        </thead>
        <tbody>
          {discounts.length === 0 ? (
            <tr>
              <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: '#666' }}>
                No first-time discounts found. Create your first discount!
              </td>
            </tr>
          ) : (
            discounts.map((discount) => (
              <tr key={discount.id} style={{ borderBottom: '1px solid #eee' }}>
                <td style={{ padding: '12px' }}>{discount.name}</td>
                <td style={{ padding: '12px' }}>
                  {discount.discount_type === 'PERCENTAGE' 
                    ? `${discount.discount_value}%` 
                    : `$${parseFloat(discount.discount_value).toFixed(2)}`}
                </td>
                <td style={{ padding: '12px' }}>
                  {discount.applicable_to_category ? discount.applicable_to_category.replace('_', ' ') : 'ALL'}
                </td>
                <td style={{ padding: '12px' }}>{formatDate(discount.start_date)}</td>
                <td style={{ padding: '12px' }}>{formatDate(discount.end_date)}</td>
                <td style={{ padding: '12px' }}>
                  <span style={{ 
                    padding: '4px 8px', 
                    borderRadius: '4px', 
                    backgroundColor: isActive(discount) ? '#d4edda' : '#f8d7da',
                    color: isActive(discount) ? '#155724' : '#721c24'
                  }}>
                    {isActive(discount) ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td style={{ padding: '12px' }}>
                  <button 
                    onClick={() => handleEdit(discount)}
                    style={{ marginRight: '8px', padding: '6px 12px', backgroundColor: '#17a2b8', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
                  >
                    Edit
                  </button>
                  <button 
                    onClick={() => handleDelete(discount.id)}
                    style={{ padding: '6px 12px', backgroundColor: '#dc3545', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
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
  )
}

export default FirstTimeDiscountsList

