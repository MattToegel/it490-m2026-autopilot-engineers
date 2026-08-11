-- rma9 - email verification stretch feature: adds the columns needed to
-- track verification state, the hashed verification code, and its
-- expiration. Already live on dev (added directly); this formalizes it
-- so migrate.php brings qa/prod in sync.

ALTER TABLE `users`
    ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `verification_code_hash` VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN `verification_expires_at` DATETIME NULL DEFAULT NULL,
    ADD COLUMN `verified_at` DATETIME NULL DEFAULT NULL;