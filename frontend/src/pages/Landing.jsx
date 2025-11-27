import { Link } from 'react-router-dom'
import { useEffect } from 'react'
import PublicHeader from '../components/PublicHeader'
import './Landing.css'

function Landing() {
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
            <div className="graphic-background">
              <div className="graphic-grid"></div>
              <div className="sports-equipment">
                <div className="dumbbell-container">
                  <div className="dumbbell dumbbell-1">
                    <div className="dumbbell-bar"></div>
                    <div className="dumbbell-weight left"></div>
                    <div className="dumbbell-weight right"></div>
                  </div>
                  <div className="dumbbell dumbbell-2">
                    <div className="dumbbell-bar"></div>
                    <div className="dumbbell-weight left"></div>
                    <div className="dumbbell-weight right"></div>
                  </div>
                </div>
                <div className="shuttlecock">
                  <div className="shuttlecock-body"></div>
                  <div className="shuttlecock-base"></div>
                </div>
                <div className="racket">
                  <div className="racket-head"></div>
                  <div className="racket-handle"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}

export default Landing

