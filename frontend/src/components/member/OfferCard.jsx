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
    <div style={{
      backgroundColor: '#646cff',
      borderRadius: '12px',
      padding: '20px',
      marginBottom: '15px',
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
      <div>
        <div style={{ 
          color: '#ffffff', 
          fontSize: '1.5rem', 
          fontWeight: 'bold',
          marginBottom: '5px',
        }}>
          {formatPrice(finalPrice)}
        </div>
        <div style={{ 
          color: 'rgba(255, 255, 255, 0.8)', 
          fontSize: '0.9rem',
        }}>
          {getDurationText(offer)}
        </div>
        {originalPrice !== finalPrice && (
          <div style={{ 
            color: 'rgba(255, 255, 255, 0.6)', 
            fontSize: '0.8rem',
            textDecoration: 'line-through',
            marginTop: '4px',
          }}>
            {formatPrice(originalPrice)}
          </div>
        )}
        {(promo || firstTimeDiscount) && (
          <div style={{ 
            color: '#ff6b35', 
            fontSize: '0.75rem',
            marginTop: '4px',
            fontWeight: 'bold',
          }}>
            {promo ? `🎁 ${promo.name}` : `✨ ${firstTimeDiscount.name}`}
          </div>
        )}
      </div>
      <button style={{
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
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.3)'
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.2)'
      }}
      >
        →
      </button>
    </div>
  )
}

export default OfferCard

