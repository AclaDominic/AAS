import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import OfferForm from './OfferForm'
import '../../pages/admin/AdminPages.css'

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
    return (
      <div className="admin-loading">
        <div className="admin-spinner"></div>
        <p>Loading offers...</p>
      </div>
    )
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 className="admin-card-title" style={{ margin: 0, fontSize: '1.8rem', color: 'rgba(255, 255, 255, 0.9)' }}>Membership Offers</h2>
        <button onClick={handleCreate} className="admin-button admin-button-primary">
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

      <div className="admin-table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Billing Type</th>
              <th>Duration</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {offers.length === 0 ? (
              <tr>
                <td colSpan="7" className="admin-empty">
                  No offers found. Create your first offer!
                </td>
              </tr>
            ) : (
              offers.map((offer) => (
                <tr key={offer.id}>
                  <td>{offer.name}</td>
                  <td>{offer.category.replace('_', ' ')}</td>
                  <td>₱{parseFloat(offer.price).toFixed(2)}</td>
                  <td>{offer.billing_type.replace('_', ' ')}</td>
                  <td>
                    {offer.duration_value} {offer.duration_type.toLowerCase()}{offer.duration_value > 1 ? 's' : ''}
                  </td>
                  <td>
                    <span className={`admin-badge ${offer.is_active ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                      {offer.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td>
                    <button 
                      onClick={() => handleEdit(offer)}
                      className="admin-button admin-button-secondary"
                      style={{ marginRight: '8px', padding: '6px 12px', fontSize: '0.85rem' }}
                    >
                      Edit
                    </button>
                    <button 
                      onClick={() => handleDelete(offer.id)}
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

export default OffersList

