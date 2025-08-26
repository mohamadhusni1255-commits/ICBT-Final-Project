const crypto = require('crypto');

const generateCSRFToken = (session) => {
  const token = crypto.randomBytes(32).toString('hex');
  session.csrfToken = token;
  return token;
};

const validateCSRFToken = (session, providedToken) => {
  if (!session.csrfToken || !providedToken) {
    return false;
  }
  
  // Use constant-time comparison to prevent timing attacks
  const sessionToken = Buffer.from(session.csrfToken, 'hex');
  const providedBuffer = Buffer.from(providedToken, 'hex');
  
  if (sessionToken.length !== providedBuffer.length) {
    return false;
  }
  
  return crypto.timingSafeEqual(sessionToken, providedBuffer);
};

module.exports = { generateCSRFToken, validateCSRFToken };