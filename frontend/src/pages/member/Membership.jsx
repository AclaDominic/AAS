import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import MemberLayout from '../../components/layout/MemberLayout'
import OfferCard from '../../components/member/OfferCard'
import PaymentMethodModal from '../../components/member/PaymentMethodModal'

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
    // Reload data to refresh offers
    loadData()
  }

  const gymOffers = offers.filter(o => o.category === 'GYM')
  const badmintonOffers = offers.filter(o => o.category === 'BADMINTON_COURT')

  if (loading) {
    return (
      <MemberLayout>
        <div style={{ padding: '40px', textAlign: 'center', color: '#ffffff' }}>
          Loading...
        </div>
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
      <div style={{ padding: '40px', minHeight: 'calc(100vh - 80px)' }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '40px', maxWidth: '1400px', margin: '0 auto' }}>
          {/* GYM Section */}
          <div>
            <h1 style={{ 
              fontSize: '4rem', 
              fontWeight: 'bold', 
              color: '#ff6b35', 
              margin: '0 0 10px 0',
              lineHeight: '1',
            }}>
              GYM
            </h1>
            <h2 style={{ 
              fontSize: '1.5rem', 
              color: '#ffffff', 
              margin: '0 0 30px 0',
              fontWeight: 'normal',
            }}>
              OFFERS
            </h2>

            {/* Ordinary Offers */}
            <div style={{ marginBottom: '30px' }}>
              <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>Regular Offers</h3>
              <div style={{ 
                maxHeight: '400px', 
                overflowY: 'auto', 
                paddingRight: '10px',
                scrollbarWidth: 'thin',
                scrollbarColor: '#646cff #1a1a1a',
              }}>
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

            {/* Promos */}
            {promos.filter(p => isPromoApplicable(p, 'GYM')).length > 0 && (
              <div style={{ marginBottom: '30px' }}>
                <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>Promos</h3>
                <div style={{ 
                  maxHeight: '300px', 
                  overflowY: 'auto', 
                  paddingRight: '10px',
                  scrollbarWidth: 'thin',
                  scrollbarColor: '#646cff #1a1a1a',
                }}>
                  {promos
                    .filter(p => isPromoApplicable(p, 'GYM'))
                    .map((promo) => (
                      <div key={promo.id} style={{ marginBottom: '15px' }}>
                        {gymOffers.map((offer) => (
                          <OfferCard
                            key={`${offer.id}-${promo.id}`}
                            offer={offer}
                            promo={promo}
                            formatPrice={formatPrice}
                            getDurationText={getDurationText}
                            calculatePriceWithDiscount={calculatePriceWithDiscount}
                            onPurchase={handlePurchase}
                          />
                        ))}
                      </div>
                    ))}
                </div>
              </div>
            )}

            {/* First-Time Discounts */}
            {eligibility?.eligible && firstTimeDiscounts.filter(d => isDiscountApplicable(d, 'GYM')).length > 0 && (
              <div>
                <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>New User</h3>
                <div style={{ 
                  maxHeight: '300px', 
                  overflowY: 'auto', 
                  paddingRight: '10px',
                  scrollbarWidth: 'thin',
                  scrollbarColor: '#646cff #1a1a1a',
                }}>
                  {firstTimeDiscounts
                    .filter(d => isDiscountApplicable(d, 'GYM'))
                    .map((discount) => (
                      <div key={discount.id} style={{ marginBottom: '15px' }}>
                        {gymOffers.map((offer) => (
                          <OfferCard
                            key={`${offer.id}-${discount.id}`}
                            offer={offer}
                            firstTimeDiscount={discount}
                            formatPrice={formatPrice}
                            getDurationText={getDurationText}
                            calculatePriceWithDiscount={calculatePriceWithDiscount}
                            onPurchase={handlePurchase}
                          />
                        ))}
                      </div>
                    ))}
                </div>
              </div>
            )}
          </div>

          {/* BADMINTON Section */}
          <div>
            <h1 style={{ 
              fontSize: '4rem', 
              fontWeight: 'bold', 
              color: '#ff6b35', 
              margin: '0 0 10px 0',
              lineHeight: '1',
            }}>
              BADMINTON
            </h1>
            <h2 style={{ 
              fontSize: '1.5rem', 
              color: '#ffffff', 
              margin: '0 0 30px 0',
              fontWeight: 'normal',
            }}>
              OFFERS
            </h2>

            {/* Ordinary Offers */}
            <div style={{ marginBottom: '30px' }}>
              <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>Regular Offers</h3>
              <div style={{ 
                maxHeight: '400px', 
                overflowY: 'auto', 
                paddingRight: '10px',
                scrollbarWidth: 'thin',
                scrollbarColor: '#646cff #1a1a1a',
              }}>
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

            {/* Promos */}
            {promos.filter(p => isPromoApplicable(p, 'BADMINTON_COURT')).length > 0 && (
              <div style={{ marginBottom: '30px' }}>
                <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>Promos</h3>
                <div style={{ 
                  maxHeight: '300px', 
                  overflowY: 'auto', 
                  paddingRight: '10px',
                  scrollbarWidth: 'thin',
                  scrollbarColor: '#646cff #1a1a1a',
                }}>
                  {promos
                    .filter(p => isPromoApplicable(p, 'BADMINTON_COURT'))
                    .map((promo) => (
                      <div key={promo.id} style={{ marginBottom: '15px' }}>
                        {badmintonOffers.map((offer) => (
                          <OfferCard
                            key={`${offer.id}-${promo.id}`}
                            offer={offer}
                            promo={promo}
                            formatPrice={formatPrice}
                            getDurationText={getDurationText}
                            calculatePriceWithDiscount={calculatePriceWithDiscount}
                            onPurchase={handlePurchase}
                          />
                        ))}
                      </div>
                    ))}
                </div>
              </div>
            )}

            {/* First-Time Discounts */}
            {eligibility?.eligible && firstTimeDiscounts.filter(d => isDiscountApplicable(d, 'BADMINTON_COURT')).length > 0 && (
              <div>
                <h3 style={{ color: '#ffffff', marginBottom: '15px', fontSize: '1.2rem' }}>New User</h3>
                <div style={{ 
                  maxHeight: '300px', 
                  overflowY: 'auto', 
                  paddingRight: '10px',
                  scrollbarWidth: 'thin',
                  scrollbarColor: '#646cff #1a1a1a',
                }}>
                  {firstTimeDiscounts
                    .filter(d => isDiscountApplicable(d, 'BADMINTON_COURT'))
                    .map((discount) => (
                      <div key={discount.id} style={{ marginBottom: '15px' }}>
                        {badmintonOffers.map((offer) => (
                          <OfferCard
                            key={`${offer.id}-${discount.id}`}
                            offer={offer}
                            firstTimeDiscount={discount}
                            formatPrice={formatPrice}
                            getDurationText={getDurationText}
                            calculatePriceWithDiscount={calculatePriceWithDiscount}
                            onPurchase={handlePurchase}
                          />
                        ))}
                      </div>
                    ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </MemberLayout>
  )
}

export default Membership

