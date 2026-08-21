-- Insert the admin account for admin@halykpetroleum-kz.com
-- Password: Nigeria1234@ (hashed with bcrypt)
-- This matches the hash expected by Vp_auth::attempt() (PASSWORD_BCRYPT)

INSERT IGNORE INTO `users` (`id`, `email`, `password`, `firstName`, `lastName`, `role`, `isActive`, `mustChangePassword`, `emailVerified`, `createdAt`, `updatedAt`)
VALUES (
  'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
  'admin@halykpetroleum-kz.com',
  '$2b$12$nl8rwgVMLEaRGk9kQ54L4eUn5whqzjUVLLgGIVQ4ntXZsdumfploW',
  'Admin',
  'User',
  'SUPER_ADMIN',
  1,
  0,
  1,
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `isActive` = VALUES(`isActive`),
  `mustChangePassword` = VALUES(`mustChangePassword`),
  `role` = VALUES(`role`),
  `updatedAt` = NOW();
