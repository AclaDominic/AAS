import { Link } from 'react-router-dom'
import { useEffect } from 'react'
import PublicHeader from '../components/PublicHeader'
import lrLogo from '../assets/lrlogo.jpg'
import './Landing.css'

function Home() {
  useEffect(() => {
    document.body.classList.add('landing-page-active')
    document.getElementById('root')?.classList.add('landing-root')
    
    return () => {
      document.body.classList.remove('landing-page-active')
      document.getElementById('root')?.classList.remove('landing-root')
    }
  }, [])

  return (
    <div className="landing-page">
      <PublicHeader />

      {/* Hero Section */}
      <section className="hero-section">
        <div className="hero-content">
          <div className="hero-text">
            <h1 className="hero-headline">
              SMASH <span className="highlight-blue">HARD</span>
              <br />
              TRAIN <span className="highlight-blue">HARD</span>
              <br />
              STAY <span className="highlight-blue">FIT</span>
            </h1>
            <p className="hero-description">
              Experience the best of both worlds at L.R. Camacho Badminton & Gym—where your game meets your goals. 
              Rent a court, hit the gym, and make every session a step toward becoming stronger, faster, and fitter.
            </p>
            <div className="hero-cta">
              <p className="price-text">For as low as P20!!</p>
              <Link to="/register" className="cta-button">
                Avail NOW!!
              </Link>
            </div>
          </div>
          <div className="hero-graphic">
            <div className="logo-image-container">
              <img src={lrLogo} alt="L.R. Camacho Logo" className="logo-image" />
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}

export default Home

