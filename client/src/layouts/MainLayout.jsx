import { useEffect } from 'react'
import Navbar from '../components/Navbar.jsx'
import { Outlet, useLocation } from 'react-router-dom'
import SEO from '../components/SEO.jsx'
import api from '../utils/api.js'

export default function MainLayout() {
  const { pathname } = useLocation()
  const hideNavbar = ['/login', '/register', '/forgot-password', '/reset-password'].includes(pathname)

  useEffect(() => {
    const excludedPrefixes = ['/admin', '/owner', '/dashboard'];
    const excludedPaths = ['/login', '/register', '/forgot-password', '/reset-password'];

    if (excludedPaths.includes(pathname) || excludedPrefixes.some((prefix) => pathname.startsWith(prefix))) {
      return;
    }

    const getId = (storage, key) => {
      let value = storage.getItem(key);
      if (!value) {
        value = typeof crypto?.randomUUID === 'function'
          ? crypto.randomUUID()
          : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        storage.setItem(key, value);
      }
      return value;
    };

    api.post('/site-visits', {
      path: pathname,
      url: window.location.href,
      referrer: document.referrer || null,
      visitorId: getId(localStorage, 'bms_visitor_id'),
      sessionId: getId(sessionStorage, 'bms_session_id'),
    }).catch(() => {});
  }, [pathname])

  return (
    <>
      <SEO />

      <div className='min-h-screen flex flex-col justify-between'>
        {!hideNavbar && <Navbar/>}
        <Outlet/>
      </div>
    </>
  )
}
