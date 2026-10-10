import { GoogleLogin } from '@react-oauth/google';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function GoogleAuthButton({ role = 'customer', onError, disabled = false }) {
  const { loginWithGoogle } = useAuth();
  const navigate = useNavigate();

  const handleSuccess = async (credentialResponse) => {
    const { credential } = credentialResponse || {};
    if (!credential) {
      onError?.('Google did not return a credential.');
      return;
    }

    const result = await loginWithGoogle(credential, role || 'customer');
    if (!result.success) {
      onError?.(result.error);
      return;
    }

    const r = result.user?.role;
    if (r === 'admin') navigate('/admin');
    else if (r === 'owner') {
      if (result.user?.is_approved === false) {
        onError?.('Your salon owner account is pending admin approval.');
      } else {
        navigate('/owner');
      }
    } else {
      navigate('/dashboard');
    }
  };

  return (
    <div
      className={`w-full flex justify-center ${disabled ? 'pointer-events-none opacity-50' : ''}`}
      aria-disabled={disabled}
    >
      <GoogleLogin
        onSuccess={handleSuccess}
        onError={() => onError?.('Google sign-in was cancelled or failed.')}
        theme="outline"
        size="large"
        width="360"
        text="continue_with"
        shape="rectangular"
        logo_alignment="center"
      />
    </div>
  );
}
