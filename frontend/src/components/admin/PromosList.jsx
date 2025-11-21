import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import PromoForm from './PromoForm'

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
    return <div>Loading promos...</div>
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2>Promos</h2>
        <button onClick={handleCreate} style={{ padding: '10px 20px', backgroundColor: '#646cff', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer' }}>
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
          {promos.length === 0 ? (
            <tr>
              <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: '#666' }}>
                No promos found. Create your first promo!
              </td>
            </tr>
          ) : (
            promos.map((promo) => (
              <tr key={promo.id} style={{ borderBottom: '1px solid #eee' }}>
                <td style={{ padding: '12px' }}>{promo.name}</td>
                <td style={{ padding: '12px' }}>
                  {promo.discount_type === 'PERCENTAGE' 
                    ? `${promo.discount_value}%` 
                    : `$${parseFloat(promo.discount_value).toFixed(2)}`}
                </td>
                <td style={{ padding: '12px' }}>
                  {promo.applicable_to_category ? promo.applicable_to_category.replace('_', ' ') : 'ALL'}
                </td>
                <td style={{ padding: '12px' }}>{formatDate(promo.start_date)}</td>
                <td style={{ padding: '12px' }}>{formatDate(promo.end_date)}</td>
                <td style={{ padding: '12px' }}>
                  <span style={{ 
                    padding: '4px 8px', 
                    borderRadius: '4px', 
                    backgroundColor: isActive(promo) ? '#d4edda' : '#f8d7da',
                    color: isActive(promo) ? '#155724' : '#721c24'
                  }}>
                    {isActive(promo) ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td style={{ padding: '12px' }}>
                  <button 
                    onClick={() => handleEdit(promo)}
                    style={{ marginRight: '8px', padding: '6px 12px', backgroundColor: '#17a2b8', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
                  >
                    Edit
                  </button>
                  <button 
                    onClick={() => handleDelete(promo.id)}
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

export default PromosList

