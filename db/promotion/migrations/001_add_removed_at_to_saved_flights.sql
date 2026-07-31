-- tad46 - example ordered migration (numbered prefix controls run order)
-- Soft-delete support for saved_flights, needed for US-05 AC5 evidence.

ALTER TABLE `saved_flights`
    ADD COLUMN `removed_at` DATETIME NULL DEFAULT NULL;

-- Replace hard-delete unique index with one that ignores removed rows,
-- so a user can re-save a flight after removing it.
ALTER TABLE `saved_flights`
    DROP INDEX `unique_user_flight`,
    ADD UNIQUE INDEX `uq_user_flight_active` (`user_id`, `flight_number`, `removed_at`);
