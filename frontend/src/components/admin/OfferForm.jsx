import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'

function OfferForm({ offer, onClose, onSuccess }) {
  const [formData, setFormData] = useState({
    category: 'GYM',
    name: '',
    description: '',
    price: '',
    billing_type: 'RECURRING',
    duration_type: 'MONTH',
    duration_value: 1,
    is_active: true,
  })
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState({})

  useEffect(() => {
    if (offer) {
      setFormData({
        category: offer.category || 'GYM',
        name: offer.name || '',
        description: offer.description || '',
        price: offer.price || '',
        billing_type: offer.billing_type || 'RECURRING',
        duration_type: offer.duration_type || 'MONTH',
        duration_value: offer.duration_value || 1,
        is_active: offer.is_active !== undefined ? offer.is_active : true,
      })
    }
  }, [offer])

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }))
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev }
        delete newErrors[name]
        return newErrors
      })
    }
  }

  const validate = () => {
    const newErrors = {}
    if (!formData.name.trim()) newErrors.name = 'Name is required'
    if (!formData.price || parseFloat(formData.price) < 0) newErrors.price = 'Valid price is required'
    if (!formData.duration_value || formData.duration_value < 1) newErrors.duration_value = 'Duration must be at least 1'
    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!validate()) return

    try {
      setLoading(true)
      const data = {
        ...formData,
        price: parseFloat(formData.price),
        duration_value: parseInt(formData.duration_value),
      }

      if (offer) {
        await offersService.updateOffer(offer.id, data)
      } else {
        await offersService.createOffer(data)
      }
      onSuccess()
    } catch (error) {
      console.error('Error saving offer:', error)
      const errorMessage = error.response?.data?.message || 'Failed to save offer'
      alert(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1000,
    }}>
      <div style={{
        backgroundColor: 'white',
        padding: '30px',
        borderRadius: '8px',
        width: '90%',
        maxWidth: '600px',
        maxHeight: '90vh',
        overflowY: 'auto',
      }}>
        <h2 style={{ marginTop: 0 }}>{offer ? 'Edit Offer' : 'Create Offer'}</h2>
        
        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Category *
            </label>
            <select
              name="category"
              value={formData.category}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            >
              <option value="GYM">GYM</option>
              <option value="BADMINTON_COURT">Badminton Court</option>
            </select>
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Name *
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.name ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.name && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.name}</div>}
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Description
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows="3"
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            />
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Price *
            </label>
            <input
              type="number"
              name="price"
              value={formData.price}
              onChange={handleChange}
              step="0.01"
              min="0"
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.price ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.price && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.price}</div>}
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Billing Type *
            </label>
            <select
              name="billing_type"
              value={formData.billing_type}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            >
              <option value="RECURRING">Recurring</option>
              <option value="NON_RECURRING">Non-Recurring</option>
            </select>
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Duration Type *
            </label>
            <select
              name="duration_type"
              value={formData.duration_type}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            >
              <option value="MONTH">Month</option>
              <option value="YEAR">Year</option>
            </select>
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Duration Value *
            </label>
            <input
              type="number"
              name="duration_value"
              value={formData.duration_value}
              onChange={handleChange}
              min="1"
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.duration_value ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.duration_value && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.duration_value}</div>}
          </div>

          <div style={{ marginBottom: '20px' }}>
            <label style={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}>
              <input
                type="checkbox"
                name="is_active"
                checked={formData.is_active}
                onChange={handleChange}
                style={{ marginRight: '8px' }}
              />
              Active
            </label>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
            <button
              type="button"
              onClick={onClose}
              style={{ padding: '10px 20px', backgroundColor: '#6c757d', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer' }}
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              style={{ padding: '10px 20px', backgroundColor: '#646cff', color: 'white', border: 'none', borderRadius: '6px', cursor: loading ? 'not-allowed' : 'pointer', opacity: loading ? 0.6 : 1 }}
            >
              {loading ? 'Saving...' : (offer ? 'Update' : 'Create')}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default OfferForm

