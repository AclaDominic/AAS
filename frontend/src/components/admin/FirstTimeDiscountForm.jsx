import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'

function FirstTimeDiscountForm({ discount, onClose, onSuccess }) {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    discount_type: 'PERCENTAGE',
    discount_value: '',
    start_date: '',
    end_date: '',
    is_active: true,
    applicable_to_category: 'ALL',
  })
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState({})

  useEffect(() => {
    if (discount) {
      setFormData({
        name: discount.name || '',
        description: discount.description || '',
        discount_type: discount.discount_type || 'PERCENTAGE',
        discount_value: discount.discount_value || '',
        start_date: discount.start_date ? discount.start_date.split('T')[0] : '',
        end_date: discount.end_date ? discount.end_date.split('T')[0] : '',
        is_active: discount.is_active !== undefined ? discount.is_active : true,
        applicable_to_category: discount.applicable_to_category || 'ALL',
      })
    } else {
      // Set default dates for new discount
      const today = new Date().toISOString().split('T')[0]
      const nextMonth = new Date()
      nextMonth.setMonth(nextMonth.getMonth() + 1)
      setFormData(prev => ({
        ...prev,
        start_date: today,
        end_date: nextMonth.toISOString().split('T')[0],
      }))
    }
  }, [discount])

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }))
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
    if (!formData.discount_value || parseFloat(formData.discount_value) < 0) {
      newErrors.discount_value = 'Valid discount value is required'
    }
    if (!formData.start_date) newErrors.start_date = 'Start date is required'
    if (!formData.end_date) newErrors.end_date = 'End date is required'
    if (formData.start_date && formData.end_date && new Date(formData.start_date) >= new Date(formData.end_date)) {
      newErrors.end_date = 'End date must be after start date'
    }
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
        discount_value: parseFloat(formData.discount_value),
        start_date: `${formData.start_date}T00:00:00`,
        end_date: `${formData.end_date}T23:59:59`,
      }

      if (discount) {
        await offersService.updateFirstTimeDiscount(discount.id, data)
      } else {
        await offersService.createFirstTimeDiscount(data)
      }
      onSuccess()
    } catch (error) {
      console.error('Error saving first-time discount:', error)
      const errorMessage = error.response?.data?.message || 'Failed to save first-time discount'
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
        <h2 style={{ marginTop: 0 }}>{discount ? 'Edit First-Time Discount' : 'Create First-Time Discount'}</h2>
        
        <form onSubmit={handleSubmit}>
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
              Discount Type *
            </label>
            <select
              name="discount_type"
              value={formData.discount_type}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            >
              <option value="PERCENTAGE">Percentage</option>
              <option value="FIXED_AMOUNT">Fixed Amount</option>
            </select>
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Discount Value *
            </label>
            <input
              type="number"
              name="discount_value"
              value={formData.discount_value}
              onChange={handleChange}
              step="0.01"
              min="0"
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.discount_value ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.discount_value && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.discount_value}</div>}
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Applicable To Category
            </label>
            <select
              name="applicable_to_category"
              value={formData.applicable_to_category}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
            >
              <option value="ALL">All Categories</option>
              <option value="GYM">GYM</option>
              <option value="BADMINTON_COURT">Badminton Court</option>
            </select>
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Start Date *
            </label>
            <input
              type="date"
              name="start_date"
              value={formData.start_date}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.start_date ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.start_date && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.start_date}</div>}
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              End Date *
            </label>
            <input
              type="date"
              name="end_date"
              value={formData.end_date}
              onChange={handleChange}
              style={{ width: '100%', padding: '8px', borderRadius: '4px', border: errors.end_date ? '1px solid red' : '1px solid #ddd' }}
            />
            {errors.end_date && <div style={{ color: 'red', fontSize: '12px', marginTop: '4px' }}>{errors.end_date}</div>}
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
              {loading ? 'Saving...' : (discount ? 'Update' : 'Create')}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default FirstTimeDiscountForm

