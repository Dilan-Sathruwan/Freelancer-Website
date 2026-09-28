-- 002_seed_dummy_data.sql
-- Versioned seed migration entrypoint.
-- Usage (from repository root):
--   mysql -u <user> -p <database> < sql/migrations/002_seed_dummy_data.sql

SOURCE sql/Dummy_Data.txt;
