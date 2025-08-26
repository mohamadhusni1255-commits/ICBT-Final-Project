const requireAuth = (req, res, next) => {
  if (!req.session.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }
  next();
};

const optionalAuth = (req, res, next) => {
  // Just passes through, session info available if present
  next();
};

module.exports = { requireAuth, optionalAuth };