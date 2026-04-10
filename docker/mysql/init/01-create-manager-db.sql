-- Banco para o painel de gerenciamento Laravel
CREATE DATABASE IF NOT EXISTS `wp_manager`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `wp_manager`.* TO 'wordpress'@'%';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
