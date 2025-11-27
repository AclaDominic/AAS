import { useEffect } from 'react'
import PublicHeader from '../components/PublicHeader'
import './PublicPages.css'

function AboutUs() {
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
          <h1 className="page-title">About L.R. Camacho</h1>
          
          <div className="about-section">
            <div className="about-content">
              <h2>Our Story</h2>
              <p>
                L.R. Camacho Badminton & Gym was founded with a vision to create a premier fitness destination 
                that combines the excitement of badminton with comprehensive gym facilities. We believe that 
                fitness should be accessible, enjoyable, and effective for everyone.
              </p>
              
              <h2>Our Mission</h2>
              <p>
                To provide a world-class facility where individuals and families can pursue their fitness goals 
                through badminton, strength training, and overall wellness. We are committed to creating a 
                welcoming environment that inspires and motivates our members to achieve their best.
              </p>
              
              <h2>Our Values</h2>
              <div className="values-grid">
                <div className="value-item">
                  <h3>Excellence</h3>
                  <p>We maintain the highest standards in our facilities and services.</p>
                </div>
                <div className="value-item">
                  <h3>Community</h3>
                  <p>We foster a supportive and inclusive environment for all members.</p>
                </div>
                <div className="value-item">
                  <h3>Accessibility</h3>
                  <p>We make fitness affordable and accessible to everyone.</p>
                </div>
                <div className="value-item">
                  <h3>Innovation</h3>
                  <p>We continuously improve our facilities and services.</p>
                </div>
              </div>
              
              <h2>Why Choose Us?</h2>
              <ul className="features-list">
                <li>🏸 Professional badminton courts with premium flooring</li>
                <li>💪 Modern gym equipment from leading brands</li>
                <li>👨‍🏫 Experienced coaches and trainers</li>
                <li>💰 Affordable pricing starting from P20</li>
                <li>🕐 Flexible hours to fit your schedule</li>
                <li>🏆 Competitive programs and tournaments</li>
              </ul>
            </div>
          </div>

          <div className="cta-section">
            <h2>Join Our Community</h2>
            <p>Become part of the L.R. Camacho family today!</p>
            <a href="/register" className="cta-button">Get Started</a>
          </div>
        </div>
      </main>
    </div>
  )
}

export default AboutUs

