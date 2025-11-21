import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import OfferForm from './OfferForm'

function OffersList() {
  const [offers, setOffers] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editingOffer, setEditingOffer] = useState(null)

  useEffect(() => {
    loadOffers()
  }, [])

  const loadOffers = async () => {
    try {
      setLoading(true)
      const data = await offersService.getOffers()
      setOffers(data)
    } catch (error) {
      console.error('Error loading offers:', error)
      alert('Failed to load offers')
    } finally {
      setLoading(false)
    }
  }

  const handleCreate = () => {
    setEditingOffer(null)
    setShowForm(true)
  }

  const handleEdit = (offer) => {
    setEditingOffer(offer)
    setShowForm(true)
  }

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this offer?')) {
      return
    }

    try {
      await offersService.deleteOffer(id)
      loadOffers()
    } catch (error) {
      console.error('Error deleting offer:', error)
      alert('Failed to delete offer')
    }
  }

  const handleFormClose = () => {
    setShowForm(false)
    setEditingOffer(null)
  }

  const handleFormSuccess = () => {
    handleFormClose()
    loadOffers()
  }

  if (loading) {
    return <div>Loading offers...</div>
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2>Membership Offers</h2>
        <button onClick={handleCreate} style={{ padding: '10px 20px', backgroundColor: '#646cff', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer' }}>
          Create Offer
        </button>
      </div>

      {showForm && (
        <OfferForm
          offer={editingOffer}
          onClose={handleFormClose}
          onSuccess={handleFormSuccess}
        />
      )}

      <table style={{ width: '100%', borderCollapse: 'collapse', backgroundColor: 'white', borderRadius: '8px', overflow: 'hidden' }}>
        <thead>
          <tr style={{ backgroundColor: '#f5f5f5' }}>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Name</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Category</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Price</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Billing Type</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Duration</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Status</th>
            <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Actions</th>
          </tr>
        </thead>
        <tbody>
          {offers.length === 0 ? (
            <tr>
              <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: '#666' }}>
                No offers found. Create your first offer!
              </td>
            </tr>
          ) : (
            offers.map((offer) => (
              <tr key={offer.id} style={{ borderBottom: '1px solid #eee' }}>
                <td style={{ padding: '12px' }}>{offer.name}</td>
                <td style={{ padding: '12px' }}>{offer.category.replace('_', ' ')}</td>
                <td style={{ padding: '12px' }}>${parseFloat(offer.price).toFixed(2)}</td>
                <td style={{ padding: '12px' }}>{offer.billing_type.replace('_', ' ')}</td>
                <td style={{ padding: '12px' }}>
                  {offer.duration_value} {offer.duration_type.toLowerCase()}{offer.duration_value > 1 ? 's' : ''}
                </td>
                <td style={{ padding: '12px' }}>
                  <span style={{ 
                    padding: '4px 8px', 
                    borderRadius: '4px', 
                    backgroundColor: offer.is_active ? '#d4edda' : '#f8d7da',
                    color: offer.is_active ? '#155724' : '#721c24'
                  }}>
                    {offer.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td style={{ padding: '12px' }}>
                  <button 
                    onClick={() => handleEdit(offer)}
                    style={{ marginRight: '8px', padding: '6px 12px', backgroundColor: '#17a2b8', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
                  >
                    Edit
                  </button>
                  <button 
                    onClick={() => handleDelete(offer.id)}
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

export default OffersList

