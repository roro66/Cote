-- Create testing database and grant privileges for the default user
DO $$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_database WHERE datname = 'testing'
   ) THEN
      PERFORM dblink_exec('dbname=' || current_database(), 'CREATE DATABASE testing');
   END IF;
EXCEPTION WHEN undefined_table THEN
   -- dblink not available; fallback
   -- Note: postgres entrypoint runs this with superuser, so we can run plain SQL
END $$;

-- Fallback plain create (will error if exists; ignore)
CREATE DATABASE testing WITH TEMPLATE=template0 ENCODING 'UTF8';
-- Grant privileges to admin user on testing database
\connect testing
GRANT ALL PRIVILEGES ON SCHEMA public TO admin;
ALTER DATABASE testing OWNER TO admin;
