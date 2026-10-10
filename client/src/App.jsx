import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import SalonsPage from './pages/SalonsPage';
import ServicesPage from './pages/ServicesPage';
import DealsPage from './pages/DealsPage';
import AboutPage from './pages/AboutPage';
import SalonDetailPage from './pages/SalonDetailPage';
// Add other page imports as needed

const App = () => {
  return (
    <Router>
      <div className="flex flex-col min-h-screen">
        <Navbar />
        
        <main className="flex-grow">
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/salons" element={<SalonsPage />} />
            <Route path="/services" element={<ServicesPage />} />
            <Route path="/deals" element={<DealsPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/salon/:id" element={<SalonDetailPage />} />
          {/*  <Route path="/salon/:id/book" element={<BookingPage />} />
            <Route path="/privacy" element={<PrivacyPage />} />
            <Route path="/terms" element={<TermsPage />} />
            <Route path="/faq" element={<FAQPage />} />
            <Route path="/contact" element={<ContactPage />} />
             Add more routes as needed */}
          </Routes>
        </main>
        
        {/* Footer will be removed from individual pages */}
      </div>
    </Router>
  );
}

export default App;