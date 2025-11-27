import { useState, useEffect } from 'react'
import PublicHeader from '../components/PublicHeader'
import './PublicPages.css'

function FAQ() {
  const [openIndex, setOpenIndex] = useState(null)

  useEffect(() => {
    document.body.classList.add('public-page-active')
    document.getElementById('root')?.classList.add('public-root')
    
    return () => {
      document.body.classList.remove('public-page-active')
      document.getElementById('root')?.classList.remove('public-root')
    }
  }, [])

  const faqs = [
    {
      question: "What are your operating hours?",
      answer: "We are open from 6:00 AM to 10:00 PM, Monday through Sunday. Members with 24/7 access can use the facilities outside regular hours."
    },
    {
      question: "How much does it cost to rent a badminton court?",
      answer: "Court rental starts at P20 per hour. We also offer discounted rates for members and bulk bookings. Check our membership plans for the best deals."
    },
    {
      question: "Do I need to be a member to use the facilities?",
      answer: "No, you can use our facilities as a walk-in guest. However, members enjoy significant discounts, priority booking, and exclusive access to certain facilities."
    },
    {
      question: "What equipment is available at the gym?",
      answer: "Our gym features a complete range of equipment including free weights, cardio machines (treadmills, ellipticals, bikes), resistance machines, and functional training areas."
    },
    {
      question: "Can I book a court online?",
      answer: "Yes! We have an online reservation system that allows you to book courts in advance. Simply create an account and you can book up to 7 days in advance."
    },
    {
      question: "Do you offer training or coaching services?",
      answer: "Yes, we have experienced coaches available for both badminton and fitness training. You can book individual sessions or join group classes."
    },
    {
      question: "What payment methods do you accept?",
      answer: "We accept cash, credit/debit cards, and digital payment methods. Members can also set up automatic billing for their membership fees."
    },
    {
      question: "Is there parking available?",
      answer: "Yes, we have parking facilities available for our members and guests. Parking is free for members."
    },
    {
      question: "Can I cancel or reschedule my court booking?",
      answer: "Yes, you can cancel or reschedule your booking up to 2 hours before your scheduled time through our online system or by contacting us."
    },
    {
      question: "Do you have locker rooms and showers?",
      answer: "Yes, we have fully equipped locker rooms with showers, changing areas, and secure lockers for your convenience."
    }
  ]

  const toggleFAQ = (index) => {
    setOpenIndex(openIndex === index ? null : index)
  }

  return (
    <div className="public-page">
      <PublicHeader />
      
      <main className="public-content">
        <div className="content-container">
          <h1 className="page-title">Frequently Asked Questions</h1>
          
          <div className="faq-section">
            {faqs.map((faq, index) => (
              <div key={index} className="faq-item">
                <button 
                  className={`faq-question ${openIndex === index ? 'open' : ''}`}
                  onClick={() => toggleFAQ(index)}
                >
                  <span>{faq.question}</span>
                  <span className="faq-icon">{openIndex === index ? '−' : '+'}</span>
                </button>
                {openIndex === index && (
                  <div className="faq-answer">
                    <p>{faq.answer}</p>
                  </div>
                )}
              </div>
            ))}
          </div>

          <div className="cta-section">
            <h2>Still Have Questions?</h2>
            <p>Feel free to contact us or visit our facility. We're here to help!</p>
            <a href="/register" className="cta-button">Get in Touch</a>
          </div>
        </div>
      </main>
    </div>
  )
}

export default FAQ

