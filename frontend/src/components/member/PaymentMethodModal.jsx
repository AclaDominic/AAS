import { useState } from 'react'
import { offersService } from '../../services/offersService'

function PaymentMethodModal({ offer, promo, firstTimeDiscount, onClose, onSuccess }) {
  const [paymentMethod, setPaymentMethod] = useState('CASH')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  const calculatePrice = () => {
    let price = parseFloat(offer.price)
    
    if (firstTimeDiscount) {
      if (firstTimeDiscount.discount_type === 'PERCENTAGE') {
        price = price * (1 - firstTimeDiscount.discount_value / 100)
      } else {
        price = price - firstTimeDiscount.discount_value
      }
    } else if (promo) {
      if (promo.discount_type === 'PERCENTAGE') {
        price = price * (1 - promo.discount_value / 100)
      } else {
        price = price - promo.discount_value
      }
    }
    
    return Math.max(0, price)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const data = {
        membership_offer_id: offer.id,
        payment_method: paymentMethod,
      }

      if (promo) {
        data.promo_id = promo.id
      }

      if (firstTimeDiscount) {
        data.first_time_discount_id = firstTimeDiscount.id
      }

      const result = await offersService.initiatePayment(data)
      
      // If online payment and checkout URL is provided, redirect to Maya
      if ((paymentMethod === 'ONLINE_CARD' || paymentMethod === 'ONLINE_MAYA') && result.checkout_url) {
        window.location.href = result.checkout_url
        return // Don't call onSuccess yet - wait for callback
      }
      
      // For cash payments, show the payment code
      if (paymentMethod === 'CASH' && result.payment?.payment_code) {
        alert(`Payment initiated! Your payment code is: ${result.payment.payment_code}\n\nPlease bring this code when you pay in person.`)
        onSuccess()
      } else {
        alert('Payment processed successfully!')
        onSuccess()
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Failed to initiate payment'
      setError(errorMessage)
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
      backgroundColor: 'rgba(0, 0, 0, 0.7)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 2000,
    }}>
      <div style={{
        backgroundColor: '#1a1a1a',
        padding: '30px',
        borderRadius: '12px',
        width: '90%',
        maxWidth: '500px',
        border: '1px solid rgba(100, 108, 255, 0.3)',
      }}>
        <h2 style={{ marginTop: 0, color: '#ff6b35', marginBottom: '20px' }}>
          Select Payment Method
        </h2>

        <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: 'rgba(100, 108, 255, 0.1)', borderRadius: '8px' }}>
          <div style={{ color: '#ffffff', marginBottom: '5px' }}>
            <strong>{offer.name}</strong>
          </div>
          <div style={{ color: '#ffffff', fontSize: '1.2rem', fontWeight: 'bold' }}>
            {formatPrice(calculatePrice())}
          </div>
          {promo && (
            <div style={{ color: '#ff6b35', fontSize: '0.9rem', marginTop: '5px' }}>
              Promo: {promo.name}
            </div>
          )}
          {firstTimeDiscount && (
            <div style={{ color: '#ff6b35', fontSize: '0.9rem', marginTop: '5px' }}>
              Discount: {firstTimeDiscount.name}
            </div>
          )}
        </div>

        {error && (
          <div style={{ 
            padding: '10px', 
            backgroundColor: 'rgba(220, 53, 69, 0.2)', 
            color: '#dc3545', 
            borderRadius: '6px',
            marginBottom: '20px',
          }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: '20px' }}>
            <label style={{ 
              display: 'flex', 
              alignItems: 'center', 
              padding: '15px',
              backgroundColor: paymentMethod === 'CASH' ? 'rgba(100, 108, 255, 0.2)' : 'rgba(255, 255, 255, 0.05)',
              borderRadius: '8px',
              marginBottom: '10px',
              cursor: 'pointer',
              border: paymentMethod === 'CASH' ? '2px solid #646cff' : '2px solid transparent',
            }}>
              <input
                type="radio"
                name="payment_method"
                value="CASH"
                checked={paymentMethod === 'CASH'}
                onChange={(e) => setPaymentMethod(e.target.value)}
                style={{ marginRight: '10px' }}
              />
              <div>
                <div style={{ color: '#ffffff', fontWeight: 'bold' }}>Cash (Walk-in)</div>
                <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.9rem' }}>
                  Pay when you visit. You'll receive a payment code.
                </div>
              </div>
            </label>

            <label style={{ 
              display: 'flex', 
              alignItems: 'center', 
              padding: '15px',
              backgroundColor: paymentMethod === 'ONLINE_CARD' ? 'rgba(100, 108, 255, 0.2)' : 'rgba(255, 255, 255, 0.05)',
              borderRadius: '8px',
              cursor: 'pointer',
              border: paymentMethod === 'ONLINE_CARD' ? '2px solid #646cff' : '2px solid transparent',
            }}>
              <input
                type="radio"
                name="payment_method"
                value="ONLINE_CARD"
                checked={paymentMethod === 'ONLINE_CARD'}
                onChange={(e) => setPaymentMethod(e.target.value)}
                style={{ marginRight: '10px' }}
              />
              <div>
                <div style={{ color: '#ffffff', fontWeight: 'bold' }}>Pay with Credit/Debit Card</div>
                <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.9rem' }}>
                  Pay online using your credit or debit card. You'll receive a payment code for reference.
                </div>
              </div>
            </label>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
            <button
              type="button"
              onClick={onClose}
              style={{ 
                padding: '12px 24px', 
                backgroundColor: '#6c757d', 
                color: 'white', 
                border: 'none', 
                borderRadius: '6px', 
                cursor: 'pointer',
              }}
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              style={{ 
                padding: '12px 24px', 
                backgroundColor: '#646cff', 
                color: 'white', 
                border: 'none', 
                borderRadius: '6px', 
                cursor: loading ? 'not-allowed' : 'pointer',
                opacity: loading ? 0.6 : 1,
              }}
            >
              {loading ? 'Processing...' : 'Continue'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default PaymentMethodModal

