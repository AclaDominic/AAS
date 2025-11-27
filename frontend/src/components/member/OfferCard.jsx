function OfferCard({ offer, promo, firstTimeDiscount, formatPrice, getDurationText, calculatePriceWithDiscount, onPurchase }) {
  const originalPrice = parseFloat(offer.price)
  const finalPrice = promo || firstTimeDiscount 
    ? calculatePriceWithDiscount(offer, promo, firstTimeDiscount)
    : originalPrice

  const handleClick = () => {
    if (onPurchase) {
      onPurchase(offer, promo, firstTimeDiscount)
    }
  }

  return (
    <div
      style={{
        backgroundColor: '#646cff',
        borderRadius: '12px',
        padding: '20px',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.3)',
        cursor: 'pointer',
        transition: 'transform 0.2s, box-shadow 0.2s',
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.transform = 'translateY(-2px)'
        e.currentTarget.style.boxShadow = '0 6px 12px rgba(0, 0, 0, 0.4)'
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.transform = 'translateY(0)'
        e.currentTarget.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.3)'
      }}
      onClick={handleClick}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: '20px', flex: 1 }}>
        <div style={{ 
          color: '#ffffff', 
          fontSize: '1.5rem', 
          fontWeight: 'bold',
        }}>
          {formatPrice(finalPrice)}
        </div>
        <div style={{ 
          color: 'rgba(255, 255, 255, 0.9)', 
          fontSize: '1rem',
          marginLeft: 'auto',
        }}>
          {getDurationText(offer)}
        </div>
      </div>
      <div
        style={{
          width: '40px',
          height: '40px',
          borderRadius: '50%',
          backgroundColor: 'rgba(255, 255, 255, 0.2)',
          border: 'none',
          color: '#ffffff',
          fontSize: '1.2rem',
          cursor: 'pointer',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          transition: 'background-color 0.2s',
          flexShrink: 0,
        }}
        onMouseEnter={(e) => {
          e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.3)'
        }}
        onMouseLeave={(e) => {
          e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.2)'
        }}
      >
        →
      </div>
    </div>
  )
}

export default OfferCard
