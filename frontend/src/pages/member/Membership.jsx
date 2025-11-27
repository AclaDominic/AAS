import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import MemberLayout from '../../components/layout/MemberLayout'
import OfferCard from '../../components/member/OfferCard'
import PaymentMethodModal from '../../components/member/PaymentMethodModal'
import './MemberPages.css'

function Membership() {
  const [offers, setOffers] = useState([])
  const [promos, setPromos] = useState([])
  const [firstTimeDiscounts, setFirstTimeDiscounts] = useState([])
  const [eligibility, setEligibility] = useState(null)
  const [loading, setLoading] = useState(true)
  const [selectedOffer, setSelectedOffer] = useState(null)
  const [selectedPromo, setSelectedPromo] = useState(null)
  const [selectedDiscount, setSelectedDiscount] = useState(null)
  const [showPaymentModal, setShowPaymentModal] = useState(false)

  useEffect(() => {
    loadData()
  }, [])

  const loadData = async () => {
    try {
      setLoading(true)
      const [offersData, promosData, discountsData, eligibilityData] = await Promise.all([
        offersService.getActiveOffers(),
        offersService.getActivePromos(),
        offersService.getEligibleFirstTimeDiscounts(),
        offersService.checkEligibility(),
      ])
      
      setOffers(offersData)
      setPromos(promosData)
      setFirstTimeDiscounts(discountsData)
      setEligibility(eligibilityData)
    } catch (error) {
      console.error('Error loading data:', error)
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  const getDurationText = (offer) => {
    if (offer.duration_value === 1) {
      return offer.duration_type === 'MONTH' ? 'Per Month' : 'Per Year'
    }
    return `${offer.duration_value} ${offer.duration_type === 'MONTH' ? 'Months' : 'Years'}`
  }

  const calculatePriceWithDiscount = (offer, promo, discount) => {
    let price = parseFloat(offer.price)
    
    if (discount) {
      if (discount.discount_type === 'PERCENTAGE') {
        price = price * (1 - discount.discount_value / 100)
      } else {
        price = price - discount.discount_value
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

  const isPromoApplicable = (promo, category) => {
    return promo.applicable_to_category === 'ALL' || promo.applicable_to_category === category
  }

  const isDiscountApplicable = (discount, category) => {
    return discount.applicable_to_category === 'ALL' || discount.applicable_to_category === category
  }

  const handlePurchase = (offer, promo, firstTimeDiscount) => {
    setSelectedOffer(offer)
    setSelectedPromo(promo || null)
    setSelectedDiscount(firstTimeDiscount || null)
    setShowPaymentModal(true)
  }

  const handlePaymentSuccess = () => {
    setShowPaymentModal(false)
    setSelectedOffer(null)
    setSelectedPromo(null)
    setSelectedDiscount(null)
    loadData()
  }

  const gymOffers = offers.filter(o => o.category === 'GYM')
  const badmintonOffers = offers.filter(o => o.category === 'BADMINTON_COURT')

  if (loading) {
    return (
      <MemberLayout>
        <div className="member-loading">Loading...</div>
      </MemberLayout>
    )
  }

  return (
    <MemberLayout>
      {showPaymentModal && selectedOffer && (
        <PaymentMethodModal
          offer={selectedOffer}
          promo={selectedPromo}
          firstTimeDiscount={selectedDiscount}
          onClose={() => {
            setShowPaymentModal(false)
            setSelectedOffer(null)
            setSelectedPromo(null)
            setSelectedDiscount(null)
          }}
          onSuccess={handlePaymentSuccess}
        />
      )}
      <div className="membership-container">
        <div className="membership-grid">
          {/* GYM Section */}
          <div className="membership-section">
            <h1 className="membership-title-large">GYM</h1>
            <h2 className="membership-title-small">OFFERS</h2>

            <div className="membership-offers-list">
              {gymOffers.map((offer) => (
                <OfferCard
                  key={offer.id}
                  offer={offer}
                  formatPrice={formatPrice}
                  getDurationText={getDurationText}
                  onPurchase={handlePurchase}
                />
              ))}
            </div>
          </div>

          {/* BADMINTON Section */}
          <div className="membership-section">
            <h1 className="membership-title-large">BADMINTON</h1>
            <h2 className="membership-title-small">OFFERS</h2>

            <div className="membership-offers-list">
              {badmintonOffers.map((offer) => (
                <OfferCard
                  key={offer.id}
                  offer={offer}
                  formatPrice={formatPrice}
                  getDurationText={getDurationText}
                  onPurchase={handlePurchase}
                />
              ))}
            </div>
          </div>
        </div>
      </div>
    </MemberLayout>
  )
}

export default Membership
