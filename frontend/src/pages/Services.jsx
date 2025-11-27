import { useEffect } from 'react'
import PublicHeader from '../components/PublicHeader'
import './PublicPages.css'

function Services() {
  useEffect(() => {
    document.body.classList.add('public-page-active')
    document.getElementById('root')?.classList.add('public-root')
    
    return () => {
      document.body.classList.remove('public-page-active')
      document.getElementById('root')?.classList.remove('public-root')
    }
  }, [])

  return (
    <div className="public-page">
      <PublicHeader />
      
      <main className="public-content">
        <div className="content-container">
          <h1 className="page-title">Our Services</h1>
          
          <div className="services-grid">
            <div className="service-card">
              <div className="service-icon">🏸</div>
              <h2>Badminton Court Rental</h2>
              <p>
                Book our professional-grade badminton courts for your practice sessions or friendly matches. 
                Available for hourly rental with flexible scheduling options.
              </p>
              <ul className="service-features">
                <li>Professional-grade courts</li>
                <li>Flexible hourly booking</li>
                <li>Equipment rental available</li>
                <li>Online reservation system</li>
              </ul>
            </div>

            <div className="service-card">
              <div className="service-icon">💪</div>
              <h2>Gym Facilities</h2>
              <p>
                Access our fully equipped gym with modern fitness equipment. Train with weights, 
                cardio machines, and functional training areas.
              </p>
              <ul className="service-features">
                <li>State-of-the-art equipment</li>
                <li>Cardio and strength training</li>
                <li>Personal trainer available</li>
                <li>24/7 access for members</li>
              </ul>
            </div>

            <div className="service-card">
              <div className="service-icon">👥</div>
              <h2>Membership Plans</h2>
              <p>
                Choose from our flexible membership options designed to fit your fitness goals and schedule. 
                Enjoy exclusive benefits and discounts.
              </p>
              <ul className="service-features">
                <li>Monthly and annual plans</li>
                <li>Student discounts</li>
                <li>Family packages</li>
                <li>No long-term contracts</li>
              </ul>
            </div>

            <div className="service-card">
              <div className="service-icon">🎯</div>
              <h2>Training Programs</h2>
              <p>
                Join our specialized training programs for badminton and fitness. Learn from experienced 
                coaches and improve your skills.
              </p>
              <ul className="service-features">
                <li>Badminton coaching</li>
                <li>Fitness classes</li>
                <li>Group training sessions</li>
                <li>Beginner to advanced levels</li>
              </ul>
            </div>
          </div>

          <div className="cta-section">
            <h2>Ready to Get Started?</h2>
            <p>Join us today and start your fitness journey!</p>
            <a href="/register" className="cta-button">Sign Up Now</a>
          </div>
        </div>
      </main>
    </div>
  )
}

export default Services

