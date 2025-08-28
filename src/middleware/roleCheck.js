const requireRole = (allowedRoles) => {
  return (req, res, next) => {
    if (!req.session.userId) {
      return res.status(401).json({ error: 'Authentication required' });
    }
    
    if (!allowedRoles.includes(req.session.userRole)) {
      return res.status(403).json({ error: 'Insufficient permissions' });
    }
    
    next();
  };
};

const requireSuperAdmin = (req, res, next) => {
  if (!req.session.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }
  
  if (req.session.userRole !== 'super_admin') {
    return res.status(403).json({ error: 'Super admin access required' });
  }
  
  next();
};

const requireAdminOrSuperAdmin = (req, res, next) => {
  if (!req.session.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }
  
  if (!['admin', 'super_admin'].includes(req.session.userRole)) {
    return res.status(403).json({ error: 'Admin access required' });
  }
  
  next();
};

module.exports = { 
  requireRole, 
  requireSuperAdmin, 
  requireAdminOrSuperAdmin 
};